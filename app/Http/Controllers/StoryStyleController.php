<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoryStyleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'style' => ['required_without:template', 'sometimes', Rule::in(['minimal','dark','soft','accent','premium'])],
            'template' => ['required_without:style', 'sometimes', 'string', 'max:64', Rule::in([
                'bosh-nail-trends:nude','bosh-nail-trends:floral','bosh-nail-trends:luxury','bosh-nail-trends:red',
                'bosh-nail-trends:bridal','bosh-nail-trends:cozy','bosh-nail-trends:urban','bosh-nail-trends:chrome',
                'bosh-hair-trends:nude','bosh-hair-trends:romantic','bosh-hair-trends:brunette','bosh-hair-trends:bold',
                'bosh-hair-trends:bridal','bosh-hair-trends:cozy','bosh-hair-trends:urban','bosh-hair-trends:chrome',
                'bosh-brow-trends:nude','bosh-brow-trends:soft-pink','bosh-brow-trends:luxury','bosh-brow-trends:bold',
                'bosh-brow-trends:natural','bosh-brow-trends:cozy','bosh-brow-trends:urban','bosh-brow-trends:editorial',
            ])],
        ]);
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        if (array_key_exists('style', $data)) $master->story_style = $data['style'];
        if (array_key_exists('template', $data)) $master->story_template = $data['template'];
        $master->save();

        return response()->json(['style' => $master->story_style, 'template' => $master->story_template]);
    }
}
