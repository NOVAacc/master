<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use App\Services\WhatsApp;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-18 00:00:00', 'UTC'));
        Http::preventStrayRequests();
    }

    private function fixture(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'edit-master', 'display_name' => 'Айзада', 'timezone' => 'Asia/Bishkek', 'phone' => '+996555111222', 'auto_confirm' => true, 'whatsapp_notifications' => true]);
        $service = $master->services()->create(['name' => 'Ресницы', 'duration_minutes' => 60, 'price_som' => 1200, 'is_active' => true]);
        foreach ([1, 2] as $weekday) {
            DB::table('master_working_hours')->insert(['master_profile_id' => $master->id, 'weekday' => $weekday, 'starts_at' => '09:00', 'ends_at' => '18:00']);
        }
        $appointment = app(BookAppointment::class)->create($master, $service, CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC'), 'Клиент', '+996700333444', null, true);

        return [$user, $master, $service, $appointment];
    }

    private function payload($appointment): array
    {
        return ['revision' => $appointment->revision, 'starts_at_local' => '2026-10-19T11:00', 'duration_minutes' => 90, 'price_som' => 1500,
            'service_ids' => array_column($appointment->services_snapshot, 'id'), 'client_name' => 'Новое имя', 'client_phone' => $appointment->client_phone,
            'client_comment' => 'Пожелания клиента', 'reason' => 'Клиент попросил позже'];
    }

    public function test_reschedule_changes_duration_price_and_client_and_records_history(): void
    {
        [$user, $master, , $a] = $this->fixture();
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $this->payload($a))->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard?calendar_date=2026-10-19');
        $a->refresh();
        $this->assertSame('2026-10-19 05:00:00', $a->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-19 06:30:00', $a->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(1500, $a->price_som);
        $this->assertSame('Новое имя', $a->client_name);
        $this->assertSame('confirmed', $a->status);
        $this->assertSame(2, $a->revision);
        $this->assertSame(2, $a->notification_revision);
        $history = $a->changes()->sole();
        $this->assertSame(60, $history->before['duration_minutes']);
        $this->assertSame(90, $history->after['duration_minutes']);
        $this->assertSame('Клиент попросил позже', $history->reason);
        $this->assertStringContainsString('11:00–12:30', app(WhatsApp::class)->message($a, 'client'));
        $this->assertSame(1, $master->appointments()->count());
        $this->get('/m/edit-master?date=2026-10-19')->assertInertia(fn (Assert $page) => $page
            ->where('slots', fn ($slots) => collect($slots)->contains('label', '10:00')
                && ! collect($slots)->contains('label', '11:00') && collect($slots)->contains('label', '12:30')));
    }

    public function test_conflicts_closed_time_and_outside_hours_leave_original_booking_untouched(): void
    {
        [$user, $master, $service, $a] = $this->fixture();
        app(BookAppointment::class)->create($master, $service, CarbonImmutable::parse('2026-10-19 06:00:00', 'UTC'), 'Другой', '+996555555555');
        $data = $this->payload($a);
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('starts_at_local');
        $this->assertSame(1, $a->fresh()->revision);
        $this->assertDatabaseCount('master_appointment_changes', 0);
        $data['starts_at_local'] = '2026-10-19T17:30';
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('starts_at_local');
        DB::table('master_time_blocks')->insert(['master_profile_id' => $master->id, 'starts_at' => '2026-10-20 04:00:00', 'ends_at' => '2026-10-20 05:00:00']);
        $data['starts_at_local'] = '2026-10-20T10:00';
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('starts_at_local');
        $data['starts_at_local'] = '2026-10-19T11:00';
        $data['duration_minutes'] = 60;
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $this->assertSame('2026-10-19 06:00:00', $a->fresh()->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_ongoing_duration_can_change_but_moves_into_the_past_cannot(): void
    {
        [$user, , , $a] = $this->fixture();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 04:30:00', 'UTC'));
        $data = $this->payload($a);
        $data['starts_at_local'] = '2026-10-19T10:00';
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $data['revision'] = 2;
        $data['starts_at_local'] = '2026-10-19T10:15';
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('starts_at_local');
    }

    public function test_stale_edits_foreign_ownership_and_terminal_bookings_are_rejected(): void
    {
        [$user, , , $a] = $this->fixture();
        $data = $this->payload($a);
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('booking');
        $other = User::factory()->create();
        MasterProfile::create(['user_id' => $other->id, 'slug' => 'edit-other', 'display_name' => 'Другой']);
        $this->actingAs($other)->post('/appointments/'.$a->id.'/edit', $data)->assertNotFound();
        $a->refresh()->update(['status' => 'cancelled']);
        $data['revision'] = $a->revision;
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('booking');
    }

    public function test_service_change_preserves_existing_snapshot_and_rejects_foreign_services(): void
    {
        [$user, $master, $service, $a] = $this->fixture();
        $extra = $master->services()->create(['name' => 'Снятие', 'duration_minutes' => 30, 'price_som' => 300, 'is_active' => true]);
        $service->update(['name' => 'Переименовано', 'price_som' => 2000, 'is_active' => false]);
        $data = $this->payload($a);
        $data['service_ids'][] = $extra->id;
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $this->assertSame('Ресницы + Снятие', $a->fresh()->service_name_snapshot);
        $this->assertSame(1200, $a->fresh()->services_snapshot[0]['price_som']);
        $other = User::factory()->create();
        $foreign = MasterProfile::create(['user_id' => $other->id, 'slug' => 'foreign-edit', 'display_name' => 'Другой']);
        $foreignService = $foreign->services()->create(['name' => 'Чужая', 'duration_minutes' => 60, 'price_som' => 10, 'is_active' => true]);
        $data['revision'] = 2;
        $data['service_ids'] = [$foreignService->id];
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasErrors('service_ids');
    }

    public function test_comment_only_changes_and_unchanged_saves_do_not_send_duplicate_confirmations(): void
    {
        config(['services.whatsapp' => ['enabled' => true, 'token' => 'test', 'phone_number_id' => '123', 'version' => 'v23.0', 'language' => 'ru', 'client_template' => 'client', 'master_template' => 'master']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.comment']]], 200)]);
        [$user, , , $a] = $this->fixture();
        $data = $this->payload($a);
        $data['starts_at_local'] = '2026-10-19T10:00';
        $data['duration_minutes'] = 60;
        $data['price_som'] = 1200;
        $data['client_name'] = $a->client_name;
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame(2, $a->revision);
        $this->assertSame(1, $a->notification_revision);
        Http::assertSentCount(2);
        $data['revision'] = 2;
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        $this->assertSame(2, $a->fresh()->revision);
        $this->assertSame(1, $a->changes()->count());
        Http::assertSentCount(2);
    }

    public function test_notification_versions_send_updated_data_once_and_new_phone_revokes_consent(): void
    {
        config(['services.whatsapp' => ['enabled' => true, 'token' => 'test', 'phone_number_id' => '123', 'version' => 'v23.0', 'language' => 'ru', 'client_template' => 'client', 'master_template' => 'master']]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.edit']]], 200)]);
        [$user, , , $a] = $this->fixture();
        Http::assertSentCount(2);
        $data = $this->payload($a);
        $this->actingAs($user)->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        Http::assertSentCount(4);
        $a->refresh();
        app(WhatsApp::class)->confirmed($a);
        Http::assertSentCount(4);
        $data['revision'] = 2;
        $data['client_phone'] = '+996700999888';
        $this->post('/appointments/'.$a->id.'/edit', $data)->assertSessionHasNoErrors();
        Http::assertSentCount(5);
        $this->assertFalse($a->fresh()->whatsapp_consent);
    }
}
