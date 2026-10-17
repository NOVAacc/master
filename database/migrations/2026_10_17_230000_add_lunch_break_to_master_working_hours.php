<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('master_working_hours', function (Blueprint $table) {
            $table->time('break_starts_at')->nullable();
            $table->time('break_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_working_hours', function (Blueprint $table) {
            $table->dropColumn(['break_starts_at', 'break_ends_at']);
        });
    }
};
