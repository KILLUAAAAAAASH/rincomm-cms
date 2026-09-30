<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();

            $table->string('setting_key')
                ->default('default')
                ->unique();

            $table->unsignedTinyInteger('billing_cycle_months')
                ->default(1);

            $table->unsignedSmallInteger('disconnection_notice_days')
                ->default(5);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
