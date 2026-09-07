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
        Schema::create('service_applications', function (Blueprint $table) {
            $table->id();

            $table->string('application_number', 30)->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('service_area_id')
                ->constrained('service_areas')
                ->restrictOnDelete();

            $table->foreignId('service_plan_id')
                ->constrained('service_plans')
                ->restrictOnDelete();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->string('phone', 30);
            $table->string('email');

            $table->text('installation_address');
            $table->text('billing_address')->nullable();

            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('draft')->index();

            $table->text('rejection_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_applications');
    }
};