<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('subscription_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('current_service_plan_id')
                ->constrained('service_plans')
                ->restrictOnDelete();

            $table
                ->foreignId('requested_service_plan_id')
                ->constrained('service_plans')
                ->restrictOnDelete();

            $table
                ->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('request_type', [
                'upgrade',
                'downgrade',
            ]);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            $table->text('reason');
            $table->timestamps();

            $table->index([
                'customer_id',
                'status',
            ]);

            $table->index([
                'subscription_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_change_requests');
    }
};
