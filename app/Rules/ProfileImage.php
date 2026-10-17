<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ProfileImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Не удалось загрузить изображение.');

            return;
        }
        // Client names never become storage paths. Reject executable/double extensions anyway.
        $name = $value->getClientOriginalName();
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $types = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP];
        $info = @getimagesize($value->getPathname());
        if (! preg_match('~^[^./\\\\:\x00-\x1f\x7f]+\.(?:jpe?g|png|webp)$~iD', $name)
            || ! isset($types[$extension]) || ! $info || $info[2] !== $types[$extension]
            || $info[0] * $info[1] > 12000000) {
            $fail('Используйте изображение JPG, PNG или WebP до 12 мегапикселей с обычным именем файла.');
        }
    }
}
