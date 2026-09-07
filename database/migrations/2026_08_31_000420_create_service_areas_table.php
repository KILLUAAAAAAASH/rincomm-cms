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
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();

            $table->string('province');
            $table->string('city_municipality');
            $table->string('barangay');
            $table->string('postal_code', 10)->nullable();

            $table->boolean('is_serviceable')
                ->default(true)
                ->index();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['province', 'city_municipality', 'barangay'],
                'service_areas_location_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};