<?php

namespace Tests\Feature;

use App\Services\MikrotikPppoeStatusClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MikrotikPppoeStatusClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mikrotik', [
            'enabled' => true,
            'base_url' => 'https://router.test',
            'username' => 'status_reader',
            'password' => 'test-only-password',
            'ca_bundle' => null,
        ]);
    }

    public function test_unconfigured_connection_is_unavailable_without_http_request(): void
    {
        config()->set('mikrotik.enabled', false);
        Http::fake();

        $this->assertSame('unavailable', app(MikrotikPppoeStatusClient::class)->check('cust1'));
        Http::assertNothingSent();
    }

    public function test_matching_pppoe_session_is_online(): void
    {
        Http::fake(['https://router.test/*' => Http::response([
            ['name' => 'cust1', 'service' => 'pppoe'],
        ])]);

        $this->assertSame('online', app(MikrotikPppoeStatusClient::class)->check('cust1'));
        Http::assertSentCount(1);
    }

    public function test_empty_active_session_list_is_offline(): void
    {
        Http::fake(['https://router.test/*' => Http::response([])]);

        $this->assertSame('offline', app(MikrotikPppoeStatusClient::class)->check('cust1'));
    }

    public function test_router_authentication_error_is_unavailable_not_offline(): void
    {
        Http::fake(['https://router.test/*' => Http::response(['error' => 'Unauthorized'], 401)]);

        $this->assertSame('unavailable', app(MikrotikPppoeStatusClient::class)->check('cust1'));
    }

    public function test_malformed_router_response_is_unavailable(): void
    {
        Http::fake(['https://router.test/*' => Http::response(['unexpected' => 'response'])]);

        $this->assertSame('unavailable', app(MikrotikPppoeStatusClient::class)->check('cust1'));
    }
}
