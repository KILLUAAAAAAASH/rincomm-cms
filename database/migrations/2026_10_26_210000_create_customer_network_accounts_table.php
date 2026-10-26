<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_network_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();
            $table->string('router_key', 64)->default('primary');
            $table->string('pppoe_username', 120);
            $table->timestamps();

            // Only one login per customer per router; a login cannot identify two customers.
            $table->unique(['customer_id', 'router_key'], 'customer_network_accounts_customer_router_unique');
            $table->unique(['router_key', 'pppoe_username'], 'customer_network_accounts_router_username_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_network_accounts');
    }
};
