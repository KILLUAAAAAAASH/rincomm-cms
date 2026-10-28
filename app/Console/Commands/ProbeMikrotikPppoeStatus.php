<?php

namespace App\Console\Commands;

use App\Services\MikrotikPppoeStatusClient;
use Illuminate\Console\Command;

/**
 * Operator-only, read-only diagnostic. Does not create accounts or sessions.
 * It is not a simulation and never assumes that the router is reachable.
 */
final class ProbeMikrotikPppoeStatus extends Command
{
    protected $signature = 'mikrotik:probe
        {--live : Explicitly permit one real read-only RouterOS status request}
        {--username= : Optional test-only PPPoE username (interactive entry is safer for real usernames)}';

    protected $description = 'Check one PPPoE session on the configured RouterOS v7 HTTPS REST endpoint (read-only)';

    public function handle(MikrotikPppoeStatusClient $client): int
    {
        if (! $this->option('live')) {
            $this->warn('No router request made. Use --live to explicitly authorize a read-only check.');
            return self::SUCCESS;
        }

        if (! config('mikrotik.enabled')) {
            $this->error('UNAVAILABLE: router monitoring is disabled; no request made.');
            return self::FAILURE;
        }

        $baseUrl = trim((string) config('mikrotik.base_url'));
        if (! str_starts_with($baseUrl, 'https://') ||
            trim((string) config('mikrotik.username')) === '' ||
            (string) config('mikrotik.password') === '') {
            $this->error('UNAVAILABLE: HTTPS router URL or credentials are missing; no request made.');
            return self::FAILURE;
        }

        $option = $this->option('username');
        $username = is_string($option) ? trim($option) : '';
        if ($username === '') {
            $entered = $this->secret('Enter PPPoE username (hidden input)');
            $username = is_string($entered) ? trim($entered) : '';
        }

        if ($username === '' || preg_match('/[\x00-\x20\x7F]/', $username)) {
            $this->error('UNAVAILABLE: a valid PPPoE username is required; no request made.');
            return self::FAILURE;
        }

        // The client reads /rest/ppp/active; it does not configure a router.
        try {
            $status = $client->check($username);
        } catch (\Throwable $exception) {
            $this->error('UNAVAILABLE: router check failed safely; no credentials or error details shown.');
            return self::FAILURE;
        }
        if ($status === 'online') {
            $this->info('ONLINE: the router returned a matching active PPPoE session.');
            return self::SUCCESS;
        }
        if ($status === 'offline') {
            $this->info('OFFLINE: router replied but no matching active PPPoE session was found.');
            return self::SUCCESS;
        }

        $this->error('UNAVAILABLE: check router HTTPS, certificate, credentials, access policy and reachability.');
        return self::FAILURE;
    }
}
