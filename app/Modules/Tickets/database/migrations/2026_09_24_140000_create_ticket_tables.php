<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->string('status', 24)->index();
            $table->string('funding_type', 16)->index();
            $table->date('valid_on')->index();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->restrictOnDelete();
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_order_id')->nullable()->constrained('hospitality_orders')->restrictOnDelete();
            $table->foreignId('assigned_table_id')->nullable()->constrained('hospitality_tables')->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_order_id', 'status'], 'tickets_order_status_index');
        });

        Schema::create('ticket_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $table->foreignId('menu_id')->constrained('hospitality_menus')->restrictOnDelete();
            $table->string('menu_name_snapshot', 160);
            $table->unsignedSmallInteger('quantity_allowed');
            $table->unsignedSmallInteger('quantity_redeemed')->default(0);
            $table->timestamps();

            $table->unique(['ticket_id', 'menu_id'], 'ticket_entitlements_ticket_menu_unique');
        });

        Schema::create('ticket_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $table->foreignId('ticket_entitlement_id')->constrained('ticket_entitlements')->restrictOnDelete();
            $table->foreignId('hospitality_order_id')->constrained('hospitality_orders')->restrictOnDelete();
            $table->foreignId('hospitality_order_item_id')->constrained('hospitality_order_items')->restrictOnDelete();
            $table->foreignId('redeemed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('covered_value_cents');
            $table->timestamp('redeemed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ticket_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_redemptions');
        Schema::dropIfExists('ticket_entitlements');
        Schema::dropIfExists('tickets');
    }
};
