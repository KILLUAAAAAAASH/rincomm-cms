<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerProfileUpdateReviewService
{
    // Only customer-contact and billing fields approved in Stage 1.
    private const FIELDS = ['phone', 'address', 'city', 'province', 'postal_code', 'billing_address'];

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function decide(CustomerProfileUpdateRequest $profileUpdateRequest, User $actor, string $decision, string $notes, ?Request $httpRequest = null): void
    {
        DB::transaction(function () use ($profileUpdateRequest, $actor, $decision, $notes, $httpRequest): void {
            // Lock in the same order as customer submission: Customer -> request.
            $customer = Customer::query()->whereKey($profileUpdateRequest->customer_id)->lockForUpdate()->firstOrFail();
            $item = CustomerProfileUpdateRequest::query()->whereKey($profileUpdateRequest->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'pending' || (int) $item->customer_id !== (int) $customer->id) {
                throw ValidationException::withMessages(['request' => 'This request is no longer pending. Refresh the page.']);
            }

            $updates = [];
            if ($decision === 'approved') {
                $original = (array) $item->current_values;
                $proposed = (array) $item->requested_values;
                foreach (self::FIELDS as $field) {
                    $before = $this->normalized($original[$field] ?? null);
                    $after = $this->normalized($proposed[$field] ?? null);
                    if ($before === $after) {
                        continue;
                    }
                    // An intervening staff edit must never be silently overwritten.
                    if ($this->normalized($customer->{$field}) !== $before) {
                        throw ValidationException::withMessages([
                            'request' => 'Customer details changed after this request was submitted. Review the current profile and reject this outdated request.',
                        ]);
                    }
                    $updates[$field] = $after;
                }
                if ($updates === []) {
                    throw ValidationException::withMessages(['request' => 'No eligible change remains to approve.']);
                }
                $customer->fill($updates);
                $customer->save();
            }

            $item->forceFill([
                'status' => $decision,
                'reviewed_by' => $actor->id,
                'review_notes' => trim($notes),
                'reviewed_at' => now(),
                'applied_at' => $decision === 'approved' ? now() : null,
            ])->save();

            $this->activityLogger->record(
                action: $decision === 'approved' ? 'customer_profile_request_approved' : 'customer_profile_request_rejected',
                actor: $actor,
                target: $customer->user,
                description: 'Staff reviewed customer profile update request #'.$item->id.'.',
                metadata: [
                    'profile_update_request_id' => $item->id,
                    'customer_id' => $customer->id,
                    'decision' => $decision,
                    'fields_applied' => array_keys($updates),
                ],
                request: $httpRequest
            );
        });
    }

    private function normalized(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
