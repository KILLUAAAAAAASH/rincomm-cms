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
        Schema::table('job_orders', function (Blueprint $table) {
            $table->enum('job_type', [
                'new_installation',
                'site_survey',
                'repair',
                'line_maintenance',
                'physical_disconnection',
            ])
                ->nullable()
                ->after('job_order_number');

            $table->timestamp('started_at')
                ->nullable()
                ->after('status');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('started_at');

            $table->text('completion_report')
                ->nullable()
                ->after('completed_at');

            $table->index([
                'job_type',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex([
                'job_type',
                'status',
            ]);

            $table->dropColumn([
                'job_type',
                'started_at',
                'completed_at',
                'completion_report',
            ]);
        });
    }
};
