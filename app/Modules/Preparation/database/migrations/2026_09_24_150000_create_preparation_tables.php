<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparation_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 64)->unique();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('notification_sound', 24)->default('bell');
            $table->timestamps();
        });

        Schema::create('preparation_station_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('preparation_stations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['station_id', 'product_id']);
        });

        Schema::create('preparation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('preparation_stations')->restrictOnDelete();
            $table->foreignId('hospitality_order_id')->constrained('hospitality_orders')->restrictOnDelete();
            $table->foreignId('hospitality_order_item_id')->constrained('hospitality_order_items')->cascadeOnDelete();
            $table->foreignId('hospitality_order_item_component_id')->nullable()->constrained('hospitality_order_item_components')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('source_key', 80);
            $table->string('label_snapshot', 200);
            $table->string('component_label_snapshot', 240)->nullable();
            $table->string('note_snapshot', 500)->nullable();
            $table->unsignedInteger('quantity');
            $table->string('status', 32)->default('new')->index();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ready_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();

            $table->unique(['station_id', 'source_key'], 'preparation_tasks_station_source_unique');
            $table->index(['hospitality_order_id', 'status'], 'preparation_tasks_order_status_index');
            $table->index(['hospitality_order_item_id', 'status'], 'preparation_tasks_item_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparation_tasks');
        Schema::dropIfExists('preparation_station_products');
        Schema::dropIfExists('preparation_stations');
    }
};
