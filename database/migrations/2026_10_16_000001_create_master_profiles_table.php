<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('display_name');
            $table->string('specialty')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('timezone', 64)->default('Asia/Bishkek');
            $table->boolean('booking_enabled')->default(true);
            $table->boolean('auto_confirm')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profiles');
    }
};
