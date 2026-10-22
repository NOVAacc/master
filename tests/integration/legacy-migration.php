<?php

// Executed before/after new migrations in a DISPOSABLE MySQL CI database.
// It simulates an existing master and appointment written by the old schema,
// never connects to production, and never prints any real customer details.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$mode = $argv[1] ?? '';
$slug = 'legacy-migration-ci';
$clientName = 'Historical Fixture';
$phone = '996555000444';

if (DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('A disposable MySQL database is required.');
}
if ($mode === 'downgrade') {
    // Keep this fixture at the pre-CRM boundary even as new migrations are added.
    $steps = DB::table('migrations')->where('migration', '>=', '2026_10_19_190000')->count();
    if ($steps > 0) {
        \Illuminate\Support\Facades\Artisan::call('migrate:rollback', ['--step' => $steps, '--force' => true]);
        echo \Illuminate\Support\Facades\Artisan::output();
    }
    exit(0);
}
if ($mode === 'seed') {
    if (Schema::hasTable('master_clients') || Schema::hasColumn('master_appointments', 'master_client_id')) {
        throw new RuntimeException('Refusing to seed: new CRM schema is already present.');
    }
    $user = User::factory()->create();
    $master = MasterProfile::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'display_name' => 'Legacy Fixture Master',
        'phone' => '996555000001',
        'timezone' => 'Asia/Bishkek',
        'booking_enabled' => true,
        'auto_confirm' => true,
    ]);
    $start = \Carbon\CarbonImmutable::now('UTC')->addDays(12)->startOfHour();
    DB::table('master_appointments')->insert([
        'master_profile_id' => $master->id,
        'client_name' => $clientName,
        'client_phone' => $phone,
        'service_name_snapshot' => 'Original historical service',
        'price_som' => 750,
        'starts_at' => $start->format('Y-m-d H:i:s'),
        'ends_at' => $start->addHour()->format('Y-m-d H:i:s'),
        'status' => 'confirmed',
        'whatsapp_consent' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "PASS: existing-schema booking inserted before upgrade.\n";
    exit(0);
}
if ($mode === 'verify') {
    $master = MasterProfile::where('slug', $slug)->firstOrFail();
    $row = DB::table('master_appointments')
        ->where('master_profile_id', $master->id)
        ->where('client_name', $clientName)->first();
    if (! $row || $row->client_phone !== $phone || $row->price_som != 750
        || $row->status !== 'confirmed'
        || $row->service_name_snapshot !== 'Original historical service'
        || $row->master_client_id !== null
        || $row->attendance_status !== null || $row->payment_status !== null
        || (int) $row->customer_reschedule_count !== 0) {
        throw new RuntimeException('Existing booking was lost or reinterpreted during schema upgrade.');
    }
    if (DB::table('master_clients')->where('master_profile_id', $master->id)->exists()) {
        throw new RuntimeException('Schema upgrade unexpectedly mutated the historical CRM.');
    }
    echo "PASS: existing appointment, price and status survived migration; new fields are safe defaults.\n";
    exit(0);
}
throw new InvalidArgumentException('Usage: php legacy-migration.php downgrade|seed|verify');
