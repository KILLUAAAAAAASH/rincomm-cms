<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('activation_job_order_id')
                ->nullable()
                ->after('is_custom_plan')
                ->constrained('job_orders')
                ->restrictOnDelete();

            $table->unique(
                'activation_job_order_id',
                'subscriptions_activation_job_order_unique'
            );

            // NULL identifies a legacy subscription activated before this workflow.
            $table->string('activation_mode', 16)
                ->nullable()
                ->after('activation_job_order_id');

            $table->foreignId('activated_by')
                ->nullable()
                ->after('activation_mode')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('activated_at')
                ->nullable()
                ->after('activated_by');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique('subscriptions_activation_job_order_unique');
            $table->dropConstrainedForeignId('activation_job_order_id');
            $table->dropConstrainedForeignId('activated_by');
            $table->dropColumn(['activation_mode', 'activated_at']);
        });
    }
};
