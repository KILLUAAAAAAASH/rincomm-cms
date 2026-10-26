<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class MikrotikPppoeStatusClient
{
    /**
     * Query a RouterOS v7 HTTPS REST endpoint. No router mutation is performed.
     * Status refers to a PPPoE session, not general Internet reachability.
     */
    public function check(?string $pppoeUsername): string
    {
        $username = trim($pppoeUsername ?? '');
        $baseUrl = trim((string) config('mikrotik.base_url'));
        $routerUser = (string) config('mikrotik.username');
        $routerPassword = (string) config('mikrotik.password');

        if (
            ! config('mikrotik.enabled')
            || $username === ''
            || ! str_starts_with($baseUrl, 'https://')
            || $routerUser === ''
            || $routerPassword === ''
        ) {
            return 'unavailable';
        }

        try {
            $request = Http::acceptJson()
                ->withBasicAuth($routerUser, $routerPassword)
                ->connectTimeout(2)
                ->timeout(5);

            $caBundle = config('mikrotik.ca_bundle');

            if (is_string($caBundle) && $caBundle !== '') {
                $request = $request->withOptions(['verify' => $caBundle]);
            }

            $response = $request->get(
                rtrim($baseUrl, '/') . '/rest/ppp/active',
                ['name' => $username, 'service' => 'pppoe']
            );
        } catch (ConnectionException $exception) {
            return 'unavailable';
        }

        if (! $response->successful()) {
            return 'unavailable';
        }

        $sessions = $response->json();

        if (! is_array($sessions) || ! array_is_list($sessions)) {
            return 'unavailable';
        }

        foreach ($sessions as $session) {
            if (! is_array($session) || ! isset($session['name'], $session['service'])) {
                return 'unavailable';
            }

            if ($session['name'] === $username && $session['service'] === 'pppoe') {
                return 'online';
            }
        }

        return 'offline';
    }
}
