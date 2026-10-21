<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_change_requests', function (Blueprint $table): void {
            // Legacy approvals have NULL effective_on and remain historically applied.
            $table->date('effective_on')->nullable();
            $table->unsignedSmallInteger('approved_lock_in_months')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->index(['status', 'effective_on', 'applied_at'], 'plan_change_application_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('plan_change_requests', function (Blueprint $table): void {
            $table->dropIndex('plan_change_application_due_idx');
            $table->dropColumn(['effective_on', 'approved_lock_in_months', 'applied_at']);
        });
    }
};
