<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('billed_service_plan_id')
                ->nullable();

            $table->string('billed_service_plan_name')
                ->nullable();

            $table->unsignedInteger('billed_speed_mbps')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'billed_service_plan_id',
                'billed_service_plan_name',
                'billed_speed_mbps',
            ]);
        });
    }
};
