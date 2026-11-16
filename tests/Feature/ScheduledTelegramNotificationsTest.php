<?php
namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ScheduledTelegramNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    }

    private function makeBooking(string $date = '2026-11-18 08:30:00'): \App\Models\MasterAppointment
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-16 00:00:00', 'UTC'));
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'telegram-scheduled',
            'display_name' => 'Эрика', 'timezone' => 'Asia/Bishkek',
            'auto_confirm' => true, 'booking_enabled' => true,
            'telegram_chat_id' => '4444', 'telegram_alerts_enabled' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Маникюр', 'duration_minutes' => 60, 'price_som' => 1200, 'is_active' => true,
        ]);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 3,
            'starts_at' => '09:00', 'ends_at' => '20:00',
        ]);
        $a = app(BookAppointment::class)->create(
            $master, $service, CarbonImmutable::parse($date, 'UTC'), 'Айдана', '+996555111222'
        );
        $a->forceFill(['client_telegram_chat_id' => '5555'])->save();
        return $a;
    }

    public function test_client_two_hour_reminder_is_sent_once_and_master_gets_thirty_minute_reminder(): void
    {
        $a = $this->makeBooking();
        $this->travelTo(CarbonImmutable::parse('2026-11-18 06:30:00', 'UTC'));
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['chat_id'] === '5555' && str_contains($r['text'], 'До визита: 2 ч'));
        $this->travelTo(CarbonImmutable::parse('2026-11-18 08:00:00', 'UTC'));
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => $r['chat_id'] === '4444' && str_contains($r['text'], 'Скоро клиент'));
        $this->assertDatabaseCount('telegram_scheduled_notifications', 2);
    }

    public function test_cancellation_blocks_reminders_and_settings_can_disable_a_window(): void
    {
        $a = $this->makeBooking();
        $a->forceFill(['status' => 'cancelled'])->save();
        $this->travelTo(CarbonImmutable::parse('2026-11-18 06:30:00', 'UTC'));
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertNothingSent();
        $a->forceFill(['status' => 'confirmed'])->save();
        SiteSetting::updateOrCreate(['key' => 'telegram_client_2h_enabled'], ['value' => '0']);
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_changing_revision_without_time_does_not_repeat_client_reminder(): void
    {
        $a = $this->makeBooking();
        $this->travelTo(CarbonImmutable::parse('2026-11-18 06:30:00', 'UTC'));
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        $a->forceFill(['revision' => 2, 'price_som' => 1500])->save();
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertSentCount(1);
    }

    public function test_daily_summary_sent_only_once_when_there_are_bookings(): void
    {
        $a = $this->makeBooking();
        $this->travelTo(CarbonImmutable::parse('2026-11-17 14:00:00', 'UTC')); // 20:00 in Bishkek
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        $this->artisan('bosh:send-scheduled-telegram')->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['chat_id'] === '4444' && str_contains($r['text'], 'Записи на завтра'));
    }
}
