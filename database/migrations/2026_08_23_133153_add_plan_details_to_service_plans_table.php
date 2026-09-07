<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->decimal('speed_mbps', 10, 2)->after('description');
            $table->decimal('monthly_fee', 10, 2)->after('speed_mbps');
            $table->boolean('is_custom')->default(false)->after('monthly_fee');
        });
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn([
                'speed_mbps',
                'monthly_fee',
                'is_custom'
            ]);
        });
    }
};