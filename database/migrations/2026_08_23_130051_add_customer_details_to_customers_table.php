<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->after('customer_code');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');

            $table->string('phone')->nullable()->after('last_name');
            $table->string('email')->nullable()->after('phone');

            $table->string('billing_address')->nullable()->after('postal_code');
            $table->string('installation_address')->nullable()->after('billing_address');

            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
                'terminated'
            ])->default('active')->after('installation_address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'phone',
                'email',
                'billing_address',
                'installation_address',
                'status'
            ]);
        });
    }
};