<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileUpdateRequestController extends Controller
{
    /** These subscriber fields require staff review. Login email and installation address are excluded. */
    private const FIELDS = ['phone', 'address', 'city', 'province', 'postal_code', 'billing_address'];

    public function index(Request $request): View
    {
        $customer = $request->user()->customer;
        $requests = $customer
            ? CustomerProfileUpdateRequest::query()
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->paginate(10)
            : null;

        return view('customer.profile-update-requests.index', compact('customer', 'requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;
        abort_unless($customer instanceof Customer, 403, 'A subscriber profile is required.');

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($customer, $request, $validated): void {
            $locked = Customer::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();

            if (CustomerProfileUpdateRequest::query()
                ->where('customer_id', $locked->id)
                ->where('status', 'pending')
                ->exists()) {
                throw ValidationException::withMessages([
                    'request' => 'You already have a pending profile update request.',
                ]);
            }

            $current = [];
            $proposed = [];
            foreach (self::FIELDS as $field) {
                $current[$field] = $this->normalized($locked->{$field});
                $proposed[$field] = $this->normalized($validated[$field] ?? null);
            }

            if ($current === $proposed) {
                throw ValidationException::withMessages([
                    'request' => 'Enter at least one change before submitting.',
                ]);
            }

            CustomerProfileUpdateRequest::create([
                'customer_id' => $locked->id,
                'requested_by' => $request->user()->id,
                'status' => 'pending',
                'current_values' => $current,
                'requested_values' => $proposed,
                'reason' => $this->normalized($validated['reason'] ?? null),
            ]);
        });

        return redirect()->route('customer.profile-update-requests.index')
            ->with('success', 'Your profile update request was submitted for staff review. No account details have changed yet.');
    }

    private function normalized(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
