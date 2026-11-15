<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->boolean('onboarding_telegram_required')->default(false);
        });
    }
    public function down(): void {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('onboarding_telegram_required');
        });
    }
};
