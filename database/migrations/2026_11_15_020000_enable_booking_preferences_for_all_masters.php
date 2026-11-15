<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // New profiles start with all three booking preferences enabled.
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->boolean('booking_enabled')->default(true)->change();
            $table->boolean('show_busy_slots')->default(true)->change();
            $table->boolean('auto_confirm')->default(true)->change();
        });

        // Apply the requested settings to every existing master exactly once.
        // Masters can still turn any preference off afterwards.
        DB::table('master_profiles')->update([
            'booking_enabled' => true,
            'show_busy_slots' => true,
            'auto_confirm' => true,
        ]);
    }

    public function down(): void
    {
        // Do not overwrite masters' subsequently saved preferences on rollback.
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->boolean('show_busy_slots')->default(false)->change();
            $table->boolean('auto_confirm')->default(false)->change();
        });
    }
};
