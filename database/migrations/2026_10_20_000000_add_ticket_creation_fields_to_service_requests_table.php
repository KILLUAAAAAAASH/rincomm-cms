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
            $table->string('subject', 160)
                ->nullable()
                ->after('ticket_number');

            $table->string('issue_type', 50)
                ->nullable()
                ->after('request_type');

            $table->string('priority', 16)
                ->default('normal')
                ->after('issue_type');

            $table->foreignId('subscription_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('service_plan_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        if (
            DB::table('service_requests')
                ->whereNull('service_plan_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back ticket fields: tickets without service plans exist.'
            );
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');

            $table->dropColumn([
                'subject',
                'issue_type',
                'priority',
            ]);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('service_plan_id')
                ->nullable(false)
                ->change();
        });
    }
};