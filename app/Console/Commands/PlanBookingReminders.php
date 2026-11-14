<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
final class PlanBookingReminders extends Command
{
    protected $signature = 'bosh:plan-reminders {--dry-run : Check legacy reminders}';
    protected $description = 'Legacy WhatsApp reminder planner (disabled)';
    public function handle(): int
    {
        $this->info('WhatsApp reminders are disabled.');
        return self::SUCCESS;
    }
}
