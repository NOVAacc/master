<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\User;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        $this->travelTo(CarbonImmutable::now('UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function master(string $slug = 'aizada'): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => $slug,
            'display_name' => 'Айзада', 'timezone' => 'Asia/Bishkek']);
        $service = $master->services()->create(['name' => 'Стрижка', 'duration_minutes' => 60,
            'price_som' => 800, 'is_active' => true]);
        DB::table('master_working_hours')->insert(['master_profile_id' => $master->id,
            'weekday' => 1, 'starts_at' => '09:00:00', 'ends_at' => '18:00:00']);

        return [$user, $master->fresh(), $service];
    }

    private function payload(MasterService $service, string $start = '2026-10-19T04:00:00+00:00'): array
    {
        return ['service_id' => $service->id, 'starts_at' => $start, 'client_name' => 'Клиент',
            'client_phone' => '+996 555 123456', 'consent' => true];
    }

    public function test_registration_creates_private_cabinet_and_profile(): void
    {
        $this->post('/register', ['name' => 'Айзада', 'email' => 'aizada@example.test',
            'password' => 'safe-test-password', 'password_confirmation' => 'safe-test-password', 'slug' => 'aizada'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('master_profiles', ['slug' => 'aizada']);
        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard')->has('master'));
    }

    public function test_guests_cannot_access_cabinet(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_logout_and_invalid_password(): void
    {
        [$user] = $this->master();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_master_cannot_modify_another_masters_records(): void
    {
        [$user] = $this->master();
        [, $other, $service] = $this->master('another');
        $this->actingAs($user)->put('/services/'.$service->id, [])->assertNotFound();
        $appointment = app(BookAppointment::class)->create($other, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Клиент', '+996555123456');
        $this->put('/appointments/'.$appointment->id, ['status' => 'cancelled'])->assertNotFound();
    }

    public function test_public_booking_stores_utc_and_price_snapshot(): void
    {
        [, $master, $service] = $this->master();
        $this->post('/m/aizada/book', $this->payload($service))->assertSessionHasNoErrors()->assertRedirect('/m/aizada');
        $this->assertDatabaseHas('master_appointments', ['starts_at' => '2026-10-19 04:00:00',
            'ends_at' => '2026-10-19 05:00:00', 'price_som' => 800, 'status' => 'pending']);
    }

    public function test_overlap_rejected_but_adjacent_slot_allowed(): void
    {
        [, , $service] = $this->master();
        $this->post('/m/aizada/book', $this->payload($service))->assertSessionHasNoErrors();
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T04:30:00+00:00'))->assertSessionHasErrors('starts_at');
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T05:00:00+00:00'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('master_appointments', 2);
    }

    public function test_service_duration_must_fit_entirely_inside_working_hours(): void
    {
        [, , $service] = $this->master();
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T11:30:00+00:00'))->assertSessionHasErrors('starts_at');
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-20T04:00:00+00:00'))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('master_appointments', 0);
    }

    public function test_blocked_time_and_disabled_booking_are_rejected(): void
    {
        [, $master, $service] = $this->master();
        DB::table('master_time_blocks')->insert(['master_profile_id' => $master->id,
            'starts_at' => '2026-10-19 04:00:00', 'ends_at' => '2026-10-19 05:00:00']);
        $this->post('/m/aizada/book', $this->payload($service))->assertSessionHasErrors('starts_at');
        $master->update(['booking_enabled' => false]);
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T06:00:00+00:00'))->assertSessionHasErrors('booking');
    }

    public function test_consent_and_valid_phone_are_required(): void
    {
        [, , $service] = $this->master();
        $this->post('/m/aizada/book', [...$this->payload($service), 'consent' => false, 'client_phone' => 'invalid'])
            ->assertSessionHasErrors(['consent', 'client_phone']);
    }

    public function test_public_slots_hide_customer_data_and_busy_times(): void
    {
        [, $master, $service] = $this->master();
        app(BookAppointment::class)->create($master, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Private Client', '+996555123456');
        $this->get('/m/aizada?date=2026-10-19&service='.$service->id)->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Booking')
                ->where('slots', fn ($slots) => ! collect($slots)->contains('label', '10:00') && collect($slots)->contains('label', '11:00'))
                ->missing('appointments'))
            ->assertDontSee('Private Client')->assertDontSee('+996555123456');
    }

    public function test_cancelled_slot_can_be_rebooked_but_old_record_cannot_reopen(): void
    {
        [$user, $master, $service] = $this->master();
        $appointment = app(BookAppointment::class)->create($master, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Клиент', '+996555123456');
        $this->actingAs($user)->put('/appointments/'.$appointment->id, ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->post('/m/aizada/book', $this->payload($service))->assertSessionHasNoErrors();
        $this->put('/appointments/'.$appointment->id, ['status' => 'confirmed'])->assertUnprocessable();
    }

    public function test_schedule_validation_and_empty_schedule(): void
    {
        [$user] = $this->master();
        $this->actingAs($user)->put('/schedule', ['hours' => [['weekday' => 1, 'starts_at' => '18:00', 'ends_at' => '09:00']]])
            ->assertSessionHasErrors('hours.0.ends_at');
        $this->put('/schedule', ['hours' => [['weekday' => 2, 'starts_at' => '10:00', 'ends_at' => '19:00']]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_working_hours', ['weekday' => 2, 'starts_at' => '10:00']);
        $this->put('/schedule', ['hours' => []])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('master_working_hours', 0);
    }

    public function test_inactive_or_foreign_service_is_not_bookable(): void
    {
        [, $master, $service] = $this->master();
        [, , $otherService] = $this->master('other');
        $this->post('/m/aizada/book', $this->payload($otherService))->assertNotFound();
        $service->update(['is_active' => false]);
        $this->post('/m/aizada/book', $this->payload($service))->assertNotFound();
    }

    public function test_past_and_malformed_dates_are_rejected(): void
    {
        [, , $service] = $this->master();
        $this->post('/m/aizada/book', $this->payload($service, '2026-01-01T04:00:00+00:00'))->assertSessionHasErrors('starts_at');
        $this->get('/m/aizada?date=invalid')->assertSessionHasErrors('date');
    }
}
