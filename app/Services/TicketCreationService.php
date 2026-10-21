<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketCreationService
{
    public function create(
        Customer $customer,
        array $data,
        bool $staffCreated = false
    ): ServiceRequest {
        return DB::transaction(function () use (
            $customer,
            $data,
            $staffCreated
        ): ServiceRequest {
            $customer = Customer::query()
                ->whereKey($customer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $requestType = $data['request_type'];

            $subscriptionId = $data['subscription_id'] ?? null;

            $subscription = null;

            if ($subscriptionId !== null && $subscriptionId !== '') {
                $subscription = Subscription::query()
                    ->whereKey($subscriptionId)
                    ->where('customer_id', $customer->id)
                    ->where('status', 'active')
                    ->first();

                if (! $subscription) {
                    throw ValidationException::withMessages([
                        'subscription_id' =>
                            'Select an active subscription belonging to this subscriber.',
                    ]);
                }
            }

            if ($requestType === 'technical' && ! $subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' =>
                        'An active subscription is required for technical tickets.',
                ]);
            }

            $priority = 'normal';

            if ($staffCreated) {
                $priority = $data['priority'] ?? 'normal';
            }

            $ticket = ServiceRequest::query()->create([
                'ticket_number' => null,
                'subject' => trim($data['subject']),
                'customer_id' => $customer->id,
                'subscription_id' => $subscription?->id,
                'service_plan_id' => $subscription?->service_plan_id,
                'request_type' => $requestType,
                'issue_type' => $data['issue_type'],
                'priority' => $priority,
                'description' => trim($data['description']),
                'status' => 'open',
                'requested_date' => now()->toDateString(),
            ]);

            $ticket->update([
                'ticket_number' => sprintf(
                    'TKT-%06d',
                    $ticket->id
                ),
            ]);

            return $ticket->refresh();
        });
    }
}