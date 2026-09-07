<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('service_plan_id')
                ->constrained('service_plans')
                ->cascadeOnDelete();

            $table->text('description')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'assigned',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->date('requested_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};