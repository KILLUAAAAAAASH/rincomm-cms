<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');

            $table->enum('request_type', [
                'technical',
                'non_technical'
            ])->default('technical')->after('service_plan_id');
        });

        DB::table('service_requests')
            ->where('status', 'pending')
            ->update(['status' => 'open']);

        DB::table('service_requests')
            ->where('status', 'approved')
            ->update(['status' => 'open']);

        DB::table('service_requests')
            ->where('status', 'in_progress')
            ->update(['status' => 'in_progress']);

        DB::table('service_requests')
            ->where('status', 'completed')
            ->update(['status' => 'resolved']);

        DB::table('service_requests')
            ->where('status', 'cancelled')
            ->update(['status' => 'closed']);

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

    public function down(): void
    {
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

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropUnique(['ticket_number']);
            $table->dropColumn([
                'ticket_number',
                'request_type'
            ]);
        });
    }
};