<?php
namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TimeBlockTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-14T00:00:00Z'));
        $this->travelTo(CarbonImmutable::now('UTC'));
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id'=>$user->id, 'slug'=>'closed-'.$user->id,
            'display_name'=>'Master', 'timezone'=>'Asia/Bishkek', 'booking_enabled'=>true, 'show_busy_slots'=>true]);
        $service = $master->services()->create(['name'=>'Service', 'duration_minutes'=>60, 'price_som'=>500, 'is_active'=>true]);
        DB::table('master_working_hours')->insert(['master_profile_id'=>$master->id,'weekday'=>6,'starts_at'=>'09:00','ends_at'=>'18:00']);
        return [$user,$master,$service];
    }
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function interval(): array
    {
        return ['starts_at_local'=>'2026-11-14T10:00','ends_at_local'=>'2026-11-14T11:00','reason'=>'Private reason'];
    }
    public function test_master_can_close_time_hide_it_from_clients_and_reopen_it(): void
    {
        [$user,$master,$service] = $this->fixture();
        $this->actingAs($user)->from('/dashboard?calendar_date=2026-11-14')->post('/time-blocks',$this->interval())
            ->assertRedirect('/dashboard?calendar_date=2026-11-14')->assertSessionHasNoErrors();
        $block = DB::table('master_time_blocks')->first();
        $this->assertSame('2026-11-14 04:00:00',$block->starts_at);
        $this->assertSame('2026-11-14 05:00:00',$block->ends_at);
        $this->get('/m/'.$master->slug.'?date=2026-11-14')->assertInertia(fn (Assert $page) => $page
            ->where('slots',fn ($slots) => collect($slots)->pluck('label')->contains('09:00')
                && !collect($slots)->pluck('label')->contains('09:30') && !collect($slots)->pluck('label')->contains('10:00')
                && !collect($slots)->pluck('label')->contains('10:30') && collect($slots)->pluck('label')->contains('11:00')));
        $this->postJson('/m/'.$master->slug.'/book', ['service_id'=>$service->id, 'starts_at'=>'2026-11-14T04:00:00+00:00',
            'client_name'=>'Client','client_phone'=>'996555000000','consent'=>true])->assertUnprocessable()->assertJsonValidationErrors('starts_at')->assertJsonPath('errors.starts_at.0', 'Это время уже занято.');
        $this->post('/time-blocks/'.$block->id.'/remove')->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('master_time_blocks',['id'=>$block->id]);
        $this->get('/m/'.$master->slug.'?date=2026-11-14')->assertInertia(fn (Assert $page) => $page
            ->where('slots',fn ($slots) => collect($slots)->pluck('label')->contains('10:00')));
    }
    public function test_cannot_close_existing_booking_or_group_and_adjacent_time_is_allowed(): void
    {
        [$user,$master,$service] = $this->fixture();
        $a = app(BookAppointment::class)->create($master,$service,CarbonImmutable::parse('2026-11-14T04:00:00Z'),'Client','996555000000');
        $before = $a->status;
        $this->actingAs($user)->postJson('/time-blocks',$this->interval())->assertUnprocessable()->assertJsonValidationErrors('starts_at_local');
        $this->assertSame($before,$a->fresh()->status);
        $this->assertDatabaseCount('master_time_blocks',0);
        $this->post('/time-blocks',array_replace($this->interval(),['starts_at_local'=>'2026-11-14T11:00','ends_at_local'=>'2026-11-14T11:30']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('master_time_blocks',1);
        DB::table('master_group_sessions')->insert(['master_profile_id'=>$master->id,'master_service_id'=>$service->id,
            'starts_at'=>'2026-11-14 08:00:00','ends_at'=>'2026-11-14 09:00:00','capacity'=>4,'reserved_seats'=>0,
            'service_name_snapshot'=>'Group','price_som'=>500,'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
        $this->postJson('/time-blocks',array_replace($this->interval(),['starts_at_local'=>'2026-11-14T14:00','ends_at_local'=>'2026-11-14T15:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('starts_at_local');
    }
    public function test_validation_ownership_and_overnight_timezone(): void
    {
        [$user,$master] = $this->fixture();
        $this->postJson('/time-blocks',$this->interval())->assertUnauthorized();
        $this->actingAs($user)->postJson('/time-blocks',array_replace($this->interval(),['ends_at_local'=>'2026-11-14T09:00']))->assertUnprocessable();
        $this->postJson('/time-blocks',array_replace($this->interval(),['starts_at_local'=>'2026-11-13T10:00']))->assertUnprocessable();
        $this->postJson('/time-blocks',array_replace($this->interval(),['ends_at_local'=>'2027-02-14T11:00']))->assertUnprocessable();
        $this->post('/time-blocks',array_replace($this->interval(),['starts_at_local'=>'2026-11-14T23:30','ends_at_local'=>'2026-11-15T00:30']))->assertSessionHasNoErrors();
        $block = DB::table('master_time_blocks')->first();
        $this->assertSame('2026-11-14 17:30:00',$block->starts_at);
        $this->assertSame('2026-11-14 18:30:00',$block->ends_at);
        $other = User::factory()->create();
        MasterProfile::create(['user_id'=>$other->id,'slug'=>'other','display_name'=>'Other','timezone'=>'Asia/Bishkek']);
        $this->actingAs($other)->postJson('/time-blocks/'.$block->id.'/remove')->assertNotFound();
        $this->assertDatabaseHas('master_time_blocks',['id'=>$block->id,'master_profile_id'=>$master->id]);
        $this->actingAs($user)->postJson('/time-blocks',array_replace($this->interval(),['starts_at_local'=>'2026-11-14T23:45','ends_at_local'=>'2026-11-15T01:00']))->assertUnprocessable();
        $this->assertDatabaseCount('master_time_blocks',1);
    }
}
