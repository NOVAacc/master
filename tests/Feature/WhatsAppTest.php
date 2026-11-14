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
class WhatsAppTest extends TestCase
{
    use RefreshDatabase;
    public function test_bookings_do_not_send_whatsapp_or_offer_manual_whatsapp_links(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        config(['services.whatsapp' => ['enabled' => true, 'token' => 'test-token',
            'phone_number_id' => '123456', 'version' => 'v23.0',
            'client_template' => 'legacy_client', 'master_template' => 'legacy_master', 'language' => 'ru']]);
        Http::fake();
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'no-whatsapp', 'display_name' => 'Тест',
            'timezone' => 'Asia/Bishkek', 'phone' => '+996555111222',
            'whatsapp_notifications' => true, 'auto_confirm' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Услуга', 'duration_minutes' => 60, 'price_som' => 500, 'is_active' => true,
        ]);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 1,
            'starts_at' => '09:00', 'ends_at' => '18:00',
        ]);
        $appointment = app(BookAppointment::class)->create(
            $master, $service, CarbonImmutable::parse('2026-10-19 07:00:00', 'UTC'),
            'Клиент', '+996555333444', null, true
        );
        $this->assertSame('confirmed', $appointment->status);
        Http::assertNothingSent();
        $this->assertDatabaseCount('master_notifications', 0);
        $this->assertDatabaseHas('master_alerts', [
            'master_appointment_id' => $appointment->id, 'kind' => 'new',
        ]);
        $this->post('/m/'.$master->slug.'/book', [
            'service_id' => $service->id,
            'starts_at' => '2026-10-19T08:30:00+00:00',
            'client_name' => 'Клиент 2',
            'client_phone' => '+996555333445',
            'consent' => true,
        ])->assertRedirect();
        $receipt = session('booking_receipt');
        $this->assertArrayNotHasKey('masterLink', $receipt);
        $this->assertArrayNotHasKey('automaticStatus', $receipt);
        Http::assertNothingSent();
        $this->assertDatabaseCount('master_notifications', 0);
    }
}
