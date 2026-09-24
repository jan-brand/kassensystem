<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitality_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('hospitality_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('hospitality_areas')->restrictOnDelete();
            $table->string('name', 120);
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['area_id', 'name'], 'hospitality_tables_area_name_unique');
        });

        Schema::create('hospitality_order_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        Schema::create('hospitality_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->unsignedInteger('price_cents');
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('hospitality_menu_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('hospitality_menus')->cascadeOnDelete();
            $table->string('name', 160);
            $table->unsignedSmallInteger('min_choices')->default(1);
            $table->unsignedSmallInteger('max_choices')->default(1);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['menu_id', 'name'], 'hospitality_menu_groups_menu_name_unique');
        });

        Schema::create('hospitality_menu_group_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_group_id')->constrained('hospitality_menu_groups')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('price_delta_cents')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(
                ['menu_group_id', 'product_id'],
                'hospitality_menu_group_product_unique',
            );
        });

        Schema::create('hospitality_option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->unsignedSmallInteger('min_choices')->default(0);
            $table->unsignedSmallInteger('max_choices')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('hospitality_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('hospitality_option_groups')->cascadeOnDelete();
            $table->string('name', 160);
            $table->unsignedInteger('price_delta_cents')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(
                ['option_group_id', 'name'],
                'hospitality_option_values_group_name_unique',
            );
        });

        Schema::create('hospitality_option_group_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('hospitality_option_groups')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['option_group_id', 'product_id'],
                'hospitality_option_group_product_unique',
            );
        });

        Schema::create('hospitality_option_group_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('hospitality_option_groups')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['option_group_id', 'category_id'],
                'hospitality_option_group_category_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_option_group_categories');
        Schema::dropIfExists('hospitality_option_group_products');
        Schema::dropIfExists('hospitality_option_values');
        Schema::dropIfExists('hospitality_option_groups');
        Schema::dropIfExists('hospitality_menu_group_products');
        Schema::dropIfExists('hospitality_menu_groups');
        Schema::dropIfExists('hospitality_menus');
        Schema::dropIfExists('hospitality_order_number_sequences');
        Schema::dropIfExists('hospitality_tables');
        Schema::dropIfExists('hospitality_areas');
    }
};
