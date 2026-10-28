<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MikrotikPppoeProbeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mikrotik', [
            'enabled' => true,
            'base_url' => 'https://router.example.test',
            'username' => 'reader',
            'password' => 'test-only-password',
            'ca_bundle' => null,
        ]);
        Http::preventStrayRequests(); // Even tests with incorrect fakes cannot contact a router.
    }

    public function test_without_explicit_live_flag_never_contacts_router(): void
    {
        $this->artisan('mikrotik:probe', ['--username' => 'demo'])
            ->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_disabled_configuration_never_contacts_router(): void
    {
        config()->set('mikrotik.enabled', false);
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'demo'])
            ->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_missing_https_configuration_never_contacts_router(): void
    {
        config()->set('mikrotik.base_url', 'http://router.example.test');
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'demo'])
            ->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_genuine_looking_matching_response_is_classified_online(): void
    {
        Http::fake(['https://router.example.test/*' => Http::response([
            ['name' => 'demo', 'service' => 'pppoe', 'address' => '192.0.2.2'],
        ])]);
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'demo'])
            ->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), '/rest/ppp/active') &&
            $request->method() === 'GET'
        );
    }

    public function test_empty_active_session_list_is_offline(): void
    {
        Http::fake(['https://router.example.test/*' => Http::response([])]);
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'demo'])
            ->assertExitCode(0);
        Http::assertSentCount(1);
    }

    public function test_failed_authentication_is_unavailable(): void
    {
        Http::fake(['https://router.example.test/*' => Http::response(['error' => 'unauthorized'], 401)]);
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'demo'])
            ->assertExitCode(1);
        Http::assertSentCount(1);
    }

    public function test_invalid_username_does_not_contact_router(): void
    {
        $this->artisan('mikrotik:probe', ['--live' => true, '--username' => 'bad name'])
            ->assertExitCode(1);
        Http::assertNothingSent();
    }
}
