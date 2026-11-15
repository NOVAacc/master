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

class PersistentClientIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        config([
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram.bot_username' => 'Boshkg_bot',
            'services.telegram.webhook_secret' => 'testing-secret',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200)]);
    }

    private function master(string $slug): array
    {
        $master = MasterProfile::create([
            'user_id' => User::factory()->create()->id,
            'slug' => $slug, 'display_name' => 'Мастер '.$slug,
            'timezone' => 'Asia/Bishkek', 'auto_confirm' => true,
            'booking_enabled' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Услуга', 'duration_minutes' => 60,
            'price_som' => 500, 'is_active' => true,
        ]);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id,
            'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00',
        ]);
        return [$master, $service];
    }

    private function book(MasterProfile $master, $service, string $date, string $name = 'Клиент'): void
    {
        $this->post('/m/'.$master->slug.'/book', [
            'service_id' => $service->id,
            'starts_at' => $date,
            'client_name' => $name,
            'client_phone' => '+996700333444',
            'consent' => true,
        ])->assertRedirect();
    }

    public function test_verified_telegram_creates_global_identity_and_browser_session(): void
    {
        [$master, $service] = $this->master('master-one');
        $this->book($master, $service, '2026-10-19T04:00:00+00:00');
        $first = $master->appointments()->firstOrFail();
        $url = session('booking_receipt')['clientTelegramUrl'];
        $token = substr($url, strlen('https://t.me/Boshkg_bot?start='));
        $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/start '.$token, 'chat' => ['id' => 12345, 'type' => 'private']],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'testing-secret'])->assertOk();
        $accountId = $first->fresh()->client_account_id;
        $this->assertNotNull($accountId);
        $this->assertSame('12345', DB::table('client_accounts')->where('id', $accountId)->value('telegram_chat_id'));
        $claim = $this->get('/client/telegram/status')->assertOk()->assertJsonPath('connected', true)->assertCookie('bosh_client_session');
        $cookie = collect($claim->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'bosh_client_session');
        $this->assertNotNull($cookie);
        // Feature tests do not automatically keep response cookies across requests.
        $this->withUnencryptedCookie('bosh_client_session', $cookie->getValue());
        $this->assertDatabaseCount('client_account_sessions', 1);
        $this->assertNull($first->fresh()->client_telegram_browser_claim_hash);

        // New bookings in the same confirmed browser inherit verified identity.
        $this->book($master, $service, '2026-10-19T06:00:00+00:00');
        $second = $master->appointments()->orderByDesc('id')->firstOrFail();
        $this->assertSame($accountId, $second->client_account_id);
        $this->assertNull(session('booking_receipt')['clientTelegramUrl']);
        [$otherMaster, $otherService] = $this->master('master-four');
        $this->book($otherMaster, $otherService, '2026-10-19T04:00:00+00:00');
        $third = $otherMaster->appointments()->firstOrFail();
        $this->assertSame($accountId, $third->client_account_id);
        $this->get('/my-bookings')->assertOk()->assertInertia(
            fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->component('ClientBookings')->has('bookings', 3)->etc()
        );
        $this->post('/my-bookings/notifications', ['enabled' => false])->assertRedirect();
        $this->assertDatabaseHas('client_accounts', ['id' => $accountId, 'notifications_enabled' => false]);
        $this->post('/my-bookings/notifications', ['enabled' => true])->assertRedirect();
        $this->assertDatabaseHas('client_accounts', ['id' => $accountId, 'notifications_enabled' => true]);
        $this->post('/my-bookings/logout')->assertRedirect('/');
        $this->assertDatabaseCount('client_account_sessions', 0);
        $this->get('/my-bookings')->assertForbidden();
    }

    public function test_client_dashboard_cannot_access_without_verified_session_or_from_other_account(): void
    {
        [$master, $service] = $this->master('master-two');
        $this->book($master, $service, '2026-10-19T04:00:00+00:00');
        $this->get('/my-bookings')->assertForbidden();
        $this->post('/my-bookings/notifications', ['enabled' => false])->assertForbidden();
        $this->assertDatabaseCount('client_account_sessions', 0);
    }

    public function test_telegram_webhook_cannot_be_replayed_or_used_without_secret(): void
    {
        [$master, $service] = $this->master('master-three');
        $this->book($master, $service, '2026-10-19T04:00:00+00:00');
        $token = substr(session('booking_receipt')['clientTelegramUrl'], strlen('https://t.me/Boshkg_bot?start='));
        $payload = ['message' => ['text' => '/start '.$token, 'chat' => ['id' => 12345, 'type' => 'private']]];
        $this->postJson('/telegram/webhook', $payload)->assertForbidden();
        $this->postJson('/telegram/webhook', $payload, ['X-Telegram-Bot-Api-Secret-Token' => 'testing-secret'])->assertOk();
        $payload['message']['chat']['id'] = 23456;
        $this->postJson('/telegram/webhook', $payload, ['X-Telegram-Bot-Api-Secret-Token' => 'testing-secret'])->assertOk();
        $this->assertDatabaseCount('client_accounts', 1);
        $this->assertSame('12345', $master->appointments()->first()->client_telegram_chat_id);
    }
}
