<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('type', 32);
            $table->unsignedInteger('value');
            $table->boolean('active')->default(true)->index();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('weekdays')->nullable();
            $table->string('daily_start_time', 5)->nullable();
            $table->string('daily_end_time', 5)->nullable();
            $table->timestamps();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('original_unit_price_cents')->nullable();
            $table->string('discount_type', 32)->nullable();
            $table->unsignedInteger('discount_value')->nullable();
            $table->string('discount_source', 16)->nullable();
            $table->string('discount_label', 160)->nullable();
            $table->foreignId('discount_offer_id')
                ->nullable()
                ->constrained('discount_offers')
                ->nullOnDelete();
        });

        DB::table('sale_items')
            ->whereNull('original_unit_price_cents')
            ->update(['original_unit_price_cents' => DB::raw('unit_price_cents')]);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_offer_id');
            $table->dropColumn([
                'original_unit_price_cents',
                'discount_type',
                'discount_value',
                'discount_source',
                'discount_label',
            ]);
        });

        Schema::dropIfExists('discount_offers');
    }
};
