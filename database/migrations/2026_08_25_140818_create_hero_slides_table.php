<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();

            // Slide content
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Required slide image
            $table->string('image_path');
            $table->string('alt_text')->nullable();

            // Determines how much content is shown
            $table->enum('content_type', [
                'image_only',
                'image_text',
                'image_text_cta',
            ])->default('image_text');

            // Type of public announcement/content
            $table->enum('category', [
                'promotion',
                'event',
                'announcement',
                'coverage_update',
                'maintenance_advisory',
            ])->default('announcement');

            // Optional CTA
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();

            // Scheduling
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            // Carousel ordering
            $table->unsignedInteger('display_order')->default(0);

            // Publishing control
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            // Helpful indexes for public carousel queries
            $table->index('is_active');
            $table->index('display_order');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};