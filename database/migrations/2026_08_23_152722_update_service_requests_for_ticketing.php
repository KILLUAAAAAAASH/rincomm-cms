<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('ticket_number')
                ->nullable()
                ->unique()
                ->after('id');

            $table->enum('request_type', [
                'technical',
                'non_technical',
            ])
                ->default('technical')
                ->after('service_plan_id');
        });

        /*
         * Normalize the legacy workflow states before narrowing the MySQL
         * ENUM definition.
         */
        DB::table('service_requests')
            ->where('status', 'pending')
            ->update(['status' => 'open']);

        DB::table('service_requests')
            ->where('status', 'approved')
            ->update(['status' => 'open']);

        DB::table('service_requests')
            ->where('status', 'completed')
            ->update(['status' => 'resolved']);

        DB::table('service_requests')
            ->where('status', 'cancelled')
            ->update(['status' => 'closed']);

        /*
         * MODIFY ... ENUM is MySQL-specific.
         *
         * SQLite is used only for the isolated automated test database.
         * Its schema does not need MySQL ENUM enforcement, so the portable
         * data migration above is sufficient there.
         */
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE service_requests
                MODIFY status ENUM(
                    'open',
                    'assigned',
                    'in_progress',
                    'resolved',
                    'closed'
                ) NOT NULL DEFAULT 'open'
            ");
        }
    }

    public function down(): void
    {
        /*
         * Restore the original MySQL ENUM before reversing the additional
         * ticketing columns.
         */
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE service_requests
                MODIFY status ENUM(
                    'pending',
                    'approved',
                    'assigned',
                    'in_progress',
                    'completed',
                    'cancelled'
                ) NOT NULL DEFAULT 'pending'
            ");
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropUnique(['ticket_number']);

            $table->dropColumn([
                'ticket_number',
                'request_type',
            ]);
        });
    }
};
