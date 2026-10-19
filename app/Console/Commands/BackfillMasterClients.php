<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Legacy records intentionally do not get linked by a schema migration.
 * Run during a controlled maintenance window; the process can be resumed.
 */
final class BackfillMasterClients extends Command
{
    protected $signature = 'bosh:backfill-clients {--dry-run : Count eligible appointments without modifying data}';
    protected $description = 'Link historical appointments to a master-scoped CRM client record';

    public function handle(): int
    {
        $scanned = 0;
        $linked = 0;
        $skipped = 0;
        $dryRun = (bool) $this->option('dry-run');

        DB::table('master_appointments')
            ->whereNull('master_client_id')
            ->select(['id', 'master_profile_id', 'client_name', 'client_phone'])
            ->orderBy('id')
            ->chunkById(200, function ($appointments) use (&$scanned, &$linked, &$skipped, $dryRun) {
                foreach ($appointments as $appointment) {
                    $scanned++;
                    $phone = preg_replace('/\\D+/', '', (string) $appointment->client_phone);
                    if (strlen($phone) < 7 || strlen($phone) > 15) {
                        $skipped++;
                        continue;
                    }
                    if ($dryRun) {
                        $linked++;
                        continue;
                    }
                    // Follow the same lock ordering as BookAppointment::create:
                    // one master row first, then its client and appointment.
                    $didLink = DB::transaction(function () use ($appointment, $phone): bool {
                        $master = DB::table('master_profiles')
                            ->where('id', $appointment->master_profile_id)
                            ->lockForUpdate()->first(['id']);
                        if (! $master) {
                            return false;
                        }
                        $current = DB::table('master_appointments')
                            ->where('id', $appointment->id)
                            ->where('master_profile_id', $master->id)
                            ->whereNull('master_client_id')->first(['id']);
                        if (! $current) {
                            return false;
                        }
                        $client = DB::table('master_clients')
                            ->where('master_profile_id', $master->id)
                            ->where('phone_digits', $phone)->first(['id']);
                        $clientId = $client?->id;
                        if (! $clientId) {
                            $clientId = DB::table('master_clients')->insertGetId([
                                'master_profile_id' => $master->id,
                                'phone_digits' => $phone,
                                'name' => mb_substr((string) $appointment->client_name, 0, 100),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        return DB::table('master_appointments')
                            ->where('id', $appointment->id)
                            ->where('master_profile_id', $master->id)
                            ->whereNull('master_client_id')
                            ->update(['master_client_id' => $clientId]) === 1;
                    }, 3);
                    if ($didLink) {
                        $linked++;
                    } else {
                        $skipped++;
                    }
                }
            }, 'id');

        $verb = $dryRun ? 'Potentially linkable' : 'Linked';
        $this->components->info("Scanned: {$scanned}; {$verb}: {$linked}; skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
