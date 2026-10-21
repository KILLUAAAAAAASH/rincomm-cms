<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_billing_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->restrictOnDelete();

            $table->foreignId('service_plan_id')
                ->nullable()
                ->constrained('service_plans')
                ->nullOnDelete();

            $table->foreignId('plan_change_request_id')
                ->nullable()
                ->constrained('plan_change_requests')
                ->nullOnDelete();

            $table->string('billed_service_plan_name');

            $table->decimal('billed_speed_mbps', 10, 2);

            $table->decimal('monthly_fee', 10, 2);

            $table->decimal('discount_amount', 10, 2)
                ->default(0);

            $table->date('effective_billing_date');

            $table->string('source', 40);

            $table->timestamps();

            $table->index(
                ['subscription_id', 'effective_billing_date'],
                'subscription_billing_rates_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_billing_rates');
    }
};
