<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_staff_employee_account(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@example.test',
                'phone' => '09171234567',
                'role' => 'staff',
            ]);

        $employee = User::query()
            ->where('email', 'maria.santos@example.test')
            ->firstOrFail();

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHas('success')
            ->assertSessionHas(
                'created_employee.employee_number',
                sprintf('EMP-%04d', $employee->id)
            )
            ->assertSessionHas(
                'created_employee.default_password',
                '12345678'
            );

        $this->assertSame('staff', $employee->role);
        $this->assertSame('pending_verification', $employee->account_status);
        $this->assertSame(
            sprintf('EMP-%04d', $employee->id),
            $employee->employee_number
        );

        $this->assertNull($employee->phone_verified_at);
        $this->assertNull($employee->activation_completed_at);

        $this->assertTrue(
            Hash::check('12345678', $employee->password)
        );

        $this->assertDatabaseMissing('technicians', [
            'user_id' => $employee->id,
        ]);
    }

    public function test_admin_can_create_technician_employee_account(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Pedro Santos',
                'email' => 'pedro.santos@example.test',
                'phone' => '09181234567',
                'role' => 'technician',
                'specialization' => 'Fiber Installation and Repair',
            ]);

        $employee = User::query()
            ->where('email', 'pedro.santos@example.test')
            ->firstOrFail();

        $technician = Technician::query()
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $expectedEmployeeNumber = sprintf(
            'EMP-%04d',
            $employee->id
        );

        $expectedTechnicianCode = sprintf(
            'TECH-%04d',
            $employee->id
        );

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHas('success')
            ->assertSessionHas(
                'created_employee.employee_number',
                $expectedEmployeeNumber
            )
            ->assertSessionHas(
                'created_employee.technician_code',
                $expectedTechnicianCode
            )
            ->assertSessionHas(
                'created_employee.default_password',
                '12345678'
            );

        $this->assertSame('technician', $employee->role);
        $this->assertSame(
            'pending_verification',
            $employee->account_status
        );
        $this->assertSame(
            $expectedEmployeeNumber,
            $employee->employee_number
        );

        $this->assertSame(
            $expectedTechnicianCode,
            $technician->technician_code
        );
        $this->assertSame(
            'Fiber Installation and Repair',
            $technician->specialization
        );
        $this->assertSame(
            'available',
            $technician->status
        );

        $this->assertNull($employee->phone_verified_at);
        $this->assertNull($employee->activation_completed_at);

        $this->assertTrue(
            Hash::check('12345678', $employee->password)
        );
    }

    public function test_staff_cannot_access_or_submit_employee_creation(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.test',
            'password' => 'password',
        ]);

        $staff->forceFill([
            'role' => 'staff',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'activation_completed_at' => now(),
        ])->save();

        $this
            ->actingAs($staff)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this
            ->actingAs($staff)
            ->post(route('admin.users.store'), [
                'name' => 'Unauthorized Employee',
                'email' => 'unauthorized@example.test',
                'phone' => '09191234567',
                'role' => 'staff',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'unauthorized@example.test',
        ]);
    }

    public function test_admin_cannot_create_employee_with_unsupported_role(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Invalid Employee',
                'email' => 'invalid.role@example.test',
                'phone' => '09201234567',
                'role' => 'admin',
            ]);

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid.role@example.test',
        ]);
    }

    public function test_admin_cannot_create_employee_with_duplicate_email(): void
    {
        $admin = $this->createAdmin();

        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate Email',
                'email' => 'existing@example.test',
                'phone' => '09211234567',
                'role' => 'staff',
            ]);

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::query()
                ->where('email', 'existing@example.test')
                ->count()
        );
    }

    public function test_admin_cannot_create_employee_with_duplicate_mobile_number(): void
    {
        $admin = $this->createAdmin();

        $existing = User::factory()->create([
            'email' => 'existing.phone@example.test',
        ]);

        $existing->forceFill([
            'phone' => '639221234567',
        ])->save();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate Mobile',
                'email' => 'new.employee@example.test',
                'phone' => '09221234567',
                'role' => 'staff',
            ]);

        $response
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', [
            'email' => 'new.employee@example.test',
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Rincomm Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
            'email_verified_at' => now(),
            'activation_completed_at' => now(),
        ])->save();

        return $admin;
    }
}
