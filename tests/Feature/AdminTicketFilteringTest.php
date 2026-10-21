<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function prepareAdmin(): ServiceRequest
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->actingAs($admin);

        return ServiceRequest::query()->firstOrFail();
    }

    private function ticket(
        ServiceRequest $original,
        string $number,
        string $subject,
        string $status = 'open',
        string $category = 'technical',
        string $priority = 'normal'
    ): ServiceRequest {
        $ticket = $original->replicate();

        $ticket->ticket_number = $number;
        $ticket->subject = $subject;
        $ticket->status = $status;
        $ticket->request_type = $category;
        $ticket->priority = $priority;

        $ticket->save();

        return $ticket;
    }

    public function test_search_matches_ticket_subject_and_number(): void
    {
        $original = $this->prepareAdmin();

        $this->ticket(
            $original,
            'TKT-SEARCH-101',
            'Special fiber interruption'
        );

        $this->ticket(
            $original,
            'TKT-SEARCH-202',
            'Account clarification'
        );

        $this->get(route('admin.tickets.index', [
            'search' => 'fiber interruption',
        ]))
            ->assertOk()
            ->assertSee('TKT-SEARCH-101')
            ->assertDontSee('TKT-SEARCH-202');

        $this->get(route('admin.tickets.index', [
            'search' => 'TKT-SEARCH-202',
        ]))
            ->assertOk()
            ->assertSee('Account clarification')
            ->assertDontSee('TKT-SEARCH-101');
    }

    public function test_combined_status_and_category_filters(): void
    {
        $original = $this->prepareAdmin();

        $this->ticket(
            $original,
            'TKT-FILTER-101',
            'Closed billing concern',
            'closed',
            'non_technical'
        );

        $this->ticket(
            $original,
            'TKT-FILTER-202',
            'Open billing concern',
            'open',
            'non_technical'
        );

        $this->ticket(
            $original,
            'TKT-FILTER-303',
            'Closed technical concern',
            'closed',
            'technical'
        );

        $this->get(route('admin.tickets.index', [
            'status' => 'closed',
            'request_type' => 'non_technical',
        ]))
            ->assertOk()
            ->assertSee('TKT-FILTER-101')
            ->assertDontSee('TKT-FILTER-202')
            ->assertDontSee('TKT-FILTER-303')
            ->assertDontSee('TKT-0001');
    }

    public function test_priority_sorting_uses_operational_order(): void
    {
        $original = $this->prepareAdmin();

        $this->ticket(
            $original,
            'TKT-LOW-101',
            'Low priority case',
            'open',
            'technical',
            'low'
        );

        $this->ticket(
            $original,
            'TKT-HIGH-202',
            'High priority case',
            'open',
            'technical',
            'high'
        );

        $this->ticket(
            $original,
            'TKT-URGENT-303',
            'Urgent priority case',
            'open',
            'technical',
            'urgent'
        );

        $this->get(route('admin.tickets.index', [
            'sort' => 'priority_high',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TKT-URGENT-303',
                'TKT-HIGH-202',
                'TKT-0001',
                'TKT-LOW-101',
            ]);

        $this->get(route('admin.tickets.index', [
            'sort' => 'priority_low',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TKT-LOW-101',
                'TKT-0001',
                'TKT-HIGH-202',
                'TKT-URGENT-303',
            ]);
    }

    public function test_ticket_number_sorting_works_both_ways(): void
    {
        $original = $this->prepareAdmin();

        $this->ticket(
            $original,
            'TKT-0009',
            'Ninth reference'
        );

        $this->ticket(
            $original,
            'TKT-0003',
            'Third reference'
        );

        $this->get(route('admin.tickets.index', [
            'sort' => 'ticket_asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TKT-0001',
                'TKT-0003',
                'TKT-0009',
            ]);

        $this->get(route('admin.tickets.index', [
            'sort' => 'ticket_desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TKT-0009',
                'TKT-0003',
                'TKT-0001',
            ]);
    }

    public function test_invalid_sort_defaults_to_newest_first(): void
    {
        $original = $this->prepareAdmin();

        $this->ticket(
            $original,
            'TKT-SAFE-101',
            'First safe case'
        );

        $this->ticket(
            $original,
            'TKT-SAFE-202',
            'Second safe case'
        );

        $this->get(route('admin.tickets.index', [
            'sort' => 'invalid_sort_value',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'TKT-SAFE-202',
                'TKT-SAFE-101',
                'TKT-0001',
            ])
            ->assertSee('Newest first');
    }

    public function test_pagination_preserves_active_filters(): void
    {
        $original = $this->prepareAdmin();

        for ($number = 1; $number <= 16; $number++) {
            $this->ticket(
                $original,
                sprintf('TKT-PAGE-%04d', $number),
                'QueueCase billing ticket',
                'closed',
                'non_technical'
            );
        }

        $filters = [
            'search' => 'QueueCase',
            'status' => 'closed',
            'request_type' => 'non_technical',
            'sort' => 'oldest',
        ];

        $this->get(route('admin.tickets.index', $filters))
            ->assertOk()
            ->assertSee('TKT-PAGE-0001')
            ->assertDontSee('TKT-PAGE-0016')
            ->assertSee('page=2');

        $this->get(route('admin.tickets.index', array_merge(
            $filters,
            ['page' => 2]
        )))
            ->assertOk()
            ->assertSee('TKT-PAGE-0016')
            ->assertDontSee('TKT-PAGE-0001');
    }
}