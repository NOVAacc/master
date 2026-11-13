<?php
namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientNotesTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'notes-'.$user->id, 'display_name' => 'Master', 'timezone' => 'Asia/Bishkek']);
        $client = DB::table('master_clients')->insertGetId(['master_profile_id' => $master->id, 'name' => 'Эрика', 'phone_digits' => '996555000000', 'notes' => 'Before', 'created_at' => now(), 'updated_at' => now()]);
        return [$user, $client];
    }

    public function test_post_saves_partial_notes_and_reopening_reads_persisted_text(): void
    {
        [$user, $client] = $this->fixture();
        $notes = "Предпочитает вечернее время.\nКыргызча эскертме.";
        $this->actingAs($user)->postJson('/clients/'.$client, ['notes' => $notes])->assertOk()->assertJsonPath('saved', true)->assertJsonPath('client.notes', $notes)->assertJsonPath('client.name', 'Эрика');
        $this->getJson('/clients/'.$client)->assertOk()->assertJsonPath('client.notes', $notes);
        $this->assertDatabaseHas('master_clients', ['id' => $client, 'notes' => $notes, 'name' => 'Эрика', 'phone_digits' => '996555000000']);
        $this->postJson('/clients/'.$client, ['notes' => ''])->assertOk()->assertJsonPath('client.notes', null);
        $this->getJson('/clients/'.$client)->assertJsonPath('client.notes', null);
    }

    public function test_put_remains_compatible_and_oversized_notes_do_not_overwrite_saved_text(): void
    {
        [$user, $client] = $this->fixture();
        $this->actingAs($user)->putJson('/clients/'.$client, ['notes' => 'Updated'])->assertOk()->assertJsonPath('client.notes', 'Updated');
        $this->postJson('/clients/'.$client, ['notes' => str_repeat('я', 2001)])->assertUnprocessable()->assertJsonValidationErrors('notes');
        $this->assertDatabaseHas('master_clients', ['id' => $client, 'notes' => 'Updated']);
    }

    public function test_guests_and_other_masters_cannot_modify_private_notes(): void
    {
        [$owner, $client] = $this->fixture();
        $this->postJson('/clients/'.$client, ['notes' => 'Unauthorized'])->assertUnauthorized();
        [$other] = $this->fixture();
        $this->actingAs($other)->postJson('/clients/'.$client, ['notes' => 'Foreign'])->assertNotFound();
        $this->assertDatabaseHas('master_clients', ['id' => $client, 'notes' => 'Before']);
    }
}
