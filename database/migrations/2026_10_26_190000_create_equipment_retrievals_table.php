<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_retrievals', function (Blueprint $table): void {
            $table->id();
            // One confirmed physical return per individual customer assignment.
            $table->foreignId('equipment_assignment_id')->unique()
                ->constrained('equipment_assignments')->restrictOnDelete();
            $table->foreignId('job_order_id')->nullable()->constrained('job_orders')->restrictOnDelete();
            $table->foreignId('retrieved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('retrieved_on');
            $table->string('reason', 32);
            $table->string('condition', 32);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index('retrieved_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_retrievals');
    }
};
