<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('previous_status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'disconnected',
            ]);

            $table->enum('new_status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'disconnected',
            ]);

            $table->text('reason');

            $table->timestamps();

            $table->index([
                'customer_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_status_histories');
    }
};
