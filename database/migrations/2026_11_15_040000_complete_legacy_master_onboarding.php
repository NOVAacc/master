<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The Telegram requirement was introduced after many masters were
        // already actively using BOSH. Do not retroactively reopen onboarding
        // for established profiles that have services and working hours.
        DB::table('master_profiles')
            ->where('created_at', '<', '2026-11-15 00:00:00')
            ->whereNull('onboarding_completed_at')
            ->whereNull('onboarding_restart_step')
            ->whereNotNull('industry')
            ->whereNotNull('activity')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('master_working_hours')
                    ->whereColumn('master_working_hours.master_profile_id', 'master_profiles.id');
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('master_services')
                    ->whereColumn('master_services.master_profile_id', 'master_profiles.id');
            })
            ->update([
                'onboarding_completed_at' => now(),
                'onboarding_telegram_required' => false,
            ]);
    }

    public function down(): void
    {
        // Backfilled completion may subsequently reflect genuine user actions.
        // Do not clear it during rollback.
    }
};
