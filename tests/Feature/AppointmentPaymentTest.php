<?php
namespace Tests\Feature;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppointmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'payment-'.$user->id, 'display_name' => 'Master', 'timezone' => 'Asia/Bishkek']);
        $service = $master->services()->create(['name' => 'Service', 'duration_minutes' => 60, 'price_som' => 500, 'is_active' => true, 'sort_order' => 0]);
        $start = now('Asia/Bishkek')->startOfDay()->addHours(12)->utc();
        $appointment = $master->appointments()->create([
            'master_service_id' => $service->id, 'client_name' => 'Client', 'client_phone' => '996555000000',
            'service_name_snapshot' => 'Service', 'price_som' => 500, 'status' => 'confirmed',
            'starts_at' => $start, 'ends_at' => $start->copy()->addHour(),
        ]);
        return [$user, $appointment];
    }

    public function test_paid_switch_persists_and_is_independent_of_booking_state(): void
    {
        [$user, $appointment] = $this->fixture();
        $before = $appointment->fresh();
        $this->actingAs($user)->postJson('/appointments/'.$appointment->id.'/payment', [
            'paid' => true, 'status' => 'cancelled', 'price_som' => 0,
        ])->assertOk()->assertJson(['id' => $appointment->id, 'payment_status' => 'paid']);
        $after = $appointment->fresh();
        $this->assertSame('paid', $after->payment_status);
        foreach (['status', 'price_som', 'revision', 'notification_revision', 'attendance_status'] as $field) $this->assertSame($before->$field, $after->$field);
        $this->assertTrue($before->starts_at->equalTo($after->starts_at));
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('calendarAppointments.0.payment_status', 'paid')->where('appointments.0.payment_status', 'paid'));
        $this->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => false])->assertOk()->assertJsonPath('payment_status', 'unpaid');
        $this->assertSame('unpaid', $appointment->fresh()->payment_status);
    }

    public function test_guest_and_other_master_cannot_change_payment(): void
    {
        [$owner, $appointment] = $this->fixture();
        $this->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => true])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => true])->assertNotFound();
        $this->assertNull($appointment->fresh()->payment_status);
    }

    public function test_validation_and_terminal_bookings_preserve_recorded_payment(): void
    {
        [$owner, $appointment] = $this->fixture();
        $this->actingAs($owner)->postJson('/appointments/'.$appointment->id.'/payment', [])->assertUnprocessable();
        $this->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => 'wrong'])->assertUnprocessable();
        $appointment->update(['status' => 'completed']);
        $this->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => true])->assertOk();
        $this->postJson('/appointments/'.$appointment->id.'/payment', ['paid' => true])->assertOk();
        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame('paid', $appointment->fresh()->payment_status);
    }
}
