<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Display the profile page.
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
     * Update the authenticated user's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $customer = $user->isCustomer()
            ? $user->customer
            : null;

        // Keep the User name and Customer name in sync.
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

            $middleName = isset($validated['middle_name']) &&
                trim($validated['middle_name']) !== ''
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

            $previousName = $user->name;

            $previousCustomerName = [
                'first_name' => $customer->first_name,
                'middle_name' => $customer->middle_name,
                'last_name' => $customer->last_name,
            ];

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

            $profileChanged =
                $previousName !== $fullName ||
                $previousCustomerName['first_name'] !== $firstName ||
                $previousCustomerName['middle_name'] !== $middleName ||
                $previousCustomerName['last_name'] !== $lastName;

            if ($profileChanged) {
                $this->activityLogger->record(
                    action: 'user.profile_updated',
                    actor: $user,
                    target: $user,
                    description: 'Updated account profile information.',
                    metadata: [
                        'old_name' => $previousName,
                        'new_name' => $fullName,
                    ],
                    request: $request
                );
            }

            return redirect()
                ->route('profile.edit')
                ->with(
                    'success',
                    'Profile updated successfully.'
                );
        }

        // Users without a Customer record update their account name directly.
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

        $previousName = $user->name;
        $newName = trim($validated['name']);

        $user->name = $newName;
        $user->save();

        if ($previousName !== $newName) {
            $this->activityLogger->record(
                action: 'user.profile_updated',
                actor: $user,
                target: $user,
                description: 'Updated account profile information.',
                metadata: [
                    'old_name' => $previousName,
                    'new_name' => $newName,
                ],
                request: $request
            );
        }

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Profile updated successfully.'
            );
    }
}
