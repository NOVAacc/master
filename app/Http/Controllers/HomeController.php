<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\MasterAppointment;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        // The public homepage introduces BOSH; account discovery belongs in admin.
        // Never serialize master records into the homepage's Inertia payload.
        $statistics = Cache::remember('home.statistics.v1', 300, function () {
            $masters = MasterProfile::query()->where('is_demo', false)
                ->whereHas('user', fn ($query) => $query->whereNotNull('email_verified_at'));

            return [
                'masters' => (clone $masters)->count(),
                'appointments' => MasterAppointment::query()
                    ->whereIn('master_profile_id', (clone $masters)->select('id'))
                    ->where('status', '!=', 'cancelled')->count(),
                'updatedAt' => now()->timezone('Asia/Bishkek')->toIso8601String(),
            ];
        });

        return Inertia::render('Home', ['statistics' => $statistics]);
    }
}
