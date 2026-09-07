<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServicePlan;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {

           // Demo administrator user
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@rincomm.test'],
            [
                'name' => 'Rincomm Administrator',
                'password' => Hash::make('password'),
            ]
        );

        $adminUser->role = 'admin';
        $adminUser->account_status = 'active';
        $adminUser->save();

        // Demo staff user
        $staffUser = User::updateOrCreate(
            ['email' => 'staff@rincomm.test'],
            [
                'name' => 'Rincomm Staff',
                'password' => Hash::make('password'),
            ]
        );

        $staffUser->role = 'staff';
        $staffUser->account_status = 'active';
        $staffUser->save();

// Demo customer user
$customerUser = User::updateOrCreate(
    ['email' => 'customer@rincomm.test'],
    [
        'name' => 'Juan Dela Cruz',
        'password' => Hash::make('password'),
    ]
);

$customerUser->role = 'customer';
$customerUser->account_status = 'active';
$customerUser->save();

        // Demo customer
        $customer = Customer::updateOrCreate(
            ['customer_code' => 'CUST-0001'],
            [
                'user_id' => $customerUser->id,
                'first_name' => 'Juan',
                'middle_name' => null,
                'last_name' => 'Dela Cruz',
                'phone' => '09123456789',
                'email' => 'customer@rincomm.test',
                'address' => 'Paniqui',
                'city' => 'Paniqui',
                'province' => 'Tarlac',
                'postal_code' => '2307',
                'billing_address' => 'Paniqui, Tarlac',
                'installation_address' => 'Paniqui, Tarlac',
                'status' => 'active',
            ]
        );

        // Demo internet plan
        $servicePlan = ServicePlan::updateOrCreate(
            ['name' => 'Fiber 100'],
            [
                'description' => '100 Mbps residential internet plan',
                'speed_mbps' => 100,
                'monthly_fee' => 1499,
                'is_custom' => false,
                'duration_months' => 12,
                'is_active' => true,
            ]
        );

        // Demo active subscription
        Subscription::updateOrCreate(
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

// Demo technician user
$technicianUser = User::updateOrCreate(
    ['email' => 'technician@rincomm.test'],
    [
        'name' => 'Pedro Santos',
        'password' => Hash::make('password'),
    ]
);

$technicianUser->role = 'technician';
$technicianUser->account_status = 'active';
$technicianUser->save();

        // Demo technician
        $technician = Technician::updateOrCreate(
            ['technician_code' => 'TECH-0001'],
            [
                'user_id' => $technicianUser->id,
                'specialization' => 'Fiber Installation and Repair',
                'status' => 'available',
            ]
        );

        // Demo open ticket
        $ticket = ServiceRequest::updateOrCreate(
            ['ticket_number' => 'TKT-0001'],
            [
                'customer_id' => $customer->id,
                'service_plan_id' => $servicePlan->id,
                'request_type' => 'technical',
                'description' => 'Customer reports intermittent internet connection.',
                'status' => 'open',
                'requested_date' => now()->toDateString(),
            ]
        );

        // Demo pending job order
        JobOrder::updateOrCreate(
            ['job_order_number' => 'JO-0001'],
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
}