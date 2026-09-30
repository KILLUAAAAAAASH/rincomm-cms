<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('document_type', 100);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index([
                'customer_id',
                'document_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
