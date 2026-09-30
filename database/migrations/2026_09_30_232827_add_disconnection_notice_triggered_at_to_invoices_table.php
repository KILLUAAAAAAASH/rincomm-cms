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
        Schema::table('invoices', function (Blueprint $table) {
            /*
             * Records when Module 4 recognizes that the disconnection
             * notice has become due.
             *
             * This does NOT mean an email or SMS was delivered.
             * Communication delivery will be handled separately by
             * the Communication & Notification module.
             */
            $table->timestamp('disconnection_notice_triggered_at')
                ->nullable()
                ->after('disconnection_notice_date');

            /*
             * Supports the scheduled lookup for invoices whose
             * disconnection notices are due but have not yet been
             * triggered.
             */
            $table->index(
                [
                    'status',
                    'disconnection_notice_date',
                    'disconnection_notice_triggered_at',
                ],
                'invoices_disconnection_notice_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(
                'invoices_disconnection_notice_lookup_index'
            );

            $table->dropColumn(
                'disconnection_notice_triggered_at'
            );
        });
    }
};