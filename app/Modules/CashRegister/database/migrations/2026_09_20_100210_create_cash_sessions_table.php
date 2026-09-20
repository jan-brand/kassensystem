<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_id')->constrained('registers')->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->string('status', 32)->index();
            $table->unsignedInteger('opening_cash_cents');
            $table->unsignedInteger('cash_sales_cents')->default(0);

            $table->unsignedInteger('closing_expected_cash_cents')->nullable();
            $table->unsignedInteger('closing_counted_cash_cents')->nullable();
            $table->integer('closing_difference_cents')->nullable();
            $table->text('closing_comment')->nullable();

            $table->timestamp('opened_at');
            $table->timestamp('closing_started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['register_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
