<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ClientVisitReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        config(['services.telegram.bot_token' => 'test-telegram-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 100]], 200)]);
    }

    private function appointment(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'reminder-master',
            'display_name' => 'Тестовый мастер', 'timezone' => 'Asia/Bishkek',
            'auto_confirm' => true, 'booking_enabled' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Стрижка', 'duration_minutes' => 60,
            'price_som' => 1200, 'is_active' => true,
        ]);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 1,
            'starts_at' => '09:00', 'ends_at' => '18:00',
        ]);
        $appointment = app(BookAppointment::class)->create(
            $master, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'),
            'Клиент', '+996555111222'
        );
        $appointment->forceFill(['client_telegram_chat_id' => '123456'])->save();
        return [$user, $master, $service, $appointment];
    }

    public function test_sends_one_hour_before_visit_without_duplicates(): void
    {
        [,,, $appointment] = $this->appointment();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 02:59:00', 'UTC'));
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        Http::assertNothingSent();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 03:00:00', 'UTC'));
        $this->artisan('bosh:send-client-reminders', ['--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseCount('client_visit_reminders', 0);
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) =>
            ($request['chat_id'] ?? null) === '123456'
            && str_contains($request['text'], 'Напоминание о записи')
            && str_contains($request['text'], '19.10.2026, 10:00')
        );
        $this->assertDatabaseHas('client_visit_reminders', [
            'master_appointment_id' => $appointment->id, 'revision' => 1, 'status' => 'sent',
        ]);
    }

    public function test_does_not_send_for_cancelled_unconfirmed_or_opted_out_clients(): void
    {
        [,,, $appointment] = $this->appointment();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 03:00:00', 'UTC'));
        $appointment->update(['status' => 'cancelled']);
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        $appointment->update(['status' => 'pending']);
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        $appointment->update(['status' => 'confirmed']);
        $accountId = DB::table('client_accounts')->insertGetId([
            'telegram_chat_id' => '123456', 'notifications_enabled' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $appointment->forceFill(['client_account_id' => $accountId])->save();
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertDatabaseCount('client_visit_reminders', 1);
        $this->assertDatabaseHas('client_visit_reminders', ['status' => 'opted_out']);
    }

    public function test_rescheduled_booking_does_not_send_old_reminder_and_new_revision_is_eligible(): void
    {
        [,,, $appointment] = $this->appointment();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 03:00:00', 'UTC'));
        $appointment->forceFill(['starts_at' => CarbonImmutable::parse('2026-10-19 05:00:00', 'UTC'),
            'ends_at' => CarbonImmutable::parse('2026-10-19 06:00:00', 'UTC'), 'revision' => 2])->save();
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        Http::assertNothingSent();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'));
        $this->artisan('bosh:send-client-reminders')->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('client_visit_reminders', [
            'master_appointment_id' => $appointment->id, 'revision' => 2, 'status' => 'sent',
        ]);
    }

    public function test_change_reason_is_in_telegram_and_manual_message(): void
    {
        [$user, , $service, $appointment] = $this->appointment();
        $this->actingAs($user)->post('/appointments/'.$appointment->id.'/edit', [
            'revision' => 1,
            'starts_at_local' => '2026-10-19T11:00',
            'duration_minutes' => 60,
            'price_som' => 1200,
            'service_ids' => [$service->id],
            'client_name' => 'Клиент',
            'client_phone' => '+996555111222',
            'reason' => 'Мастер задерживается на предыдущем приёме',
        ])->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) =>
            str_contains($request['text'], 'Причина изменения: Мастер задерживается')
        );
        $message = DB::table('client_booking_alerts')
            ->where('master_appointment_id', $appointment->id)
            ->where('kind', 'rescheduled')->value('message');
        $this->assertStringContainsString('Причина изменения: Мастер задерживается', $message);
    }
}
