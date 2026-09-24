<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitality_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('table_id')->constrained('hospitality_tables')->restrictOnDelete();
            $table->foreignId('open_table_id')
                ->nullable()
                ->constrained('hospitality_tables')
                ->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->index();
            $table->string('note', 500)->nullable();
            $table->unsignedInteger('total_cents')->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique('open_table_id', 'hospitality_orders_one_open_per_table');
        });

        Schema::create('hospitality_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('hospitality_orders')->cascadeOnDelete();
            $table->string('kind', 24);
            $table->foreignId('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('hospitality_menus')->restrictOnDelete();
            $table->string('label', 160);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total_cents');
            $table->boolean('is_consumable');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'kind'], 'hospitality_order_items_order_kind_index');
        });

        Schema::create('hospitality_order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('hospitality_order_items')->cascadeOnDelete();
            $table->string('option_group_name', 160);
            $table->string('option_value_name', 160);
            $table->unsignedInteger('price_delta_cents')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hospitality_order_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('hospitality_order_items')->cascadeOnDelete();
            $table->string('menu_group_name', 160);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 160);
            $table->boolean('product_is_consumable');
            $table->unsignedInteger('price_delta_cents')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_order_item_components');
        Schema::dropIfExists('hospitality_order_item_options');
        Schema::dropIfExists('hospitality_order_items');
        Schema::dropIfExists('hospitality_orders');
    }
};
