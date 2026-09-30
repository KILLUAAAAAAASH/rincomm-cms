<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relocation_requests', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('subscription_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('requested_service_area_id')
                ->constrained('service_areas')
                ->restrictOnDelete();

            $table
                ->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('current_installation_address');

            $table->string('requested_installation_address');

            /*
             * Keep a location snapshot so historical relocation records
             * remain understandable even if the service area is edited later.
             */
            $table->string('requested_province');
            $table->string('requested_city_municipality');
            $table->string('requested_barangay');
            $table->string('requested_postal_code', 10)->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'completed',
            ])->default('pending');

            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index([
                'customer_id',
                'status',
            ]);

            $table->index([
                'subscription_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relocation_requests');
    }
};
