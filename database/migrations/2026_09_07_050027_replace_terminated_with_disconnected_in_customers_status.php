<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Temporarily allow both values so existing data can be converted safely.
        DB::statement("
            ALTER TABLE customers
            MODIFY status ENUM(
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
                'disconnected'
            ) NOT NULL DEFAULT 'pending'
        ");

        DB::table('customers')
            ->where('status', 'terminated')
            ->update([
                'status' => 'disconnected',
            ]);

        DB::statement("
            ALTER TABLE customers
            MODIFY status ENUM(
                'pending',
                'active',
                'inactive',
                'suspended',
                'disconnected'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // Temporarily allow both values for a safe rollback.
        DB::statement("
            ALTER TABLE customers
            MODIFY status ENUM(
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
                'disconnected'
            ) NOT NULL DEFAULT 'pending'
        ");

        DB::table('customers')
            ->where('status', 'disconnected')
            ->update([
                'status' => 'terminated',
            ]);

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
};
