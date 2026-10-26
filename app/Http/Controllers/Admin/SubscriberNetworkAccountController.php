<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerNetworkAccount;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriberNetworkAccountController extends Controller
{
    private const ROUTER_KEY = 'primary';

    public function show(Customer $subscriber): View
    {
        $networkAccount = CustomerNetworkAccount::query()
            ->where('customer_id', $subscriber->getKey())
            ->where('router_key', self::ROUTER_KEY)
            ->first();

        return view('admin.subscribers.network-account', [
            'subscriber' => $subscriber,
            'networkAccount' => $networkAccount,
        ]);
    }

    public function update(Request $request, Customer $subscriber): RedirectResponse
    {
        $input = $request->input('pppoe_username');
        if (is_string($input)) {
            $request->merge(['pppoe_username' => trim($input)]);
        }

        $existing = CustomerNetworkAccount::query()
            ->where('customer_id', $subscriber->getKey())
            ->where('router_key', self::ROUTER_KEY)
            ->first();

        $validated = $request->validate([
            'pppoe_username' => [
                'required',
                'string',
                'max:120',
                // Do not allow whitespace or non-printable characters in router logins.
                'regex:/\A[^\s\x00-\x1F\x7F]+\z/u',
                Rule::unique('customer_network_accounts', 'pppoe_username')
                    ->where('router_key', self::ROUTER_KEY)
                    ->ignore($existing?->getKey()),
            ],
        ], [
            'pppoe_username.regex' => 'The PPPoE username must not contain spaces or control characters.',
            'pppoe_username.unique' => 'This PPPoE username is already assigned to another subscriber.',
        ]);

        try {
            CustomerNetworkAccount::query()->updateOrCreate(
                ['customer_id' => $subscriber->getKey(), 'router_key' => self::ROUTER_KEY],
                ['pppoe_username' => $validated['pppoe_username']]
            );
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors([
                'pppoe_username' => 'That PPPoE account is already assigned. Refresh and try again.',
            ])->withInput();
        }

        Log::notice('Subscriber PPPoE mapping saved', [
            'actor_id' => $request->user()->getKey(),
            'customer_id' => $subscriber->getKey(),
            'router_key' => self::ROUTER_KEY,
        ]);

        return redirect()
            ->route('admin.subscribers.network-account.show', $subscriber)
            ->with('success', 'PPPoE account mapping saved. Router settings were not changed.');
    }

    public function destroy(Request $request, Customer $subscriber): RedirectResponse
    {
        $deleted = CustomerNetworkAccount::query()
            ->where('customer_id', $subscriber->getKey())
            ->where('router_key', self::ROUTER_KEY)
            ->delete();

        if ($deleted > 0) {
            Log::notice('Subscriber PPPoE mapping removed', [
                'actor_id' => $request->user()->getKey(),
                'customer_id' => $subscriber->getKey(),
                'router_key' => self::ROUTER_KEY,
            ]);
        }

        return redirect()
            ->route('admin.subscribers.network-account.show', $subscriber)
            ->with('success', 'PPPoE account mapping removed. No router session was disconnected.');
    }
}
