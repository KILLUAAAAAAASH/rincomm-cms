<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $customer = $user->isCustomer()
            ? $user->customer
            : null;

        return view('profile.edit', [
            'user' => $user,
            'customer' => $customer,
        ]);
    }

    /**
     * Update the authenticated user's current profile name.
     *
     * Email changes are intentionally deferred until the verified-email
     * OTP workflow is implemented.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $customer = $user->isCustomer()
            ? $user->customer
            : null;

        /*
        |--------------------------------------------------------------------------
        | Customer with linked subscriber profile
        |--------------------------------------------------------------------------
        |
        | The Customer record owns the structured subscriber name while the User
        | record owns authentication identity. Both current names are kept in
        | sync in a single database transaction.
        |
        | Historical ServiceApplication records are intentionally not modified.
        |
        */

        if ($customer) {
            $validated = $request->validate(
                [
                    'first_name' => [
                        'bail',
                        'required',
                        'string',
                        'max:100',
                    ],

                    'middle_name' => [
                        'nullable',
                        'string',
                        'max:100',
                    ],

                    'last_name' => [
                        'bail',
                        'required',
                        'string',
                        'max:100',
                    ],
                ],
                [
                    'first_name.required' => 'Please enter your first name.',
                    'last_name.required' => 'Please enter your last name.',
                ]
            );

            $firstName = trim($validated['first_name']);

            $middleName = isset($validated['middle_name'])
                && trim($validated['middle_name']) !== ''
                ? trim($validated['middle_name'])
                : null;

            $lastName = trim($validated['last_name']);

            $fullName = collect([
                $firstName,
                $middleName,
                $lastName,
            ])
                ->filter()
                ->implode(' ');

            DB::transaction(function () use (
                $user,
                $customer,
                $firstName,
                $middleName,
                $lastName,
                $fullName
            ): void {
                $user->name = $fullName;
                $user->save();

                $customer->update([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                ]);
            });

            return redirect()
                ->route('profile.edit')
                ->with(
                    'success',
                    'Profile updated successfully.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | User without linked Customer profile
        |--------------------------------------------------------------------------
        |
        | Administrator, Staff, Technician, and customer-role applicants without
        | a Customer record continue to use the User name directly.
        |
        */

        $validated = $request->validate(
            [
                'name' => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                ],
            ],
            [
                'name.required' => 'Please enter your name.',
            ]
        );

        $user->name = trim($validated['name']);
        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Profile updated successfully.'
            );
    }
}
