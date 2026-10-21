<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientMergeArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $slug): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'display_name' => $slug,
            'phone' => '996555222333',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => false,
            'auto_confirm' => false,
            'is_demo' => false,
        ]);
        return [$user, $master];
    }

    private function client(int $masterId, string $phone, string $notes): int
    {
        return DB::table('master_clients')->insertGetId([
            'master_profile_id' => $masterId,
            'name' => 'Клиент '.$phone,
            'phone_digits' => $phone,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function appointment(int $masterId, int $clientId): int
    {
        return DB::table('master_appointments')->insertGetId([
            'master_profile_id' => $masterId,
            'master_client_id' => $clientId,
            'client_name' => 'Клиент',
            'client_phone' => '996555000000',
            'starts_at' => now('UTC')->subDays(3),
            'ends_at' => now('UTC')->subDays(3)->addHour(),
            'price_som' => 600,
            'service_name_snapshot' => 'Услуга',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_merge_moves_appointments_and_preserves_notes(): void
    {
        [$user, $master] = $this->master('merge-test');
        $target = $this->client($master->id, '996555000001', 'Основная заметка');
        $source = $this->client($master->id, '996555000002', 'Вторая заметка');
        $appointment = $this->appointment($master->id, $source);

        $this->actingAs($user)->postJson("/clients/{$target}/merge", ['source_id' => $source])
            ->assertOk()->assertJson(['merged' => true]);
        $this->assertDatabaseHas('master_appointments', ['id' => $appointment, 'master_client_id' => $target, 'price_som' => 600]);
        $this->assertDatabaseMissing('master_clients', ['id' => $source]);
        $row = DB::table('master_clients')->where('id', $target)->first();
        $this->assertStringContainsString('Основная заметка', $row->notes);
        $this->assertStringContainsString('Вторая заметка', $row->notes);
    }

    public function test_other_masters_client_cannot_be_merged_or_archived(): void
    {
        [$user, $master] = $this->master('merge-owner');
        [, $other] = $this->master('merge-foreign');
        $target = $this->client($master->id, '996555000003', '');
        $foreign = $this->client($other->id, '996555000004', 'Private');
        $this->actingAs($user)->postJson("/clients/{$target}/merge", ['source_id' => $foreign])->assertNotFound();
        $this->deleteJson("/clients/{$foreign}")->assertNotFound();
        $this->assertDatabaseHas('master_clients', ['id' => $foreign, 'archived_at' => null]);
    }

    public function test_self_merge_is_rejected(): void
    {
        [$user, $master] = $this->master('self-merge');
        $client = $this->client($master->id, '996555000005', 'Note');
        $this->actingAs($user)->postJson("/clients/{$client}/merge", ['source_id' => $client])
            ->assertUnprocessable();
        $this->assertDatabaseHas('master_clients', ['id' => $client]);
    }

    public function test_archive_preserves_appointments_and_hides_client_from_search(): void
    {
        [$user, $master] = $this->master('archive-test');
        $client = $this->client($master->id, '996555000006', 'Note');
        $appointment = $this->appointment($master->id, $client);
        $this->actingAs($user)->deleteJson("/clients/{$client}")->assertOk()->assertJson(['archived' => true]);
        $this->assertDatabaseHas('master_appointments', ['id' => $appointment, 'master_client_id' => $client]);
        $this->assertNotNull(DB::table('master_clients')->where('id', $client)->value('archived_at'));
        $this->getJson('/appointments/clients?q=996555000006')->assertExactJson([]);
        $this->getJson("/clients/{$client}")->assertNotFound();
    }
}
