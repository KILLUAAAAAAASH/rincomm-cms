<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceArea;
use App\Models\ServicePlan;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a controlled Rincomm development/demo dataset.
     *
     * Existing records are preserved. This seeder creates missing demo
     * records but does not reset operational statuses on every run.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DemoDataSeeder must not be executed in production.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Administrator
        |--------------------------------------------------------------------------
        */

        $this->createDemoUser(
            name: 'Rincomm Administrator',
            email: 'admin@rincomm.test',
            role: 'admin'
        );

        /*
        |--------------------------------------------------------------------------
        | Staff
        |--------------------------------------------------------------------------
        */

        $this->createDemoUser(
            name: 'Rincomm Staff',
            email: 'staff@rincomm.test',
            role: 'staff'
        );

        /*
        |--------------------------------------------------------------------------
        | Confirmed Rincomm Service Area
        |--------------------------------------------------------------------------
        |
        | This record restores the Week 3 coverage-check foundation.
        | These values were recovered from the original confirmed development
        | command used to create the Acocolao service-area record.
        |
        */

        ServiceArea::firstOrCreate(
            [
                'province' => 'Tarlac',
                'city_municipality' => 'Paniqui',
                'barangay' => 'Acocolao',
            ],
            [
                'postal_code' => '2307',
                'latitude' => 15.6591,
                'longitude' => 120.5634,
                'is_serviceable' => true,
                'notes' => 'Confirmed Rincomm service area',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Customer Account
        |--------------------------------------------------------------------------
        */

        $customerUser = $this->createDemoUser(
            name: 'Miguel Santos',
            email: 'miguel.santos@rincomm.test',
            role: 'customer'
        );

        $customer = Customer::firstOrCreate(
            [
                'customer_code' => 'CUST-0001',
            ],
            [
                'user_id' => $customerUser->id,
                'first_name' => 'Miguel',
                'middle_name' => null,
                'last_name' => 'Santos',
                'phone' => '09123456789',
                'email' => 'miguel.santos@rincomm.test',
                'address' => 'Paniqui',
                'city' => 'Paniqui',
                'province' => 'Tarlac',
                'postal_code' => '2307',
                'billing_address' => 'Paniqui, Tarlac',
                'installation_address' => 'Paniqui, Tarlac',
                'status' => 'active',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Internet Plan
        |--------------------------------------------------------------------------
        */

        $servicePlan = ServicePlan::firstOrCreate(
            [
                'name' => 'Fiber 100',
            ],
            [
                'description' => '100 Mbps residential internet plan',
                'speed_mbps' => 100,
                'monthly_fee' => 1499,
                'is_custom' => false,
                'duration_months' => 12,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Subscription
        |--------------------------------------------------------------------------
        */

        Subscription::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'service_plan_id' => $servicePlan->id,
            ],
            [
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(12)->toDateString(),
                'lock_in_months' => 12,
                'discount_amount' => 0,
                'is_custom_plan' => false,
                'status' => 'active',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Technician
        |--------------------------------------------------------------------------
        */

        $technicianUser = $this->createDemoUser(
            name: 'Pedro Santos',
            email: 'technician@rincomm.test',
            role: 'technician'
        );

        $technician = Technician::firstOrCreate(
            [
                'technician_code' => 'TECH-0001',
            ],
            [
                'user_id' => $technicianUser->id,
                'specialization' => 'Fiber Installation and Repair',
                'status' => 'available',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Ticket
        |--------------------------------------------------------------------------
        */

        $ticket = ServiceRequest::firstOrCreate(
            [
                'ticket_number' => 'TKT-0001',
            ],
            [
                'customer_id' => $customer->id,
                'service_plan_id' => $servicePlan->id,
                'request_type' => 'technical',
                'description' => 'Customer reports intermittent internet connection.',
                'status' => 'open',
                'requested_date' => now()->toDateString(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Job Order
        |--------------------------------------------------------------------------
        */

        JobOrder::firstOrCreate(
            [
                'job_order_number' => 'JO-0001',
            ],
            [
                'service_request_id' => $ticket->id,
                'customer_id' => $customer->id,
                'technician_id' => $technician->id,
                'description' => 'Inspect customer fiber connection.',
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '09:00:00',
                'status' => 'pending',
                'labor_cost' => 0,
                'materials_cost' => 0,
                'total_cost' => 0,
                'remarks' => 'Demo job order',
            ]
        );
    }

    /**
     * Create a development-only login fixture.
     *
     * Existing users are intentionally left unchanged so reseeding cannot
     * reset passwords, account statuses, roles, or verification state.
     */
    private function createDemoUser(
        string $name,
        string $email,
        string $role
    ): User {
        $user = User::firstOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill([
                'role' => $role,
                'account_status' => 'active',
                'email_verified_at' => now(),
            ])->save();
        }

        return $user;
    }
}
