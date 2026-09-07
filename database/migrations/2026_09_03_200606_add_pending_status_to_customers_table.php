<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE customers
            MODIFY status ENUM(
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            DB::table('customers')
            ->where('status', 'pending')
            ->exists()
        ) {
            throw new RuntimeException(
                'Cannot remove the pending customer status while pending customers exist.'
            );
        }

        DB::statement("
            ALTER TABLE customers
            MODIFY status ENUM(
                'active',
                'inactive',
                'suspended',
                'terminated'
            ) NOT NULL DEFAULT 'active'
        ");
    }
};
