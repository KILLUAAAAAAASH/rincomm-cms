<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('technicians')
                ->nullOnDelete();

            $table->string('job_order_number')->unique();

            $table->text('description')->nullable();

            $table->date('scheduled_date')->nullable();

            $table->time('scheduled_time')->nullable();

            $table->enum('status', [
                'pending',
                'assigned',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('materials_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};