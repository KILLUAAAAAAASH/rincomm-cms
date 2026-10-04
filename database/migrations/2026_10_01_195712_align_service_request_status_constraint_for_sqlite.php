<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align SQLite's service request status CHECK constraint with the
     * ticketing workflow introduced by the earlier migration.
     *
     * MySQL is already updated by
     * 2026_08_23_152722_update_service_requests_for_ticketing.php.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->enum('status', [
                'open',
                'assigned',
                'in_progress',
                'resolved',
                'closed',
            ])
                ->default('open')
                ->change();
        });
    }

    /**
     * Restore the original SQLite status constraint when rolling back.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'approved',
                'assigned',
                'in_progress',
                'completed',
                'cancelled',
            ])
                ->default('pending')
                ->change();
        });
    }
};
