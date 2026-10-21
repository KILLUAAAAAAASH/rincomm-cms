<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('billing_anchor_date')
                ->nullable()
                ->after('start_date');
        });

        // Preserve the original billing schedule for existing subscriptions.
        DB::table('subscriptions')
            ->whereNotNull('start_date')
            ->update([
                'billing_anchor_date' => DB::raw('start_date'),
            ]);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_anchor_date');
        });
    }
};
