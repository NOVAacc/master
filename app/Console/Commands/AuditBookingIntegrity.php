<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AuditBookingIntegrity extends Command
{
    protected $signature = 'bosh:audit-bookings';
    protected $description = 'Read-only integrity audit of group capacity, client links and booking collisions';

    public function handle(): int
    {
        $overCapacity = DB::table('master_group_sessions')
            ->whereColumn('reserved_seats', '>', 'capacity')->count();

        $seatMismatches = DB::table('master_group_sessions as s')
            ->leftJoin('master_group_participants as p', function ($join) {
                $join->on('p.master_group_session_id', '=', 's.id')
                    ->where('p.status', '=', 'confirmed');
            })
            ->groupBy('s.id', 's.reserved_seats')
            ->havingRaw('COUNT(p.id) <> s.reserved_seats')
            ->get(['s.id'])->count();

        $invalidClientLinks = DB::table('master_appointments as a')
            ->join('master_clients as c', 'a.master_client_id', '=', 'c.id')
            ->whereColumn('a.master_profile_id', '!=', 'c.master_profile_id')
            ->count();

        $groupOverlaps = DB::table('master_group_sessions as g')
            ->join('master_appointments as a', function ($join) {
                $join->on('g.master_profile_id', '=', 'a.master_profile_id')
                    ->whereColumn('g.starts_at', '<', 'a.ends_at')
                    ->whereColumn('g.ends_at', '>', 'a.starts_at');
            })
            ->where('g.status', 'open')
            ->whereIn('a.status', ['pending', 'confirmed'])
            ->count();

        $results = [
            'Group sessions over capacity' => $overCapacity,
            'Mismatch between confirmed participants and reserved seats' => $seatMismatches,
            'Appointment links to another master client' => $invalidClientLinks,
            'Active group sessions overlapping individual appointments' => $groupOverlaps,
        ];
        foreach ($results as $label => $count) {
            $this->line($label.': '.$count);
        }
        $total = array_sum($results);
        if ($total) {
            $this->components->error('Integrity anomalies detected: '.$total.'. No data was changed.');
            return self::FAILURE;
        }
        $this->components->info('No integrity anomalies detected in the checked invariants. Read-only.');
        return self::SUCCESS;
    }
}
