<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoryStyleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['style' => ['required', Rule::in(['minimal', 'dark', 'soft', 'accent', 'premium'])]]);
        MasterProfile::where('user_id', $request->user()->id)->firstOrFail()->update(['story_style' => $data['style']]);

        return response()->json(['style' => $data['style']]);
    }
}
