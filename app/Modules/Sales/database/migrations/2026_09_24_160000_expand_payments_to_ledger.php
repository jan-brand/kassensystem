<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep a non-unique index in place before removing the v1 UNIQUE index.
        // MySQL/MariaDB need an index for the existing sale_id foreign key.
        Schema::table('payments', function (Blueprint $table): void {
            $table->index('sale_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['sale_id']);
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('provider_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['confirmed_by_user_id']);
            $table->dropColumn(['confirmed_by_user_id', 'provider_reference']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('sale_id');
            $table->dropIndex(['sale_id']);
        });
    }
};
