<?php

namespace App\Services;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class WorkingHours
{
    public function contains(MasterProfile $master, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $localStart = $start->setTimezone($master->timezone);
        $localEnd = $end->setTimezone($master->timezone);
        if (! $localStart->isSameDay($localEnd) || $end->lessThanOrEqualTo($start)) {
            return false;
        }

        return DB::table('master_working_hours')
            ->where('master_profile_id', $master->id)
            ->where('weekday', $localStart->isoWeekday())
            ->where('starts_at', '<=', $localStart->format('H:i'))
            ->where('ends_at', '>=', $localEnd->format('H:i'))
            // An appointment must not overlap the recurring lunch interval.
            // An appointment ending exactly when lunch starts (or starting
            // when lunch ends) remains available.
            ->where(function ($query) use ($localStart, $localEnd) {
                $query->whereNull('break_starts_at')
                    ->orWhereNull('break_ends_at')
                    ->orWhere('break_starts_at', '>=', $localEnd->format('H:i'))
                    ->orWhere('break_ends_at', '<=', $localStart->format('H:i'));
            })
            ->exists();
    }
}
