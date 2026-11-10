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
    public static function store(UploadedFile $file, int $masterId, string $field): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                $field => 'Обработка изображений временно недоступна. Обратитесь в поддержку.',
            ]);
        }

        $info = @getimagesize($file->getRealPath());
        $formats = [
            IMAGETYPE_JPEG => ['extension' => 'jpg'],
            IMAGETYPE_PNG => ['extension' => 'png'],
            IMAGETYPE_WEBP => ['extension' => 'webp'],
        ];
        if (! $info || ! isset($formats[$info[2]]) || ($info[2] === IMAGETYPE_WEBP && ! function_exists('imagewebp')) || $info[0] <= 0 || $info[1] <= 0
            || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages([$field => 'Недопустимое изображение.']);
        }

        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages([$field => 'Не удалось прочитать изображение.']);
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
            $outputSize = fstat($temp)['size'] ?? 0;
            if (! $encoded || $outputSize === 0 || $outputSize > 5 * 1024 * 1024) {
                throw ValidationException::withMessages([$field => 'Изображение слишком большое после обработки.']);
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
