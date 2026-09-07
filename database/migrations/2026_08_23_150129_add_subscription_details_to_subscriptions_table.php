<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('lock_in_months')
                ->default(0)
                ->after('end_date');

            $table->decimal('discount_amount', 10, 2)
                ->default(0)
                ->after('lock_in_months');

            $table->boolean('is_custom_plan')
                ->default(false)
                ->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'lock_in_months',
                'discount_amount',
                'is_custom_plan'
            ]);
        });
    }
};