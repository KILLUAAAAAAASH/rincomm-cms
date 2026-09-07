<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated user's basic profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate(
            [
                'name' => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                ],

                'email' => [
                    'bail',
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
            ],
            [
                'name.required' => 'Please enter your name.',

                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'An account with this email address already exists.',
            ]
        );

        $user->update([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
