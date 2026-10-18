<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Illuminate\Support\Facades\Storage;

class MasterImageController extends Controller
{
    public function __invoke(MasterProfile $master, string $kind, string $version)
    {
        $path = match ($kind) {
            'avatar' => $master->avatar_path, 'cover' => $master->cover_path,
            'work1' => $master->work1_path, 'work2' => $master->work2_path, 'work3' => $master->work3_path, default => null
        };
        abort_unless($path && preg_match('~^profile-images/'.$master->id.'/[A-Za-z0-9]{40}\.(?:jpg|jpeg|png|webp)$~D', $path) && pathinfo($path, PATHINFO_FILENAME) === $version, 404);
        $disk = Storage::disk('local');
        $root = realpath($disk->path('profile-images/'.$master->id));
        $absolute = $disk->path($path);
        $resolved = realpath($absolute);
        abort_unless($root && $resolved && ! is_link($absolute) && ! is_link($disk->path('profile-images'))
            && ! is_link($disk->path('profile-images/'.$master->id)) && dirname($resolved) === $root && is_file($resolved), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($resolved, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox", 'Cross-Origin-Resource-Policy' => 'same-origin', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
