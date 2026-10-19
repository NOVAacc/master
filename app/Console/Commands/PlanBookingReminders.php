<?php

namespace App\Console\Commands;

use App\Services\WhatsApp;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PlanBookingReminders extends Command
{
    protected $signature = 'bosh:plan-reminders {--dry-run : Report eligible reminders without writing}';
    protected $description = 'Plan opt-in 24-hour WhatsApp reminders without sending messages';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $planned = 0;
        $candidates = 0;
        $dryRun = (bool) $this->option('dry-run');

        // A cancelled or superseded reminder must not be sent. Do not
        // invalidate delivery history already accepted by the provider.
        if (! $dryRun) {
            DB::table('master_reminders')->where('status', 'scheduled')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')->from('master_appointments as a')
                        ->whereColumn('a.id', 'master_reminders.master_appointment_id')
                        ->where(function ($q) {
                            $q->where('a.status', '!=', 'confirmed')
                                ->orWhere('a.whatsapp_consent', false)
                                ->orWhereColumn('a.notification_revision', '!=', 'master_reminders.notification_revision');
                        });
                })->update(['status' => 'cancelled', 'updated_at' => now()]);
        }

        DB::table('master_appointments as a')
            ->where('a.status', 'confirmed')
            ->where('a.whatsapp_consent', true)
            ->where('a.starts_at', '>', $now->addHour()->format('Y-m-d H:i:s'))
            ->where('a.starts_at', '<=', $now->addHours(48)->format('Y-m-d H:i:s'))
            ->select('a.id', 'a.client_phone', 'a.notification_revision', 'a.starts_at')
            ->orderBy('a.id')
            ->chunkById(200, function ($appointments) use ($now, $dryRun, &$planned, &$candidates) {
                foreach ($appointments as $a) {
                    $phone = WhatsApp::phone($a->client_phone);
                    if (! $phone) {
                        continue;
                    }
                    $candidates++;
                    $start = CarbonImmutable::parse($a->starts_at, 'UTC');
                    $target = $start->subDay();
                    $due = $target->greaterThan($now) ? $target : $now;
                    if ($dryRun) {
                        $planned++;
                        continue;
                    }

                    // Unique DB key makes repeated planner runs safe.
                    $planned += DB::table('master_reminders')->insertOrIgnore([
                        'master_appointment_id' => $a->id,
                        'notification_revision' => (int) $a->notification_revision,
                        'channel' => 'whatsapp',
                        'recipient' => 'client',
                        'phone' => $phone,
                        'due_at' => $due->format('Y-m-d H:i:s'),
                        'status' => 'scheduled',
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'a.id', 'id');

        $this->components->info("Eligible: {$candidates}; ".($dryRun ? 'potential' : 'new')." reminders: {$planned}. No messages sent.");

        return self::SUCCESS;
    }
}
