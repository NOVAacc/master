<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use App\Services\ClientBookingAlerts;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerTelegramAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        config(['services.telegram.bot_token' => 'telegram-test-token',
            'services.telegram.bot_username' => 'Boshkg_bot',
            'services.telegram.webhook_secret' => 'test-secret']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200)]);
    }

    private function booking(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'tg-test-master',
            'display_name' => 'Мастер', 'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true, 'auto_confirm' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Маникюр', 'duration_minutes' => 60,
            'price_som' => 1000, 'is_active' => true,
        ]);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 1,
            'starts_at' => '09:00', 'ends_at' => '18:00',
        ]);
        $appointment = app(BookAppointment::class)->create(
            $master, $service, CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'),
            'Клиент', '+996700333444'
        );
        return [$user, $master, $service, $appointment];
    }

    public function test_customer_can_opt_in_with_booking_token_without_account(): void
    {
        [$user, $master, $service] = $this->booking();
        $this->post('/m/'.$master->slug.'/book', [
            'service_id' => $service->id, 'starts_at' => '2026-10-19T06:00:00+00:00',
            'client_name' => 'Клиент 2', 'client_phone' => '+996700333445',
            'consent' => true,
        ])->assertRedirect();
        $receipt = session('booking_receipt');
        $this->assertStringStartsWith('https://t.me/Boshkg_bot?start=c', $receipt['clientTelegramUrl']);
        $payload = substr($receipt['clientTelegramUrl'], strlen('https://t.me/Boshkg_bot?start='));
        $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/start '.$payload, 'chat' => ['id' => 123456, 'type' => 'private']],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertOk();
        $booked = $master->appointments()->orderByDesc('id')->first();
        $this->assertSame('123456', $booked->client_telegram_chat_id);
        $this->assertNull($booked->client_telegram_link_hash);
        // A replay cannot rebind this appointment to a different chat.
        $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/start '.$payload, 'chat' => ['id' => 98765, 'type' => 'private']],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertOk();
        $this->assertSame('123456', $booked->fresh()->client_telegram_chat_id);
    }

    public function test_master_edit_notifies_connected_client_and_provides_new_signed_link(): void
    {
        [$user, , $service, $appointment] = $this->booking();
        $appointment->forceFill(['client_telegram_chat_id' => '12345'])->save();
        $this->actingAs($user)->post('/appointments/'.$appointment->id.'/edit', [
            'revision' => 1,
            'starts_at_local' => '2026-10-19T11:00',
            'duration_minutes' => 60, 'price_som' => 1000,
            'service_ids' => [$service->id], 'client_name' => 'Клиент',
            'client_phone' => '+996700333444',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('client_booking_alerts', [
            'master_appointment_id' => $appointment->id,
            'revision' => 2, 'kind' => 'rescheduled', 'delivery_status' => 'sent',
        ]);
        Http::assertSent(fn ($req) =>
            str_contains((string)$req->url(), '/sendMessage')
            && ($req['chat_id'] ?? null) === '12345'
            && str_contains($req['text'], 'Было: 19.10.2026 10:00')
            && str_contains($req['text'], 'Новое время: 19.10.2026 11:00')
        );
        $message = DB::table('client_booking_alerts')->where('master_appointment_id', $appointment->id)->value('message');
        $this->assertStringContainsString('revision=2', $message);
    }

    public function test_unconnected_customer_gets_manual_fallback_and_marking_is_owner_only(): void
    {
        [$user, $master, $service, $appointment] = $this->booking();
        $this->actingAs($user)->post('/appointments/'.$appointment->id.'/edit', [
            'revision' => 1, 'starts_at_local' => '2026-10-19T11:00',
            'duration_minutes' => 60, 'price_som' => 1000,
            'service_ids' => [$service->id], 'client_name' => 'Клиент',
            'client_phone' => '+996700333444',
        ])->assertSessionHasNoErrors();
        $alert = DB::table('client_booking_alerts')->where('master_appointment_id', $appointment->id)->first();
        $this->assertSame('pending_manual', $alert->delivery_status);
        $this->get('/appointments/'.$appointment->id.'/client-alert')->assertOk()->assertJsonPath('id', $alert->id);
        $other = User::factory()->create();
        MasterProfile::create(['user_id' => $other->id, 'slug' => 'another-master', 'display_name' => 'Другой мастер']);
        $this->actingAs($other)->get('/appointments/'.$appointment->id.'/client-alert')->assertNotFound();
        $this->post('/appointments/'.$appointment->id.'/client-alert/'.$alert->id.'/manual')->assertNotFound();
        $this->actingAs($user)->post('/appointments/'.$appointment->id.'/client-alert/'.$alert->id.'/manual')->assertRedirect();
        $this->assertDatabaseHas('client_booking_alerts', ['id' => $alert->id, 'delivery_status' => 'manual_done']);
    }
}
