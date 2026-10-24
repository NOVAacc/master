<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoryStyleController extends Controller
{
    public function __invoke(Request $request)
    {
        $permitted = [];
        foreach (StoryTemplateAdminController::COLLECTIONS as $collection) {
            foreach ($collection['ids'] as $id) {
                $permitted[] = $collection['slug'].':'.$id;
            }
        }
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        if ($master->industry && $master->activity
            && \App\Support\CategoryStoryCatalog::hasCategory($master->industry, $master->activity)) {
            foreach (array_keys(\App\Support\CategoryStoryCatalog::ROLES) as $role) {
                $permitted[] = 'category:'.$master->industry.':'.$master->activity.':'.$role;
            }
        }
        $data = $request->validate([
            'style' => ['required_without:template', 'sometimes', Rule::in(['minimal','dark','soft','accent','premium'])],
            'template' => ['required_without:style', 'sometimes', 'string', 'max:64', Rule::in($permitted)],
        ]);
        
        if (array_key_exists('style', $data)) $master->story_style = $data['style'];
        if (array_key_exists('template', $data)) $master->story_template = $data['template'];
        $master->save();

        return response()->json(['style' => $master->story_style, 'template' => $master->story_template]);
    }
}
