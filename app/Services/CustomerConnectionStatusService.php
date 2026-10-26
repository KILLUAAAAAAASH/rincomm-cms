<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerNetworkAccount;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class CustomerConnectionStatusService
{
    private const ROUTER_KEY = 'primary';

    public function __construct(
        private readonly MikrotikPppoeStatusClient $routerClient
    ) {}

    /**
     * Status describes an authenticated PPPoE session, not internet reachability.
     * Only a primary-router account belonging to the authenticated customer is used.
     */
    public function forCustomer(Customer $customer): array
    {
        if (! config('mikrotik.enabled')) {
            return ['status' => 'unavailable', 'checked_at' => null];
        }

        $account = CustomerNetworkAccount::query()
            ->where('customer_id', $customer->getKey())
            ->where('router_key', self::ROUTER_KEY)
            ->first();

        if ($account === null || trim($account->pppoe_username) === '') {
            return ['status' => 'unavailable', 'checked_at' => null];
        }

        // Cache per account and username so a mapping change cannot inherit old status.
        // No PPPoE username or router secret is included in the cache key or response.
        $cacheKey = 'rincomm:pppoe:status:' . hash(
            'sha256',
            $account->getKey() . '|' . self::ROUTER_KEY . '|' . $account->pppoe_username
        );

        try {
            return Cache::remember($cacheKey, now()->addSeconds(15), function () use ($account): array {
            try {
                $status = $this->routerClient->check($account->pppoe_username);
            } catch (Throwable $exception) {
                // Never describe a failed router check as an offline subscriber.
                $status = 'unavailable';
            }

            if (! in_array($status, ['online', 'offline'], true)) {
                return ['status' => 'unavailable', 'checked_at' => null];
            }

            return [
                'status' => $status,
                'checked_at' => now()->toIso8601String(),
            ];
            });
        } catch (Throwable $exception) {
            return ['status' => 'unavailable', 'checked_at' => null];
        }
    }
}
