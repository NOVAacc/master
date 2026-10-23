<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Rules\ProfileImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoryTemplateAdminController extends Controller
{
    public const COLLECTIONS = [
        'nails' => ['title' => 'Маникюр', 'ids' => ['nude','floral','luxury','red','bridal','cozy','urban','chrome']],
        'brows' => ['title' => 'Брови', 'ids' => ['nude','soft-pink','luxury','bold','natural','cozy','urban','editorial']],
        'hair' => ['title' => 'Волосы', 'ids' => ['nude','romantic','brunette','bold','bridal','cozy','urban','chrome']],
    ];
    public const LABELS = [
        'nails' => ['nude'=>'Нюд','floral'=>'Цветочный','luxury'=>'Чёрное золото','red'=>'Красный акцент','bridal'=>'Невеста','cozy'=>'Тёплый уют','urban'=>'Городской','chrome'=>'Хром'],
        'brows' => ['nude'=>'Нюд','soft-pink'=>'Нежный розовый','luxury'=>'Премиум','bold'=>'Яркий акцент','natural'=>'Натуральный','cozy'=>'Тёплый уют','urban'=>'Городской стиль','editorial'=>'Fashion'],
        'hair' => ['nude'=>'Нюд','romantic'=>'Романтика','brunette'=>'Тёмный премиум','bold'=>'Яркое окрашивание','bridal'=>'Свадебный','cozy'=>'Тёплый сезон','urban'=>'Городской','chrome'=>'Креатив'],
    ];
    public const DEFAULT_PHOTOS = [
        'nails' => ['nude' => 'photo-1727199433231-346fd8101839', 'floral' => 'photo-1674383600495-bfa0405f3c93', 'luxury' => 'photo-1736434518489-0eb84070017f', 'red' => 'photo-1637851362556-46d07c374023', 'bridal' => 'photo-1680540441735-2cdc020037c8', 'cozy' => 'photo-1736434518489-0eb84070017f', 'urban' => 'photo-1727199433231-346fd8101839', 'chrome' => 'photo-1684609365994-a144ee021c88'],
        'brows' => ['nude' => 'photo-1516220362602-dba5272034e7', 'soft-pink' => 'photo-1728767593550-705d6ebbf1d6', 'luxury' => 'photo-1729886771704-3a9f5a613d40', 'bold' => 'photo-1738594399272-f05ad0a5ba8a', 'natural' => 'photo-1781503056004-53972080018b', 'cozy' => 'photo-1731284450430-484343eec537', 'urban' => 'photo-1725886260675-7156c4b56b84', 'editorial' => 'photo-1569597212855-f10721b2e1ac'],
        'hair' => ['nude' => 'photo-1562322140-8baeececf3df', 'romantic' => 'photo-1519699047748-de8e457a634e', 'brunette' => 'photo-1522337360788-8b13dee7a37e', 'bold' => 'photo-1607779097040-26e80aa78e66', 'bridal' => 'photo-1560869713-7d563336772e', 'cozy' => 'photo-1633681926022-84c23e8cb2d6', 'urban' => 'photo-1605497788044-5a32c7078486', 'chrome' => 'photo-1521590832167-7bcbfaa6381f']
    ];
    public const FONTS = ['system' => 'Современный', 'serif' => 'Редакционный', 'rounded' => 'Мягкий'];
    public const DEFAULTS = ['font' => 'system', 'scale' => 100, 'text_color' => '', 'focal_x' => 50, 'focal_y' => 50, 'title_x' => 0, 'title_y' => 0, 'details_x' => 0, 'details_y' => 0];

    private static function settingKey(string $theme, string $template): string
    {
        return 'story.'.strtolower($theme).'.'.strtolower($template);
    }

    private static function allowed(string $theme, string $template): bool
    {
        return isset(self::COLLECTIONS[$theme]) && in_array($template, self::COLLECTIONS[$theme]['ids'], true);
    }

    private static function config(string $theme, string $template): array
    {
        $value = SiteSetting::whereKey(self::settingKey($theme, $template))->value('value');
        $config = is_string($value) ? json_decode($value, true) : null;
        return is_array($config) ? array_merge(self::DEFAULTS, $config) : self::DEFAULTS;
    }

    private static function photoPath(string $theme, string $template, array $config): ?string
    {
        $path = $config['path'] ?? null;
        if (! is_string($path) || ! preg_match('~^story-templates/'.preg_quote($theme, '~').'/'.preg_quote($template, '~').'/[a-f0-9]{40}\\.(?:jpg|jpeg|png|webp)$~D', $path)) {
            return null;
        }
        return $path;
    }

    public static function catalog(): array
    {
        $result = [];
        foreach (self::COLLECTIONS as $theme => $collection) {
            foreach ($collection['ids'] as $id) {
                $cfg = self::config($theme, $id);
                $path = self::photoPath($theme, $id, $cfg);
                $result[$theme][$id] = [
                    'font' => in_array($cfg['font'], array_keys(self::FONTS), true) ? $cfg['font'] : 'system',
                    'scale' => max(80, min(120, (int) $cfg['scale'])),
                    'text_color' => preg_match('/^#[0-9a-fA-F]{6}$/D', (string) $cfg['text_color']) ? $cfg['text_color'] : '',
                    'focal_x' => max(0, min(100, (int) $cfg['focal_x'])),
                    'focal_y' => max(0, min(100, (int) $cfg['focal_y'])),
                    'title_x' => max(-50, min(50, (int) $cfg['title_x'])),
                    'title_y' => max(-50, min(50, (int) $cfg['title_y'])),
                    'details_x' => max(-50, min(50, (int) $cfg['details_x'])),
                    'details_y' => max(-30, min(30, (int) $cfg['details_y'])),
                    'image_url' => $path && Storage::disk('local')->exists($path)
                        ? route('stories.template.image', ['theme' => $theme, 'template' => $id, 'version' => pathinfo($path, PATHINFO_FILENAME)])
                        : null,
                ];
            }
        }
        return $result;
    }

    public function index()
    {
        return view('admin.stories', ['collections' => self::COLLECTIONS, 'catalog' => self::catalog(), 'fonts' => self::FONTS, 'labels' => self::LABELS, 'photos' => self::DEFAULT_PHOTOS]);
    }

    public function update(Request $request, string $theme, string $template)
    {
        abort_unless(self::allowed($theme, $template), 404);
        $data = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:4096', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
            'font' => ['required', Rule::in(array_keys(self::FONTS))],
            'scale' => ['required', 'integer', 'between:80,120'],
            'text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'focal_x' => ['sometimes', 'integer', 'between:0,100'],
            'focal_y' => ['sometimes', 'integer', 'between:0,100'],
            'title_x' => ['sometimes', 'integer', 'between:-50,50'],
            'title_y' => ['sometimes', 'integer', 'between:-50,50'],
            'details_x' => ['sometimes', 'integer', 'between:-50,50'],
            'details_y' => ['sometimes', 'integer', 'between:-30,30'],
        ]);
        $old = self::config($theme, $template);
        $oldPath = self::photoPath($theme, $template, $old);
        $newPath = null;
        $config = [
            'font' => $data['font'],
            'scale' => (int) $data['scale'],
            'text_color' => strtoupper($data['text_color'] ?? ''),
            'focal_x' => (int) ($data['focal_x'] ?? $old['focal_x'] ?? 50),
            'focal_y' => (int) ($data['focal_y'] ?? $old['focal_y'] ?? 50),
            'title_x' => (int) ($data['title_x'] ?? $old['title_x'] ?? 0),
            'title_y' => (int) ($data['title_y'] ?? $old['title_y'] ?? 0),
            'details_x' => (int) ($data['details_x'] ?? $old['details_x'] ?? 0),
            'details_y' => (int) ($data['details_y'] ?? $old['details_y'] ?? 0),
            'path' => ($data['remove_image'] ?? false) ? null : $oldPath,
        ];
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = strtolower($file->extension());
            $newPath = 'story-templates/'.$theme.'/'.$template.'/'.sha1(Str::random(64)).'.'.$extension;
            if (! Storage::disk('local')->putFileAs(dirname($newPath), $file, basename($newPath))) {
                return back()->withErrors(['image' => 'Не удалось сохранить изображение.']);
            }
            $config['path'] = $newPath;
        }
        try {
            SiteSetting::updateOrCreate(['key' => self::settingKey($theme, $template)], ['value' => json_encode($config, JSON_THROW_ON_ERROR)]);
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            throw $e;
        }
        if ($oldPath && $oldPath !== $config['path']) Storage::disk('local')->delete($oldPath);
        return redirect()->route('admin.stories')->with('success', 'Шаблон сохранён.');
    }

    public function image(string $theme, string $template, string $version)
    {
        abort_unless(self::allowed($theme, $template), 404);
        $config = self::config($theme, $template);
        $path = self::photoPath($theme, $template, $config);
        abort_unless($path && hash_equals(pathinfo($path, PATHINFO_FILENAME), $version), 404);
        $disk = Storage::disk('local');
        $absolute = $disk->path($path);
        $root = realpath($disk->path('story-templates/'.$theme.'/'.$template));
        $resolved = realpath($absolute);
        abort_unless($root && $resolved && dirname($resolved) === $root && ! is_link($absolute)
            && ! is_link($disk->path('story-templates'))
            && ! is_link($disk->path('story-templates/'.$theme))
            && ! is_link($disk->path('story-templates/'.$theme.'/'.$template)), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        return response()->file($resolved, [
            'Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin', 'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
