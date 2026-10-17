<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('tagline', 160)->nullable();
            $table->string('instagram_username', 30)->nullable();
            $table->string('profile_theme', 20)->default('graphite');
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', fn (Blueprint $table) => $table->dropColumn(['avatar_path', 'cover_path', 'tagline', 'instagram_username', 'profile_theme']));
    }
};
