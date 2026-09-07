<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_registration_page_returns_a_successful_response(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_forgot_password_page_returns_a_successful_response(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
    }
}
