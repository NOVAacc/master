<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Support\Facades\Storage;

class MasterImageController extends Controller
{
    public function __invoke(MasterProfile $master, string $kind, string $version)
    {
        $path = match ($kind) {
            'avatar' => $master->avatar_path, 'cover' => $master->cover_path, default => null
        };
        abort_unless($path && str_starts_with($path, 'profile-images/'.$master->id.'/') && pathinfo($path, PATHINFO_FILENAME) === $version, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($disk->path($path), ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
