<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable();
        });

        // Existing masters who finished the full setup before this marker existed
        // should not be shown the wizard again on every dashboard reload.
        DB::table('master_profiles')
            ->whereNotNull('telegram_chat_id')
            ->whereNotNull('industry')
            ->whereNotNull('activity')
            ->whereNull('onboarding_restart_step')
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('master_working_hours')
                    ->whereColumn('master_working_hours.master_profile_id', 'master_profiles.id');
            })
            ->whereExists(function ($q) {
                $q->selectRaw('1')->from('master_services')
                    ->whereColumn('master_services.master_profile_id', 'master_profiles.id');
            })
            ->update([
                'onboarding_completed_at' => now(),
                'onboarding_telegram_required' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
