<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false);
        });
        if (app()->environment('production')) {
            app(\Database\Seeders\DemoMastersSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Profiles stay disabled if this display flag is rolled back.
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
