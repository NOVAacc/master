<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HotSlotOfferTest extends TestCase
{
    use RefreshDatabase;

    private function setupOffer(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'hot-master', 'display_name' => 'Айжан',
            'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Маникюр', 'duration_minutes' => 60, 'price_som' => 1000,
            'is_active' => true, 'sort_order' => 1,
        ]);
        $start = CarbonImmutable::now('Asia/Bishkek')->addDays(3)->startOfDay()->setTime(10, 0);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => $start->isoWeekday(),
            'starts_at' => '09:00', 'ends_at' => '18:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$user, $master, $service, $start];
    }

    public function test_master_creates_multi_slot_offer_with_optional_special_price(): void
    {
        [$user, $master, $service, $start] = $this->setupOffer();
        $response = $this->actingAs($user)->postJson('/hot-slots', [
            'service_id' => $service->id, 'special_price_som' => 800, 'story_template' => 'red',
            'slots' => [$start->format('Y-m-d\TH:i'), $start->addHours(2)->format('Y-m-d\TH:i')],
        ]);
        $response->assertOk()->assertJsonStructure(['id', 'url']);
        $this->assertDatabaseHas('hot_slot_offers', [
            'master_profile_id' => $master->id, 'master_service_id' => $service->id,
            'special_price_som' => 800, 'story_template' => 'red',
        ]);
        $this->assertDatabaseCount('hot_slot_offer_slots', 2);
    }

    public function test_offer_booking_saves_special_price_without_changing_catalog_price(): void
    {
        [$user, $master, $service, $start] = $this->setupOffer();
        $created = $this->actingAs($user)->postJson('/hot-slots', [
            'service_id' => $service->id, 'special_price_som' => 800,
            'slots' => [$start->format('Y-m-d\TH:i')],
        ])->json();
        $token = basename(parse_url($created['url'], PHP_URL_PATH));

        $this->post('/m/'.$master->slug.'/book', [
            'offer' => $token, 'service_id' => $service->id, 'starts_at' => $start->utc()->toIso8601String(),
            'client_name' => 'Клиент', 'client_phone' => '+996 555 123 456', 'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('master_appointments', ['master_profile_id' => $master->id, 'price_som' => 800]);
        $this->assertDatabaseHas('master_services', ['id' => $service->id, 'price_som' => 1000]);
        $this->assertNotNull(DB::table('hot_slot_offer_slots')->value('master_appointment_id'));
    }

    public function test_cancelled_or_expired_offer_falls_back_to_regular_master_page(): void
    {
        [$user, $master, $service, $start] = $this->setupOffer();
        $created = $this->actingAs($user)->postJson('/hot-slots', [
            'service_id' => $service->id, 'slots' => [$start->format('Y-m-d\TH:i')],
        ])->json();
        $offerId = $created['id'];
        $token = basename(parse_url($created['url'], PHP_URL_PATH));
        $this->actingAs($user)->delete('/hot-slots/'.$offerId)->assertRedirect();
        $this->get('/m/'.$master->slug.'?offer='.$token)->assertOk()->assertInertia(fn ($page) => $page->where('hotOffer', null));
    }
}
