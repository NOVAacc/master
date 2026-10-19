<?php

// Standalone MySQL integration test. Called only after the CI database has
// been migrated. Two distinct PHP processes race for the final group seat.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\ReserveGroupSeat;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

if (($argv[1] ?? '') === '--child') {
    [, , $sessionId, $name, $phone, $barrier] = $argv;
    $until = microtime(true) + 10;
    while (! file_exists($barrier) && microtime(true) < $until) {
        usleep(10000);
    }
    if (! file_exists($barrier)) {
        fwrite(STDERR, "Barrier timeout\n");
        exit(3);
    }
    try {
        app(ReserveGroupSeat::class)->reserve((int) $sessionId, $name, $phone);
        echo "CLAIMED\n";
        exit(0);
    } catch (ValidationException $e) {
        echo "REJECTED\n";
        exit(2);
    }
}

if (DB::getDriverName() !== 'mysql') {
    fwrite(STDERR, "MySQL is required for this concurrency test.\n");
    exit(3);
}

$user = User::factory()->create();
$master = MasterProfile::create([
    'user_id' => $user->id, 'slug' => 'race'.bin2hex(random_bytes(4)),
    'display_name' => 'Race Test', 'phone' => '996555123456',
    'timezone' => 'Asia/Bishkek',
    'booking_enabled' => true, 'auto_confirm' => true, 'is_demo' => false,
]);
$service = $master->services()->create([
    'name' => 'Race Group', 'description' => '', 'duration_minutes' => 60,
    'price_som' => 100, 'is_active' => true,
]);
$start = CarbonImmutable::now('UTC')->addDays(4)->startOfHour();
$sessionId = DB::table('master_group_sessions')->insertGetId([
    'master_profile_id' => $master->id, 'master_service_id' => $service->id,
    'starts_at' => $start->format('Y-m-d H:i:s'),
    'ends_at' => $start->addHour()->format('Y-m-d H:i:s'),
    'capacity' => 1, 'reserved_seats' => 0,
    'service_name_snapshot' => $service->name, 'price_som' => 100,
    'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
]);

$barrier = tempnam(sys_get_temp_dir(), 'bosh-race-');
if ($barrier === false) {
    throw new RuntimeException('Cannot create barrier');
}
unlink($barrier);
$workers = [];

try {
    foreach ([
        ['ClientA', '996555333111'],
        ['ClientB', '996555333222'],
    ] as [$name, $phone]) {
        $command = [
            PHP_BINARY, __FILE__, '--child', (string) $sessionId,
            $name, $phone, $barrier,
        ];
        $pipes = [];
        $process = proc_open($command, [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, dirname(__DIR__, 2));
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to spawn second independent PHP process');
        }
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    // Release both processes into the same critical section.
    usleep(150000);
    touch($barrier);
    $codes = [];
    foreach ($workers as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        echo trim($stdout)." (exit ".$code.")\n";
        if ($stderr !== '') {
            fwrite(STDERR, $stderr);
        }
        $codes[] = $code;
    }
    sort($codes);

    $row = DB::table('master_group_sessions')->where('id', $sessionId)->first();
    $count = DB::table('master_group_participants')
        ->where('master_group_session_id', $sessionId)->count();
    if ($codes !== [0, 2] || (int) $row->reserved_seats !== 1 || $count !== 1) {
        throw new RuntimeException('Concurrency invariant failed: '.json_encode([
            'codes' => $codes, 'reserved' => $row->reserved_seats, 'participants' => $count,
        ]));
    }
    echo "PASS: two simultaneous PHP processes reserved exactly one group seat.\n";
} finally {
    if (file_exists($barrier)) {
        unlink($barrier);
    }
    DB::table('master_group_sessions')->where('id', $sessionId)->delete();
    $service->delete();
    $master->delete();
    $user->delete();
}
