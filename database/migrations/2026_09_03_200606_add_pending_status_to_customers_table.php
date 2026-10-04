<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
            ])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            DB::table('customers')
            ->where('status', 'pending')
            ->exists()
        ) {
            throw new RuntimeException(
                'Cannot remove the pending customer status while pending customers exist.'
            );
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
                'terminated',
            ])
                ->default('active')
                ->change();
        });
    }
};
