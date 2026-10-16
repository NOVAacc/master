<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $profiles = MasterProfile::query()
            ->select(['id', 'slug', 'display_name', 'specialty', 'address'])
            ->where('booking_enabled', true)
            ->whereHas('services', fn (Builder $query) => $query->where('is_active', true))
            ->withMin(['services as price_from' => fn (Builder $query) => $query->where('is_active', true)], 'price_som')
            ->withCount(['appointments as completed_count' => fn (Builder $query) => $query->where('status', 'completed')]);

        $publicFields = fn ($master) => [
            'slug' => $master->slug,
            'display_name' => $master->display_name,
            'specialty' => $master->specialty,
            'address' => $master->address,
            'price_from' => (int) $master->price_from,
            'completed_count' => (int) $master->completed_count,
        ];

        return Inertia::render('Home', [
            'newMasters' => (clone $profiles)->orderByDesc('created_at')->orderByDesc('id')->limit(12)->get()->map($publicFields),
            'topMasters' => (clone $profiles)
                ->whereHas('appointments', fn (Builder $query) => $query->where('status', 'completed'))
                ->orderByDesc('completed_count')->orderByDesc('id')->limit(6)->get()->map($publicFields),
        ]);
    }
}
