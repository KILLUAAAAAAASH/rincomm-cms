<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_proof_submissions', function (Blueprint $table): void {
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_proof_submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn(['reviewed_at', 'review_notes']);
        });
    }
};
