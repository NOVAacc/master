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
        if ($files && (! $request->is('profile') || ! in_array($request->method(), ['POST', 'PUT'], true))) {
            abort(422, 'Загрузка файлов здесь не поддерживается.');
        }
        foreach ($files as $field => $file) {
            if (! in_array($field, ['avatar', 'cover', 'work1', 'work2', 'work3'], true) || is_array($file)) {
                throw ValidationException::withMessages(['avatar' => 'Допустимы аватар, обложка и три фотографии работ — по одному файлу в каждом поле.']);
            }
        }

        return $next($request);
    }
}
