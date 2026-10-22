<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->string('story_style', 20)->default('minimal');
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('story_style');
        });
    }
};
