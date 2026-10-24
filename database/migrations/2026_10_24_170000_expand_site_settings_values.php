<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    public function down(): void
    {
        // Do not silently truncate saved template settings in production.
        if (DB::table('site_settings')->whereRaw('CHAR_LENGTH(value) > 255')->exists()) {
            throw new RuntimeException('Нельзя сузить site_settings.value: есть настройки длиннее 255 символов.');
        }
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('value', 255)->change();
        });
    }
};