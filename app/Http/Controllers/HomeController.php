<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $industries = config('industries');
        $industry = $request->query('industry');
        $activities = is_string($industry) ? ($industries[$industry]['activities'] ?? []) : [];
        $filters = $request->validate([
            'industry' => ['nullable', 'string', Rule::in(array_keys($industries))],
            'activity' => ['nullable', 'string', Rule::in(array_keys($activities))],
        ]);
        $profiles = MasterProfile::query()
            ->select(['id', 'slug', 'display_name', 'specialty', 'address', 'avatar_path', 'cover_path', 'profile_theme', 'is_demo', 'industry', 'activity'])
            ->where(fn (Builder $query) => $query->where('booking_enabled', true)->orWhere('is_demo', true))
            ->when($filters['industry'] ?? null, fn (Builder $q, $value) => $q->where('industry', $value))
            ->when($filters['activity'] ?? null, fn (Builder $q, $value) => $q->where('activity', $value))
            ->whereHas('services', fn (Builder $query) => $query->where('is_active', true))
            ->withMin(['services as price_from' => fn (Builder $query) => $query->where('is_active', true)], 'price_som')
            ->withCount(['appointments as completed_count' => fn (Builder $query) => $query->where('status', 'completed')]);

        $publicFields = fn ($master) => [
            'slug' => $master->slug,
            'is_demo' => (bool) $master->is_demo,
            'industry' => $master->industry,
            'activity' => $master->activity,
            'display_name' => $master->display_name,
            'specialty' => $master->specialty,
            'address' => $master->address,
            'avatar_url' => $master->avatar_url,
            'cover_url' => $master->cover_url,
            'profile_theme' => $master->profile_theme,
            'price_from' => (int) $master->price_from,
            'completed_count' => (int) $master->completed_count,
        ];

        return Inertia::render('Home', [
            'industries' => $industries,
            'filters' => ['industry' => $filters['industry'] ?? '', 'activity' => $filters['activity'] ?? ''],
            'newMasters' => (clone $profiles)->orderByDesc('created_at')->orderByDesc('id')->limit(12)->get()->map($publicFields),
            'topMasters' => (clone $profiles)
                ->whereHas('appointments', fn (Builder $query) => $query->where('status', 'completed'))
                ->orderByDesc('completed_count')->orderByDesc('id')->limit(6)->get()->map($publicFields),
        ]);
    }
}
