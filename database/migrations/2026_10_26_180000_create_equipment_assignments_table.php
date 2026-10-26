<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_asset_id')->constrained('equipment_assets')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            // Kept nullable for Feature 3's retrieval event and assignment history.
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['equipment_asset_id', 'released_at'], 'equipment_assignments_asset_active_idx');
            $table->index(['customer_id', 'released_at'], 'equipment_assignments_customer_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assignments');
    }
};
