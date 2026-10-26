<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_tag', 40)->unique();
            $table->string('name', 120);
            $table->string('category', 40)->index();
            $table->string('brand', 80)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 120)->nullable()->unique();
            $table->string('status', 32)->default('in_stock')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assets');
    }
};
