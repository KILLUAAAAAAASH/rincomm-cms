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
        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('requested_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('review_notes')
                ->nullable()
                ->after('reason');

            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('review_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);

            $table->dropColumn([
                'reviewed_by',
                'review_notes',
                'reviewed_at',
            ]);
        });
    }
};
