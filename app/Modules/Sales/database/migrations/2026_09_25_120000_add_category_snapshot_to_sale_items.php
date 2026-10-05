<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->string('category_name', 160)->nullable();
        });

        $items = DB::table('sale_items as items')
            ->join('products', 'products.id', '=', 'items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'items.id',
                'categories.name as category_name',
            ])
            ->get();

        foreach ($items as $item) {
            DB::table('sale_items')
                ->where('id', $item->id)
                ->update(['category_name' => $item->category_name]);
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn('category_name');
        });
    }
};
