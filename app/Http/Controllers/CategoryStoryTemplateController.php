<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Rules\ProfileImage;
use App\Support\CategoryStoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryStoryTemplateController extends Controller
{
    public static function allowed(string $industry, string $activity, string $role): bool
    {
        return CategoryStoryCatalog::hasCategory($industry, $activity)
            && array_key_exists($role, CategoryStoryCatalog::ROLES);
    }

    private static function key(string $industry, string $activity, string $role): string
    {
        return 'category-story.'.$industry.'.'.$activity.'.'.$role;
    }

    private static function path(string $industry, string $activity, string $role, array $config): ?string
    {
        $path = $config['path'] ?? null;
        $pattern = '~^category-stories/'.preg_quote($industry, '~').'/'.preg_quote($activity, '~').'/'.preg_quote($role, '~').'/[a-f0-9]{40}\.(?:jpg|jpeg|png|webp)$~D';

        return is_string($path) && preg_match($pattern, $path) ? $path : null;
    }

    public static function config(string $industry, string $activity, string $role): array
    {
        abort_unless(self::allowed($industry, $activity, $role), 404);
        $default = CategoryStoryCatalog::ROLES[$role];
        $value = SiteSetting::whereKey(self::key($industry, $activity, $role))->value('value');
        $saved = is_string($value) ? json_decode($value, true) : null;
        $saved = is_array($saved) ? $saved : [];
        $path = self::path($industry, $activity, $role, $saved);
        $exists = $path !== null && Storage::disk('local')->exists($path);
        return [
            'font' => array_key_exists($saved['font'] ?? '', StoryTemplateAdminController::FONTS) ? $saved['font'] : $default['font'],
            'text_color' => in_array($saved['text_color'] ?? $default['text_color'], array_keys(StoryTemplateAdminController::TEXT_COLORS), true) ? ($saved['text_color'] ?? $default['text_color']) : $default['text_color'],
            'scale' => max(80, min(120, (int) ($saved['scale'] ?? 100))),
            'shade' => max(0, min(40, (int) ($saved['shade'] ?? $default['shade']))),
            'image_position' => in_array($saved['image_position'] ?? '', ['top', 'center', 'bottom'], true) ? $saved['image_position'] : 'center',
            'image_horizontal' => in_array($saved['image_horizontal'] ?? '', ['left', 'center', 'right'], true) ? $saved['image_horizontal'] : 'center',
            'image_zoom' => max(100, min(150, (int) ($saved['image_zoom'] ?? 100))),
            'image_url' => $exists ? route('admin.categories.stories.image', [
                'industry' => $industry, 'activity' => $activity, 'role' => $role,
                'version' => pathinfo($path, PATHINFO_FILENAME),
            ]) : null,
            'has_photo' => $exists,
        ];
    }

    public function edit(string $industry, string $activity, string $role)
    {
        abort_unless(self::allowed($industry, $activity, $role), 404);
        $category = CategoryStoryCatalog::forCategory($industry, $activity);
        return view('admin.category-story-edit', [
            'category' => $category,
            'role' => $role,
            'concept' => CategoryStoryCatalog::ROLES[$role],
            'config' => self::config($industry, $activity, $role),
            'fonts' => StoryTemplateAdminController::FONTS,
            'colors' => StoryTemplateAdminController::TEXT_COLORS,
        ]);
    }

    public function update(Request $request, string $industry, string $activity, string $role)
    {
        abort_unless(self::allowed($industry, $activity, $role), 404);
        $data = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:4096', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
            'font' => ['required', Rule::in(array_keys(StoryTemplateAdminController::FONTS))],
            'text_color' => ['nullable', Rule::in(array_keys(StoryTemplateAdminController::TEXT_COLORS))],
            'scale' => ['required', 'integer', 'between:80,120'],
            'shade' => ['required', 'integer', 'between:0,40'],
            'image_position' => ['required', Rule::in(['top','center','bottom'])],
            'image_horizontal' => ['required', Rule::in(['left','center','right'])],
            'image_zoom' => ['required', 'integer', 'between:100,150'],
        ]);
        $key = self::key($industry, $activity, $role);
        $saved = json_decode((string) SiteSetting::whereKey($key)->value('value'), true);
        $oldPath = self::path($industry, $activity, $role, is_array($saved) ? $saved : []);
        $newPath = null;
        $path = $oldPath;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = 'category-stories/'.$industry.'/'.$activity.'/'.$role.'/'.sha1(Str::random(64)).'.'.strtolower($file->extension());
            if (! Storage::disk('local')->putFileAs(dirname($path), $file, basename($path))) {
                return back()->withErrors(['image' => 'Не удалось сохранить фотографию.']);
            }
            $newPath = $path;
        } elseif ($data['remove_image'] ?? false) {
            $path = null;
        }
        $value = [
            'font' => $data['font'], 'text_color' => $data['text_color'] ?? '',
            'scale' => (int) $data['scale'], 'shade' => (int) $data['shade'],
            'image_position' => $data['image_position'], 'image_horizontal' => $data['image_horizontal'],
            'image_zoom' => (int) $data['image_zoom'], 'path' => $path,
        ];
        try {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_THROW_ON_ERROR)]);
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            throw $e;
        }
        if ($oldPath && $oldPath !== $path) Storage::disk('local')->delete($oldPath);
        return redirect()->route('admin.categories.stories.edit', compact('industry','activity','role'))
            ->with('success', 'Настройки сохранены. Публикация для мастеров будет на этапе 3.');
    }

    public function image(string $industry, string $activity, string $role, string $version)
    {
        abort_unless(self::allowed($industry, $activity, $role), 404);
        $key = self::key($industry, $activity, $role);
        $saved = json_decode((string) SiteSetting::whereKey($key)->value('value'), true);
        $path = self::path($industry, $activity, $role, is_array($saved) ? $saved : []);
        abort_unless($path && hash_equals(pathinfo($path, PATHINFO_FILENAME), $version), 404);
        $disk = Storage::disk('local');
        $absolute = $disk->path($path);
        $root = realpath($disk->path('category-stories/'.$industry.'/'.$activity.'/'.$role));
        $real = realpath($absolute);
        abort_unless($root && $real && dirname($real) === $root && !is_link($absolute)
            && !is_link($disk->path('category-stories'))
            && !is_link($disk->path('category-stories/'.$industry))
            && !is_link($disk->path('category-stories/'.$industry.'/'.$activity))
            && !is_link($disk->path('category-stories/'.$industry.'/'.$activity.'/'.$role)), 404);
        $mime = $disk->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg','image/png','image/webp'], true), 404);
        return response()->file($real, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin', 'Cache-Control' => 'private, max-age=3600']);
    }
}
