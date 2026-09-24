<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->unsignedInteger('cash_refunds_cents')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->dropColumn('cash_refunds_cents');
        });
    }
};
