<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained('sales')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->restrictOnDelete();
            $table->string('payment_method', 32)->nullable();
            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('cash_refund_cents')->default(0);
            $table->string('reason', 500);
            $table->timestamp('reversed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('reversed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_reversals');
    }
};
