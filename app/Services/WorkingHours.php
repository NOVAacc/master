<?php

namespace App\Services;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class WorkingHours
{
    /**
     * Request-scoped cache, one query per master and weekday.
     * Public availability checks up to 48 slots; querying per slot is wasteful.
     *
     * @var array<string, \Illuminate\Support\Collection>
     */
    private array $days = [];

    public function contains(MasterProfile $master, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $localStart = $start->setTimezone($master->timezone);
        $localEnd = $end->setTimezone($master->timezone);
        if (! $localStart->isSameDay($localEnd) || $end->lessThanOrEqualTo($start)) {
            return false;
        }

        $key = $master->id.':'.$localStart->isoWeekday();
        if (! array_key_exists($key, $this->days)) {
            $this->days[$key] = DB::table('master_working_hours')
                ->where('master_profile_id', $master->id)
                ->where('weekday', $localStart->isoWeekday())
                ->get(['starts_at', 'ends_at', 'break_starts_at', 'break_ends_at']);
        }

        $from = $localStart->format('H:i');
        $until = $localEnd->format('H:i');

        return $this->days[$key]->contains(static function ($hour) use ($from, $until): bool {
            $workStart = substr($hour->starts_at, 0, 5);
            $workEnd = substr($hour->ends_at, 0, 5);
            if ($from < $workStart || $until > $workEnd) {
                return false;
            }

            if ($hour->break_starts_at === null || $hour->break_ends_at === null) {
                return true;
            }

            $breakStart = substr($hour->break_starts_at, 0, 5);
            $breakEnd = substr($hour->break_ends_at, 0, 5);

            return $until <= $breakStart || $from >= $breakEnd;
        });
    }
}
