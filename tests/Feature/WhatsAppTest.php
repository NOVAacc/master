<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use App\Services\WhatsApp;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        config(['services.whatsapp' => ['enabled' => true, 'token' => 'test-token-only', 'phone_number_id' => '123456',
            'version' => 'v23.0', 'language' => 'ru', 'client_template' => 'master_booking_client', 'master_template' => 'master_booking_master']]);
        Http::preventStrayRequests();
    }

    private function fixture(bool $automatic = false, bool $consent = true): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'whatsapp-master', 'display_name' => 'Айзада',
            'timezone' => 'Asia/Bishkek', 'phone' => '+996 555 111222', 'whatsapp_notifications' => true, 'auto_confirm' => $automatic]);
        $service = $master->services()->create(['name' => 'Ресницы', 'duration_minutes' => 90, 'price_som' => 1500, 'is_active' => true]);
        DB::table('master_working_hours')->insert(['master_profile_id' => $master->id, 'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);
        $appointment = app(BookAppointment::class)->create($master, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Клиент', '+996 700 333444', null, $consent);

        return [$user, $master, $service, $appointment];
    }

    public function test_confirmation_notifies_client_and_master_once_with_local_time_and_price(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);
        [$user, , , $appointment] = $this->fixture();
        Http::assertNothingSent();
        $this->actingAs($user)->post('/appointments/'.$appointment->id, ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->post('/appointments/'.$appointment->id, ['status' => 'confirmed'])->assertSessionHasNoErrors();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['to'] === '996700333444' && $request['type'] === 'template'
            && $request['template']['components'][0]['parameters'][4]['text'] === '10:00–11:30 (Asia/Bishkek)'
            && $request['template']['components'][0]['parameters'][5]['text'] === '1 500 сом');
        $this->assertDatabaseCount('master_notifications', 2);
        $this->assertSame(2, $appointment->notifications()->where('status', 'accepted')->count());
    }

    public function test_auto_confirmation_respects_separate_client_and_master_opt_in(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.auto']]], 200)]);
        [, $master, , $appointment] = $this->fixture(true, false);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '996555111222');
        $this->assertSame('master', $appointment->notifications()->sole()->recipient);
        $master->update(['whatsapp_notifications' => false]);
        $appointment->update(['whatsapp_consent' => true]);
        app(WhatsApp::class)->confirmed($appointment->fresh());
        Http::assertSentCount(2);
    }

    public function test_missing_provider_configuration_never_claims_or_sends_a_message(): void
    {
        config(['services.whatsapp.enabled' => false]);
        [, , , $appointment] = $this->fixture(true);
        Http::assertNothingSent();
        $this->assertSame(2, $appointment->notifications()->where('status', 'not_configured')->where('attempts', 0)->count());
    }

    public function test_ambiguous_timeout_is_not_retried_and_does_not_undo_confirmation(): void
    {
        Http::fake(fn () => throw new ConnectionException('simulated timeout'));
        [$user, , , $appointment] = $this->fixture();
        $this->actingAs($user)->post('/appointments/'.$appointment->id, ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame(2, $appointment->notifications()->where('status', 'unknown')->where('attempts', 1)->count());
        $this->post('/appointments/'.$appointment->id.'/whatsapp')->assertSessionHasNoErrors();
        $this->assertSame(2, $appointment->notifications()->where('attempts', 1)->count());
    }

    public function test_rejected_requests_can_retry_but_other_owners_cannot_trigger_it(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['error' => ['code' => 132001]], 400)->push(['error' => ['code' => 132001]], 400)->push(['messages' => [['id' => 'wamid.retry1']]], 200)->push(['messages' => [['id' => 'wamid.retry2']]], 200)]);
        [$user, , , $appointment] = $this->fixture(true);
        $this->assertSame(2, $appointment->notifications()->where('status', 'failed')->count());
        $other = User::factory()->create();
        MasterProfile::create(['user_id' => $other->id, 'slug' => 'other-wa', 'display_name' => 'Другой']);
        $this->actingAs($other)->post('/appointments/'.$appointment->id.'/whatsapp')->assertNotFound();
        Http::assertSentCount(2);
        $this->actingAs($user)->post('/appointments/'.$appointment->id.'/whatsapp')->assertSessionHasNoErrors();
        $this->assertSame(2, $appointment->notifications()->where('status', 'accepted')->where('attempts', 2)->count());
    }

    public function test_manual_links_and_receipt_are_private_to_the_booking_session(): void
    {
        config(['services.whatsapp.enabled' => false]);
        [, $master, $service] = $this->fixture();
        $this->post('/m/'.$master->slug.'/book', ['service_id' => $service->id, 'starts_at' => '2026-10-19T07:00:00+00:00',
            'client_name' => 'Receipt Client', 'client_phone' => '+996700444555', 'consent' => true])->assertSessionHas('booking_receipt');
        $receipt = session('booking_receipt');
        $this->assertSame('pending', $receipt['status']);
        $this->assertStringStartsWith('https://wa.me/996555111222?text=', $receipt['masterLink']);
        $this->assertStringContainsString('13:00–14:30', $receipt['text']);
        $this->flushSession();
        $this->get('/m/'.$master->slug)->assertDontSee('Receipt Client');
        $this->assertNull(WhatsApp::phone('not-a-phone'));
        $this->assertSame('996555111222', WhatsApp::phone('0555 111222'));
    }
}
