<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SafeProfileImage
{
    /**
     * Decode and re-encode uploaded image pixels. Never publish original bytes
     * (which can include active polyglot payloads, appended code or metadata).
     */
    public static function store(UploadedFile $file, int $masterId): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                $file->getClientOriginalName() => 'Обработка изображений временно недоступна. Обратитесь в поддержку.',
            ]);
        }

        $info = @getimagesize($file->getRealPath());
        $formats = [
            IMAGETYPE_JPEG => ['extension' => 'jpg', 'encoder' => 'imagejpeg'],
            IMAGETYPE_PNG => ['extension' => 'png', 'encoder' => 'imagepng'],
            IMAGETYPE_WEBP => ['extension' => 'webp', 'encoder' => 'imagewebp'],
        ];
        if (! $info || ! isset($formats[$info[2]]) || $info[0] <= 0 || $info[1] <= 0
            || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages(['avatar' => 'Недопустимое изображение.']);
        }

        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['avatar' => 'Не удалось прочитать изображение.']);
        }
        $temp = tmpfile();
        if (! $temp) {
            imagedestroy($image);
            throw new \RuntimeException('Could not allocate temporary image file.');
        }

        try {
            imagesavealpha($image, true);
            $encoded = match ($info[2]) {
                IMAGETYPE_JPEG => imagejpeg($image, $temp, 85),
                IMAGETYPE_PNG => imagepng($image, $temp, 6),
                IMAGETYPE_WEBP => imagewebp($image, $temp, 85),
            };
            if (! $encoded || ! isset(fstat($temp)['size']) || fstat($temp)['size'] > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(['avatar' => 'Изображение слишком большое после обработки.']);
            }
            rewind($temp);
            $path = 'profile-images/'.$masterId.'/'.Str::random(40).'.'.$formats[$info[2]]['extension'];
            if (! Storage::disk('local')->put($path, $temp)) {
                throw new \RuntimeException('Failed to save sanitized profile image.');
            }
            return $path;
        } finally {
            fclose($temp);
            imagedestroy($image);
        }
    }
}
