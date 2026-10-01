<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             * Employee number is only assigned to internal Rincomm accounts:
             * Administrator, Staff, and Technician.
             *
             * Customer accounts keep this value null.
             */
            $table->string('employee_number', 30)
                ->nullable()
                ->unique();

            /*
             * Account-level mobile number used specifically for verified
             * authentication, OTP delivery, and account recovery.
             *
             * This is intentionally separate from Customer or
             * ServiceApplication contact information.
             */
            $table->string('phone', 20)
                ->nullable()
                ->unique();

            /*
             * Records when the account-level mobile number was successfully
             * verified through the OTP process.
             */
            $table->timestamp('phone_verified_at')
                ->nullable();

            /*
             * Records completion of the first-time activation process for
             * internally created Administrator, Staff, and Technician accounts.
             *
             * Existing customer accounts do not require this field.
             */
            $table->timestamp('activation_completed_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropUnique(['phone']);

            $table->dropColumn([
                'employee_number',
                'phone',
                'phone_verified_at',
                'activation_completed_at',
            ]);
        });
    }
};
