<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentBookingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_concurrent_requests_cannot_book_the_same_master_slot(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL row locking is exercised by the CI database matrix.');
        }
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'concurrent', 'display_name' => 'Concurrent']);
        $service = $master->services()->create(['name' => 'Service', 'duration_minutes' => 60, 'price_som' => 100]);
        $start = CarbonImmutable::now('Asia/Bishkek')->addDay()->startOfDay()->setTime(10, 0);
        DB::table('master_working_hours')->insert(['master_profile_id' => $master->id,
            'weekday' => $start->isoWeekday(), 'starts_at' => '09:00:00', 'ends_at' => '18:00:00']);
        $barrier = sys_get_temp_dir().'/master-booking-'.bin2hex(random_bytes(10));
        $connection = config('database.connections.mysql');
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
            'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password']];
        $workers = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $workers[] = $worker = new Process([PHP_BINARY, base_path('tests/Support/book-worker.php'),
                    (string) $master->id, (string) $service->id, $start->toIso8601String(), $barrier], base_path(), $env, timeout: 20);
                $worker->start();
            }
            touch($barrier);
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
                $results[] = trim($worker->getOutput());
            }
            sort($results);
            $this->assertSame(['created', 'rejected'], $results);
            $this->assertDatabaseCount('master_appointments', 1);
        } finally {
            foreach ($workers as $worker) {
                $worker->stop();
            }
            if (file_exists($barrier)) {
                unlink($barrier);
            }
        }
    }
}
