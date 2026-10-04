<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_order_proofs', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('job_order_id')
                ->constrained('job_orders')
                ->cascadeOnDelete();

            $table
                ->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'job_order_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_order_proofs');
    }
};
