<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFixture(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'edge-master',
            'display_name' => 'Мастер с очень длинным демонстрационным названием рабочего кабинета',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
            'auto_confirm' => false,
        ]);
        $service = $master->services()->create([
            'name' => 'Стрижка',
            'duration_minutes' => 60,
            'price_som' => 800,
            'is_active' => true,
        ]);
        $date = CarbonImmutable::now('Asia/Bishkek')->addDays(2)->startOfDay();
        \Illuminate\Support\Facades\DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id,
            'weekday' => $date->dayOfWeekIso,
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
        ]);
        $start = $date->setTime(9, 0)->toIso8601String();

        return [$master, $service, $date->toDateString(), $start];
    }

    private function payload(int $serviceId, string $start, string $name = 'Тестовый клиент'): array
    {
        return [
            'service_ids' => [$serviceId],
            'starts_at' => $start,
            'client_name' => $name,
            'client_phone' => '996700123456',
            'consent' => true,
        ];
    }

    public function test_full_day_has_no_available_slots_after_booking(): void
    {
        [$master, $service, $day, $start] = $this->bookingFixture();
        $this->post("/m/{$master->slug}/book", $this->payload($service->id, $start))
            ->assertSessionHasNoErrors();
        $this->get("/m/{$master->slug}?date={$day}&selection=1&services[]={$service->id}")
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Booking')
                ->where('slots', fn ($slots) => collect($slots)->every(fn ($slot) => ! $slot['available'])));
    }

    public function test_two_submissions_for_same_slot_create_only_one_appointment(): void
    {
        [$master, $service, , $start] = $this->bookingFixture();
        $payload = $this->payload($service->id, $start);
        $this->post("/m/{$master->slug}/book", $payload)->assertSessionHasNoErrors();
        $this->post("/m/{$master->slug}/book", $payload)->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('master_appointments', 1);
    }

    public function test_long_names_are_accepted_only_within_documented_limit(): void
    {
        [$master, $service, , $start] = $this->bookingFixture();
        $name = str_repeat('А', 100);
        $this->post("/m/{$master->slug}/book", $this->payload($service->id, $start, $name))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_appointments', ['client_name' => $name]);

        $this->post("/m/{$master->slug}/book", $this->payload($service->id, $start, str_repeat('Б', 101)))
            ->assertSessionHasErrors('client_name');
        $this->assertDatabaseCount('master_appointments', 1);
    }
}
