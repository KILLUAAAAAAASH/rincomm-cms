<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->text('reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('plan_change_requests')
            ->whereNull('reason')
            ->update(['reason' => 'Not provided']);

        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->text('reason')->nullable(false)->change();
        });
    }
};
