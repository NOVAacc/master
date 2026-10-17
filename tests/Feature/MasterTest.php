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

    public function test_home_lists_ready_profiles_and_ranks_only_completed_bookings(): void
    {
        [$user, $older, $service] = $this->master('older');
        $older->forceFill(['created_at' => now()->subDay()])->save();
        [, $newer] = $this->master('newer');
        [, $disabled] = $this->master('disabled');
        $disabled->update(['booking_enabled' => false]);
        [, $inactive, $inactiveService] = $this->master('inactive');
        $inactiveService->update(['is_active' => false]);
        $older->services()->create(['name' => 'Hidden cheap service', 'duration_minutes' => 30,
            'price_som' => 1, 'is_active' => false]);
        $appointment = app(BookAppointment::class)->create($older, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Secret Customer', '+996555999888');
        $appointment->update(['status' => 'completed']);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Home')
            ->has('newMasters', 2)->where('newMasters.0.slug', 'newer')
            ->where('newMasters.1.price_from', 800)->missing('newMasters.0.phone')
            ->missing('newMasters.0.user_id')->has('topMasters', 1)
            ->where('topMasters.0.slug', 'older')->where('topMasters.0.completed_count', 1))
            ->assertDontSee('Secret Customer')->assertDontSee('+996555999888')->assertDontSee($user->email);
        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Home'));
        $appointment->update(['status' => 'cancelled']);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('topMasters', 0));
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

    public function test_calendar_loads_a_complete_local_week_without_leaking_other_masters(): void
    {
        [$user, $master, $service] = $this->master();
        [, $other, $otherService] = $this->master('calendar-other');
        $selected = app(BookAppointment::class)->create($master, $service,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Calendar Client', '+996555123456');
        app(BookAppointment::class)->create($other, $otherService,
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Other Private Client', '+996555999888');
        DB::table('master_time_blocks')->insert(['master_profile_id' => $master->id,
            'starts_at' => '2026-10-20 04:00:00', 'ends_at' => '2026-10-20 05:00:00', 'reason' => 'Перерыв']);
        $this->actingAs($user)->get('/dashboard?calendar_date=2026-10-19')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('calendarDate', '2026-10-19')
                ->has('calendarAppointments', 1)->where('calendarAppointments.0.id', $selected->id)
                ->has('calendarBlocks', 1)->where('calendarBlocks.0.starts_at', '2026-10-20T04:00:00+00:00'))
            ->assertDontSee('Other Private Client')->assertDontSee('+996555999888');
        DB::table('master_appointments')->insert(array_fill(0, 201, [
            'master_profile_id' => $master->id, 'master_service_id' => $service->id,
            'client_name' => 'Archived cancellation', 'client_phone' => '+996555123456',
            'starts_at' => '2026-10-19 04:00:00', 'ends_at' => '2026-10-19 05:00:00',
            'price_som' => 800, 'service_name_snapshot' => 'Стрижка', 'status' => 'cancelled',
        ]));
        $this->get('/dashboard?calendar_date=2026-10-19')->assertInertia(fn (Assert $page) => $page
            ->has('calendarAppointments', 202)->has('appointments', 200));
        $this->get('/dashboard?calendar_date=2026-10-26')->assertInertia(fn (Assert $page) => $page
            ->has('calendarAppointments', 0)->has('calendarBlocks', 0));
        $this->get('/dashboard?calendar_date=invalid')->assertSessionHasErrors('calendar_date');
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

    public function test_multiple_services_reserve_the_combined_duration_and_keep_price_snapshots(): void
    {
        [, $master, $service] = $this->master();
        $extra = $master->services()->create(['name' => 'Укладка', 'duration_minutes' => 30, 'price_som' => 400]);
        $query = http_build_query(['date' => '2026-10-19', 'selection' => 1, 'services' => [$service->id, $extra->id]]);
        $this->get('/m/aizada?'.$query)->assertInertia(fn (Assert $page) => $page
            ->where('selectedServices', [$service->id, $extra->id])->where('workingDays', [1])
            ->where('slots', fn ($slots) => collect($slots)->contains('label', '16:30') && ! collect($slots)->contains('label', '17:00')));
        $payload = $this->payload($service);
        unset($payload['service_id']);
        $payload['service_ids'] = [$service->id, $extra->id];
        $payload['price_som'] = 1;
        $this->post('/m/aizada/book', $payload)->assertRedirect('/m/aizada')->assertSessionHasNoErrors();
        $appointment = $master->appointments()->firstOrFail();
        $this->assertSame(1200, $appointment->price_som);
        $this->assertSame('2026-10-19 05:30:00', $appointment->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('Стрижка + Укладка', $appointment->service_name_snapshot);
        $this->assertCount(2, $appointment->services_snapshot);
        $extra->update(['name' => 'Другая услуга', 'price_som' => 999]);
        $this->assertSame(400, $appointment->fresh()->services_snapshot[1]['price_som']);
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T05:00:00+00:00'))->assertSessionHasErrors('starts_at');
        $this->post('/m/aizada/book', $this->payload($service, '2026-10-19T05:30:00+00:00'))->assertSessionHasNoErrors();
        $payload['starts_at'] = '2026-10-19T11:00:00+00:00';
        $this->post('/m/aizada/book', $payload)->assertSessionHasErrors('starts_at');
    }

    public function test_multiple_services_reject_duplicates_empty_foreign_and_inactive_selections(): void
    {
        [, $master, $service] = $this->master();
        [, , $foreign] = $this->master('other-multi');
        $payload = $this->payload($service);
        unset($payload['service_id']);
        $this->post('/m/aizada/book', [...$payload, 'service_ids' => []])->assertSessionHasErrors('service_ids');
        $this->post('/m/aizada/book', [...$payload, 'service_ids' => [$service->id, $service->id]])->assertSessionHasErrors('service_ids.0');
        $this->post('/m/aizada/book', [...$payload, 'service_ids' => [$service->id, $foreign->id]])->assertNotFound();
        $service->update(['is_active' => false]);
        $this->post('/m/aizada/book', [...$payload, 'service_ids' => [$service->id]])->assertNotFound();
        $this->assertDatabaseCount('master_appointments', 0);
        $this->get('/m/aizada?selection=1')->assertInertia(fn (Assert $page) => $page->has('selectedServices', 0)->has('slots', 0));
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

    public function test_schedule_post_round_trip_updates_multiple_days_and_preserves_other_masters(): void
    {
        [$user, $master] = $this->master();
        [, $other] = $this->master('other-schedule');
        $hours = [
            ['weekday' => 1, 'starts_at' => '08:30', 'ends_at' => '17:15'],
            ['weekday' => 3, 'starts_at' => '10:00', 'ends_at' => '20:30'],
        ];
        $this->actingAs($user)->from('/dashboard')->post('/schedule', ['hours' => $hours])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('hours', $hours));
        $updated = [['weekday' => 3, 'starts_at' => '11:45', 'ends_at' => '18:00']];
        $this->post('/schedule', ['hours' => $updated])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('hours', $updated));
        $this->assertSame(1, DB::table('master_working_hours')->where('master_profile_id', $other->id)->count());
        $this->post('/schedule', ['hours' => []])->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->has('hours', 0));
    }

    public function test_cabinet_updates_accept_post_without_bypassing_ownership(): void
    {
        [$user, $master, $service] = $this->master();
        [, , $otherService] = $this->master('other-owner');
        $this->actingAs($user)->post('/profile', [
            'display_name' => 'Новое имя', 'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true, 'auto_confirm' => false,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_profiles', ['id' => $master->id, 'display_name' => 'Новое имя']);
        $data = ['name' => 'Новая услуга', 'duration_minutes' => 90, 'price_som' => 1200, 'is_active' => true];
        $this->post('/services/'.$service->id, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_services', ['id' => $service->id, 'duration_minutes' => 90]);
        $this->post('/services/'.$otherService->id, $data)->assertNotFound();
        $appointment = app(BookAppointment::class)->create($master, $service->fresh(),
            CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Клиент', '+996555123456');
        $this->post('/appointments/'.$appointment->id, ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
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
