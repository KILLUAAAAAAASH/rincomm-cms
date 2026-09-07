<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->string('payment_reference')->unique();

            $table->decimal('amount', 10, 2);

            $table->enum('payment_method', [
                'cash',
                'gcash',
                'bank_transfer',
                'other'
            ]);

            $table->enum('payment_status', [
                'pending',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');

            $table->dateTime('paid_at')->nullable();

            $table->string('gateway_reference')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};