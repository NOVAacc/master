<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RestrictUploads
{
    public function handle(Request $request, Closure $next)
    {
        $files = $request->allFiles();
        if (! $files) {
            return $next($request);
        }

        $profile = $request->is('profile') && in_array($request->method(), ['POST', 'PUT'], true);
        $story = $request->is('admin/stories/*') && $request->isMethod('POST')
            && preg_match('~^admin/stories/(?:nails|brows|hair)/[a-z-]+$~D', $request->path());

        if (! $profile && ! $story) {
            abort(422, 'Загрузка файлов здесь не поддерживается.');
        }

        $allowed = $profile ? ['avatar', 'cover', 'work1', 'work2', 'work3'] : ['image'];
        foreach ($files as $field => $file) {
            if (! in_array($field, $allowed, true) || is_array($file)) {
                throw ValidationException::withMessages([
                    $profile ? 'avatar' : 'image' => $profile
                        ? 'Допустимы аватар, обложка и три фотографии работ — по одному файлу в каждом поле.'
                        : 'Для шаблонов сторис допустимо одно изображение в поле image.',
                ]);
            }
        }

        return $next($request);
    }
}
