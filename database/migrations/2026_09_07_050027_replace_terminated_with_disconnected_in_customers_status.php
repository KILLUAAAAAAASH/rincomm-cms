<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Temporarily allow both legacy and replacement values so existing
         * customer records can be converted without violating the status
         * constraint.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
                'disconnected',
            ])
                ->default('pending')
                ->change();
        });

        DB::table('customers')
            ->where('status', 'terminated')
            ->update([
                'status' => 'disconnected',
            ]);

        /*
         * Remove the legacy terminated value after all existing records have
         * been migrated to disconnected.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'disconnected',
            ])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * Temporarily restore both values so disconnected records can be
         * converted safely back to the legacy terminated status.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
                'disconnected',
            ])
                ->default('pending')
                ->change();
        });

        DB::table('customers')
            ->where('status', 'disconnected')
            ->update([
                'status' => 'terminated',
            ]);

        /*
         * Restore the previous status definition after the rollback data
         * conversion is complete.
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
            ])
                ->default('pending')
                ->change();
        });
    }
};
