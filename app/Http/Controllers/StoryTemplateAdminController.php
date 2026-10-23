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
    public const FONTS = ['system' => 'Современный', 'serif' => 'Редакционный', 'rounded' => 'Мягкий'];
    public const DEFAULTS = ['font' => 'system', 'scale' => 100, 'text_color' => ''];

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
        return view('admin.stories', ['collections' => self::COLLECTIONS, 'catalog' => self::catalog(), 'fonts' => self::FONTS]);
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
        ]);
        $old = self::config($theme, $template);
        $oldPath = self::photoPath($theme, $template, $old);
        $newPath = null;
        $config = [
            'font' => $data['font'],
            'scale' => (int) $data['scale'],
            'text_color' => strtoupper($data['text_color'] ?? ''),
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
