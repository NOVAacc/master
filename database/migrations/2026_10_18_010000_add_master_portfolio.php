<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->string('work1_path')->nullable();
            $table->string('work2_path')->nullable();
            $table->string('work3_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn(['work1_path', 'work2_path', 'work3_path']);
        });
    }
};
