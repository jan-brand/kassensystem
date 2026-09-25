<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('sale_reversal_id')->nullable()->constrained('sale_reversals')->restrictOnDelete();
            $table->string('kind', 16);
            $table->char('token_hash', 64)->unique();
            $table->text('token_ciphertext');
            $table->string('merchant_name_snapshot', 160);
            $table->string('sale_number_snapshot', 32);
            $table->char('currency_snapshot', 3);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sale_id', 'kind']);
            $table->unique('sale_reversal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_receipts');
    }
};
