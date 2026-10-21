<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTicketCreationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $subscription->update(['status' => 'active']);

        $customer->user->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        return [
            $customer->user->refresh(),
            $customer,
            $subscription->refresh(),
        ];
    }

    private function activeUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function secondCustomer(): array
    {
        $user = $this->activeUser('customer');

        $customer = Customer::query()->create([
            'user_id' => $user->id,
            'customer_code' => 'CUST-TEST-9002',
            'first_name' => 'Second',
            'last_name' => 'Subscriber',
            'phone' => '09129999002',
            'email' => $user->email,
            'address' => 'Paniqui',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' => 'Paniqui, Tarlac',
            'installation_address' => 'Paniqui, Tarlac',
            'status' => 'active',
        ]);

        return [$user, $customer];
    }

    private function technicalPayload(int $subscriptionId): array
    {
        return [
            'request_type' => 'technical',
            'issue_type' => 'no_internet',
            'subscription_id' => $subscriptionId,
            'subject' => 'Internet connection unavailable',
            'description' => 'No connection since this morning.',
        ];
    }

    private function nontechnicalPayload(): array
    {
        return [
            'request_type' => 'non_technical',
            'issue_type' => 'billing',
            'subject' => 'Billing clarification',
            'description' => 'Please explain the latest invoice.',
        ];
    }

    public function test_customer_can_create_technical_ticket_for_own_subscription(): void
    {
        [$user, $customer, $subscription] = $this->fixture();

        $before = ServiceRequest::count();

        $response = $this->actingAs($user)
            ->post(
                route('customer.tickets.store'),
                $this->technicalPayload($subscription->id)
            );

        $this->assertSame($before + 1, ServiceRequest::count());

        $ticket = ServiceRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(
            route('customer.tickets.show', $ticket->id)
        );

        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame($subscription->id, $ticket->subscription_id);
        $this->assertSame($subscription->service_plan_id, $ticket->service_plan_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('normal', $ticket->priority);
        $this->assertSame(
            sprintf('TKT-%06d', $ticket->id),
            $ticket->ticket_number
        );
    }

    public function test_customer_can_create_billing_ticket_without_subscription(): void
    {
        [$user, $customer] = $this->fixture();

        $response = $this->actingAs($user)->post(
            route('customer.tickets.store'),
            $this->nontechnicalPayload()
        );

        $ticket = ServiceRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(
            route('customer.tickets.show', $ticket->id)
        );

        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertNull($ticket->subscription_id);
        $this->assertNull($ticket->service_plan_id);
        $this->assertSame('non_technical', $ticket->request_type);
    }

    public function test_customer_cannot_use_another_subscribers_subscription(): void
    {
        [, , $subscription] = $this->fixture();
        [$otherUser] = $this->secondCustomer();

        $before = ServiceRequest::count();

        $this->actingAs($otherUser)
            ->post(
                route('customer.tickets.store'),
                $this->technicalPayload($subscription->id)
            )
            ->assertSessionHasErrors('subscription_id');

        $this->assertSame($before, ServiceRequest::count());
    }

    public function test_customer_cannot_set_ticket_priority_or_impersonate_customer(): void
    {
        [$user, $customer] = $this->fixture();
        [, $otherCustomer] = $this->secondCustomer();

        $payload = array_merge(
            $this->nontechnicalPayload(),
            [
                'priority' => 'urgent',
                'customer_id' => $otherCustomer->id,
                'status' => 'closed',
                'ticket_number' => 'FAKE-123',
            ]
        );

        $this->actingAs($user)
            ->post(route('customer.tickets.store'), $payload)
            ->assertSessionHasNoErrors();

        $ticket = ServiceRequest::query()->latest('id')->firstOrFail();

        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame('normal', $ticket->priority);
        $this->assertSame('open', $ticket->status);
        $this->assertNotSame('FAKE-123', $ticket->ticket_number);
    }

    public function test_technical_ticket_requires_active_subscription(): void
    {
        [$user, , $subscription] = $this->fixture();

        $subscription->update(['status' => 'inactive']);

        $before = ServiceRequest::count();

        $this->actingAs($user)
            ->post(
                route('customer.tickets.store'),
                $this->technicalPayload($subscription->id)
            )
            ->assertSessionHasErrors('subscription_id');

        $this->assertSame($before, ServiceRequest::count());
    }

    public function test_issue_type_must_match_ticket_category(): void
    {
        [$user] = $this->fixture();

        $payload = array_merge(
            $this->nontechnicalPayload(),
            ['issue_type' => 'no_internet']
        );

        $before = ServiceRequest::count();

        $this->actingAs($user)
            ->post(route('customer.tickets.store'), $payload)
            ->assertSessionHasErrors('issue_type');

        $this->assertSame($before, ServiceRequest::count());
    }

    public function test_admin_and_staff_can_create_tickets_with_priority(): void
    {
        [, $customer] = $this->fixture();

        foreach (['admin', 'staff'] as $role) {
            $user = $this->activeUser($role);

            $payload = array_merge(
                $this->nontechnicalPayload(),
                [
                    'customer_id' => $customer->id,
                    'priority' => 'high',
                ]
            );

            $this->actingAs($user)
                ->post(route('admin.tickets.store'), $payload)
                ->assertSessionHasNoErrors();

            $ticket = ServiceRequest::query()->latest('id')->firstOrFail();

            $this->assertSame($customer->id, $ticket->customer_id);
            $this->assertSame('high', $ticket->priority);
            $this->assertSame('open', $ticket->status);
        }
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        [, $customer] = $this->fixture();
        [$otherUser] = $this->secondCustomer();

        $ticket = ServiceRequest::query()
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $this->actingAs($otherUser)
            ->get(route('customer.tickets.show', $ticket->id))
            ->assertNotFound();
    }

    public function test_customer_and_technician_cannot_access_staff_ticket_pages(): void
    {
        [$customerUser] = $this->fixture();

        $this->actingAs($customerUser)
            ->get(route('admin.tickets.index'))
            ->assertForbidden();

        $technician = $this->activeUser('technician');

        $this->actingAs($technician)
            ->get(route('admin.tickets.index'))
            ->assertForbidden();
    }

    public function test_ticket_creation_does_not_create_job_order(): void
    {
        [$user, , $subscription] = $this->fixture();

        $before = JobOrder::count();

        $this->actingAs($user)
            ->post(
                route('customer.tickets.store'),
                $this->technicalPayload($subscription->id)
            )
            ->assertSessionHasNoErrors();

        $this->assertSame($before, JobOrder::count());

        $ticket = ServiceRequest::query()->latest('id')->firstOrFail();

        $this->assertSame(0, $ticket->jobOrders()->count());
    }

    public function test_ticket_numbers_are_unique(): void
    {
        [$user] = $this->fixture();

        $this->actingAs($user)
            ->post(route('customer.tickets.store'), $this->nontechnicalPayload())
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('customer.tickets.store'), $this->nontechnicalPayload())
            ->assertSessionHasNoErrors();

        $tickets = ServiceRequest::query()
            ->latest('id')
            ->take(2)
            ->get();

        $this->assertCount(2, $tickets);
        $this->assertNotSame(
            $tickets[0]->ticket_number,
            $tickets[1]->ticket_number
        );

        foreach ($tickets as $ticket) {
            $this->assertSame(
                sprintf('TKT-%06d', $ticket->id),
                $ticket->ticket_number
            );
        }
    }
}