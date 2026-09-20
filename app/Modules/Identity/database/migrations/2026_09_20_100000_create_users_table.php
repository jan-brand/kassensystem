<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 80)->unique();
            $table->string('pin_hash');

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('display_name', 150)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 50)->nullable();

            $table->string('role', 32)->index();
            $table->boolean('active')->default(true)->index();

            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('pin_changed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
