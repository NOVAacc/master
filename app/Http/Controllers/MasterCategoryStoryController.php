<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Support\CategoryStoryCatalog;
use Illuminate\Http\Request;

class MasterCategoryStoryController extends Controller
{
    private function master(Request $request): MasterProfile
    {
        return MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function catalog(Request $request)
    {
        $master = $this->master($request);
        $industry = (string) $request->query('industry', $master->industry ?? '');
        $activity = (string) $request->query('activity', $master->activity ?? '');
        $category = CategoryStoryCatalog::forCategory($industry, $activity);
        $groups = [];
        foreach (config('industries', []) as $id => $definition) {
            $groups[] = ['id' => $id, 'name' => $definition['name'], 'activities' => $definition['activities']];
        }
        if (! $category) {
            return response()->json(['category' => null, 'templates' => [], 'groups' => $groups]);
        }
        $templates = [];
        foreach ($category['templates'] as $role) {
            $config = CategoryStoryTemplateController::config($industry, $activity, $role['id']);
            $version = $config['image_url'] ? basename($config['image_url']) : null;
            // The admin-only URL is never sent to the master.
            $config['image_url'] = $version ? route('stories.category.image', [
                'industry' => $industry, 'activity' => $activity,
                'role' => $role['id'], 'version' => $version,
            ]) : null;
            $templates[] = array_merge($role, $config);
        }

        return response()->json([
            'category' => [
                'industry' => $industry,
                'activity' => $activity,
                'name' => $category['activity_name'],
            ],
            'templates' => $templates,
            'groups' => $groups,
        ]);
    }

    public function image(Request $request, string $industry, string $activity, string $role, string $version)
    {
        $this->master($request);
        abort_unless(CategoryStoryTemplateController::allowed($industry, $activity, $role), 404);

        // Reuse the path integrity and current-version checks in the admin controller.
        // It does not require the admin guard itself; this route already verifies the authenticated master.
        return app(CategoryStoryTemplateController::class)->image($industry, $activity, $role, $version);
    }
}
