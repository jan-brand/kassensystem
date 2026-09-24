<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitality_orders', function (Blueprint $table) {
            $table->string('area_name_snapshot', 120)->nullable()->after('open_table_id');
            $table->string('table_name_snapshot', 120)->nullable()->after('area_name_snapshot');
        });

        $orders = DB::table('hospitality_orders as orders')
            ->join('hospitality_tables as tables', 'tables.id', '=', 'orders.table_id')
            ->join('hospitality_areas as areas', 'areas.id', '=', 'tables.area_id')
            ->select([
                'orders.id',
                'tables.name as table_name',
                'areas.name as area_name',
            ])
            ->get();

        foreach ($orders as $order) {
            DB::table('hospitality_orders')
                ->where('id', $order->id)
                ->update([
                    'area_name_snapshot' => $order->area_name,
                    'table_name_snapshot' => $order->table_name,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('hospitality_orders', function (Blueprint $table) {
            $table->dropColumn(['area_name_snapshot', 'table_name_snapshot']);
        });
    }
};
