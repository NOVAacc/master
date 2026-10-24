<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Support\CategoryStoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterCategoryStoryController extends Controller
{
    private function master(Request $request): MasterProfile
    {
        return MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function catalog(Request $request)
    {
        $master = $this->master($request);
        $category = $master->industry && $master->activity
            ? CategoryStoryCatalog::forCategory($master->industry, $master->activity)
            : null;

        if (! $category) {
            return response()->json(['category' => null, 'templates' => []]);
        }
        $templates = [];
        foreach ($category['templates'] as $role) {
            $config = CategoryStoryTemplateController::config($master->industry, $master->activity, $role['id']);
            $version = $config['image_url'] ? basename($config['image_url']) : null;
            // The admin-only URL is never sent to the master.
            $config['image_url'] = $version ? route('stories.category.image', [
                'industry' => $master->industry, 'activity' => $master->activity,
                'role' => $role['id'], 'version' => $version,
            ]) : null;
            $templates[] = array_merge($role, $config);
        }

        return response()->json([
            'category' => [
                'industry' => $master->industry,
                'activity' => $master->activity,
                'name' => $category['activity_name'],
            ],
            'templates' => $templates,
        ]);
    }

    public function image(Request $request, string $industry, string $activity, string $role, string $version)
    {
        $master = $this->master($request);
        abort_unless($master->industry === $industry && $master->activity === $activity, 404);
        abort_unless(CategoryStoryTemplateController::allowed($industry, $activity, $role), 404);

        // Reuse the path integrity and current-version checks in the admin controller.
        // It does not require the admin guard itself; this route already verifies ownership.
        return app(CategoryStoryTemplateController::class)->image($industry, $activity, $role, $version);
    }
}
