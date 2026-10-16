<?php

use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script, $masterId, $serviceId, $start, $barrier] = $argv;
$deadline = microtime(true) + 10;
while (! file_exists($barrier)) {
    if (microtime(true) > $deadline) {
        exit(2);
    }
    usleep(10000);
}
try {
    app(BookAppointment::class)->create(MasterProfile::findOrFail($masterId), MasterService::findOrFail($serviceId),
        CarbonImmutable::parse($start)->utc(), 'Concurrent client', '+996555123456');
    echo 'created';
} catch (ValidationException $e) {
    echo 'rejected';
}
