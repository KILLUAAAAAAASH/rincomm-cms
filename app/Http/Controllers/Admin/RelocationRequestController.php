<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RelocationRequest;
use App\Models\ServiceArea;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelocationRequestController extends Controller
{
    /**
     * Record a relocation request without changing the live installation address.
     */
    public function store(
        Request $request,
        Customer $subscriber
    ): RedirectResponse {
        $request->merge([
            'requested_installation_address' => $this->cleanAddress(
                (string) $request->input('requested_installation_address')
            ),
        ]);

        $validated = $request->validate(
            [
                'requested_service_area_id' => [
                    'required',
                    'integer',
                    'exists:service_areas,id',
                ],
                'requested_installation_address' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ],
            [
                'requested_service_area_id.required' =>
                'Please select the new service area.',

                'requested_service_area_id.exists' =>
                'The selected service area is no longer available.',

                'requested_installation_address.required' =>
                'Please enter the new installation address.',

                'requested_installation_address.max' =>
                'The installation address cannot exceed 255 characters.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $validated
        ) {
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'Only active subscribers can submit a relocation request.',
                ];
            }

            if (! $lockedSubscriber->installation_address) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscriber does not have a current installation address to relocate.',
                ];
            }

            $subscription = Subscription::query()
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscriber does not have an active subscription that can be relocated.',
                ];
            }

            $serviceArea = ServiceArea::query()
                ->whereKey($validated['requested_service_area_id'])
                ->lockForUpdate()
                ->first();

            if (! $serviceArea || ! $serviceArea->is_serviceable) {
                return [
                    'success' => false,
                    'message' =>
                    'The selected location is not currently serviceable by Rincomm.',
                ];
            }

            $currentAddress = $this->cleanAddress(
                $lockedSubscriber->installation_address
            );

            $requestedAddress = $validated['requested_installation_address'];

            if (strcasecmp($currentAddress, $requestedAddress) === 0) {
                return [
                    'success' => false,
                    'message' =>
                    'The new installation address must be different from the current installation address.',
                ];
            }

            $hasOpenRequest = RelocationRequest::query()
                ->where('subscription_id', $subscription->id)
                ->whereIn('status', [
                    'pending',
                    'approved',
                ])
                ->exists();

            if ($hasOpenRequest) {
                return [
                    'success' => false,
                    'message' =>
                    'This subscription already has an open relocation request.',
                ];
            }

            RelocationRequest::create([
                'customer_id' => $lockedSubscriber->id,
                'subscription_id' => $subscription->id,
                'requested_service_area_id' => $serviceArea->id,
                'requested_by' => $request->user()->id,
                'current_installation_address' =>
                $lockedSubscriber->installation_address,
                'requested_installation_address' => $requestedAddress,
                'requested_province' => $serviceArea->province,
                'requested_city_municipality' =>
                $serviceArea->city_municipality,
                'requested_barangay' => $serviceArea->barangay,
                'requested_postal_code' => $serviceArea->postal_code,
                'status' => 'pending',
            ]);

            return [
                'success' => true,
                'message' =>
                'Relocation request submitted successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Approve a pending relocation request.
     *
     * Approval does not change the subscriber's live installation address.
     */
    public function approve(
        Request $request,
        Customer $subscriber,
        RelocationRequest $relocationRequest
    ): RedirectResponse {
        $request->merge([
            'review_notes' => trim(
                (string) $request->input('review_notes')
            ),
        ]);

        $validated = $request->validate(
            [
                'review_notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'review_notes.max' =>
                'Review notes cannot exceed 1000 characters.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $relocationRequest,
            $validated
        ) {
            $lockedRelocation = $this->lockedRelocationForSubscriber(
                $subscriber,
                $relocationRequest
            );

            if ($lockedRelocation->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending relocation requests can be approved.',
                ];
            }

            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber must be active before this relocation request can be approved.',
                ];
            }

            $subscription = Subscription::query()
                ->whereKey($lockedRelocation->subscription_id)
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscription linked to this relocation request is no longer active.',
                ];
            }

            $serviceArea = ServiceArea::query()
                ->whereKey(
                    $lockedRelocation->requested_service_area_id
                )
                ->lockForUpdate()
                ->first();

            if (! $serviceArea || ! $serviceArea->is_serviceable) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested location is no longer serviceable by Rincomm.',
                ];
            }

            $currentAddress = $this->cleanAddress(
                $lockedSubscriber->installation_address ?? ''
            );

            $requestedAddress = $this->cleanAddress(
                $lockedRelocation->requested_installation_address
            );

            if (strcasecmp($currentAddress, $requestedAddress) === 0) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested installation address now matches the subscriber\'s current installation address.',
                ];
            }

            $lockedRelocation->status = 'approved';
            $lockedRelocation->reviewed_by = $request->user()->id;
            $lockedRelocation->review_notes =
                $validated['review_notes'] ?: null;
            $lockedRelocation->reviewed_at = now();
            $lockedRelocation->save();

            return [
                'success' => true,
                'message' =>
                'Relocation request approved successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Reject a pending relocation request.
     */
    public function reject(
        Request $request,
        Customer $subscriber,
        RelocationRequest $relocationRequest
    ): RedirectResponse {
        $request->merge([
            'review_notes' => trim(
                (string) $request->input('review_notes')
            ),
        ]);

        $validated = $request->validate(
            [
                'review_notes' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'review_notes.required' =>
                'Please provide a reason for rejecting this relocation request.',

                'review_notes.max' =>
                'The rejection reason cannot exceed 1000 characters.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $relocationRequest,
            $validated
        ) {
            $lockedRelocation = $this->lockedRelocationForSubscriber(
                $subscriber,
                $relocationRequest
            );

            if ($lockedRelocation->status !== 'pending') {
                return [
                    'success' => false,
                    'message' =>
                    'Only pending relocation requests can be rejected.',
                ];
            }

            $lockedRelocation->status = 'rejected';
            $lockedRelocation->reviewed_by = $request->user()->id;
            $lockedRelocation->review_notes =
                $validated['review_notes'];
            $lockedRelocation->reviewed_at = now();
            $lockedRelocation->save();

            return [
                'success' => true,
                'message' =>
                'Relocation request rejected successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Complete an approved relocation request and update the live address.
     */
    public function complete(
        Request $request,
        Customer $subscriber,
        RelocationRequest $relocationRequest
    ): RedirectResponse {
        $result = DB::transaction(function () use (
            $subscriber,
            $relocationRequest
        ) {
            $lockedRelocation = $this->lockedRelocationForSubscriber(
                $subscriber,
                $relocationRequest
            );

            if ($lockedRelocation->status !== 'approved') {
                return [
                    'success' => false,
                    'message' =>
                    'Only approved relocation requests can be completed.',
                ];
            }

            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscriber->status !== 'active') {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber must be active before this relocation can be completed.',
                ];
            }

            $subscription = Subscription::query()
                ->whereKey($lockedRelocation->subscription_id)
                ->where('customer_id', $lockedSubscriber->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscription linked to this relocation request is no longer active.',
                ];
            }

            $serviceArea = ServiceArea::query()
                ->whereKey(
                    $lockedRelocation->requested_service_area_id
                )
                ->lockForUpdate()
                ->first();

            if (! $serviceArea || ! $serviceArea->is_serviceable) {
                return [
                    'success' => false,
                    'message' =>
                    'The requested location is no longer serviceable by Rincomm.',
                ];
            }

            $liveAddress = $this->cleanAddress(
                $lockedSubscriber->installation_address ?? ''
            );

            $originalAddress = $this->cleanAddress(
                $lockedRelocation->current_installation_address
            );

            if (strcasecmp($liveAddress, $originalAddress) !== 0) {
                return [
                    'success' => false,
                    'message' =>
                    'The subscriber\'s live installation address has changed since this relocation request was created. Review the request before completing it.',
                ];
            }

            $lockedSubscriber->installation_address =
                $lockedRelocation->requested_installation_address;

            $lockedSubscriber->save();

            $lockedRelocation->status = 'completed';
            $lockedRelocation->save();

            return [
                'success' => true,
                'message' =>
                'Relocation completed and the subscriber installation address was updated successfully.',
            ];
        });

        return $this->redirectWithResult(
            $subscriber,
            $result
        );
    }

    /**
     * Lock a relocation request and verify that it belongs to the subscriber.
     */
    private function lockedRelocationForSubscriber(
        Customer $subscriber,
        RelocationRequest $relocationRequest
    ): RelocationRequest {
        return RelocationRequest::query()
            ->whereKey($relocationRequest->getKey())
            ->where('customer_id', $subscriber->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Redirect to the subscriber screen with the workflow result.
     */
    private function redirectWithResult(
        Customer $subscriber,
        array $result
    ): RedirectResponse {
        $redirect = redirect()
            ->route('admin.subscribers.show', $subscriber);

        if (! $result['success']) {
            return $redirect->with(
                'error',
                $result['message']
            );
        }

        return $redirect->with(
            'success',
            $result['message']
        );
    }

    /**
     * Normalize an address before comparing it.
     */
    private function cleanAddress(string $address): string
    {
        return preg_replace(
            '/\s+/',
            ' ',
            trim($address)
        ) ?? '';
    }
}
