<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class StoryTemplateAdminController extends Controller
{
    public const COLLECTIONS = [
        'nails' => ['title' => 'Маникюр', 'slug' => 'bosh-nail-trends', 'ids' => ['nude','floral','luxury','red','bridal','cozy','urban','chrome']],
        'brows' => ['title' => 'Брови', 'slug' => 'bosh-brow-trends', 'ids' => ['nude','soft-pink','luxury','bold','natural','cozy','urban','editorial']],
        'hair' => ['title' => 'Волосы', 'slug' => 'bosh-hair-trends', 'ids' => ['nude','romantic','brunette','bold','bridal','cozy','urban','chrome']],
        'lashes' => ['title' => 'Ресницы', 'slug' => 'bosh-lash-trends', 'ids' => ['nude','romantic','luxury','glam','natural','soft','urban','evening']],
        'makeup' => ['title' => 'Макияж', 'slug' => 'bosh-makeup-trends', 'ids' => ['nude','blush','luxury','red','bridal','warm','urban','editorial']],
        'massage' => ['title' => 'Массаж', 'slug' => 'bosh-massage-calm', 'ids' => ['minimal','relax','spa','recovery','natural','cozy','urban','luxury']],
        'skin' => ['title' => 'Косметология', 'slug' => 'bosh-skin-aesthetics', 'ids' => ['clean','glow','premium','bold','natural','ritual','urban','advanced']],
        'barber' => ['title' => 'Барбер', 'slug' => 'bosh-barber-style', 'ids' => ['classic','gentleman','luxury','street','beard','vintage','urban','creative']],
        'smooth' => ['title' => 'Эпиляция', 'slug' => 'bosh-smooth-skin', 'ids' => ['nude','soft','premium','bold','natural','care','urban','luxury']],
    ];
    public const LABELS = [
        'nails' => ['nude'=>'Нюд','floral'=>'Цветочный','luxury'=>'Чёрное золото','red'=>'Красный акцент','bridal'=>'Невеста','cozy'=>'Тёплый уют','urban'=>'Городской','chrome'=>'Хром'],
        'brows' => ['nude'=>'Нюд','soft-pink'=>'Нежный розовый','luxury'=>'Премиум','bold'=>'Яркий акцент','natural'=>'Натуральный','cozy'=>'Тёплый уют','urban'=>'Городской стиль','editorial'=>'Fashion'],
        'hair' => ['nude'=>'Нюд','romantic'=>'Романтика','brunette'=>'Тёмный премиум','bold'=>'Яркое окрашивание','bridal'=>'Свадебный','cozy'=>'Тёплый сезон','urban'=>'Городской','chrome'=>'Креатив'],
        'lashes' => ['nude' => 'Лёгкий нюд', 'romantic' => 'Романтика', 'luxury' => 'Чёрный люкс', 'glam' => 'Гламур', 'natural' => 'Естественная красота', 'soft' => 'Мягкий свет', 'urban' => 'Городской шик', 'evening' => 'Вечерний образ'],
        'makeup' => ['nude' => 'Чистый нюд', 'blush' => 'Розовый румянец', 'luxury' => 'Вечерний люкс', 'red' => 'Красный акцент', 'bridal' => 'Невеста', 'warm' => 'Тёплые оттенки', 'urban' => 'Городской стиль', 'editorial' => 'Арт-макияж'],
        'massage' => ['minimal' => 'Спокойствие', 'relax' => 'Релакс', 'spa' => 'SPA-ритуал', 'recovery' => 'Восстановление', 'natural' => 'Гармония природы', 'cozy' => 'Тёплый уют', 'urban' => 'Современный wellness', 'luxury' => 'Премиум SPA'],
        'skin' => ['clean' => 'Чистая кожа', 'glow' => 'Сияние', 'premium' => 'Эстетика премиум', 'bold' => 'Beauty-акцент', 'natural' => 'Естественность', 'ritual' => 'Ритуал ухода', 'urban' => 'Современная эстетика', 'advanced' => 'Новая красота'],
        'barber' => ['classic' => 'Классика', 'gentleman' => 'Джентльмен', 'luxury' => 'Чёрный премиум', 'street' => 'Уличный стиль', 'beard' => 'Борода и стиль', 'vintage' => 'Винтаж', 'urban' => 'Городской барбер', 'creative' => 'Креативная стрижка'],
        'smooth' => ['nude' => 'Мягкий нюд', 'soft' => 'Нежность', 'premium' => 'Премиум уход', 'bold' => 'Яркий beauty', 'natural' => 'Натуральность', 'care' => 'Забота о себе', 'urban' => 'Современный стиль', 'luxury' => 'Эстетика тела'],
    ];
    public const DEFAULT_PHOTOS = [
        'nails' => ['nude' => 'photo-1727199433231-346fd8101839', 'floral' => 'photo-1674383600495-bfa0405f3c93', 'luxury' => 'photo-1736434518489-0eb84070017f', 'red' => 'photo-1637851362556-46d07c374023', 'bridal' => 'photo-1680540441735-2cdc020037c8', 'cozy' => 'photo-1736434518489-0eb84070017f', 'urban' => 'photo-1727199433231-346fd8101839', 'chrome' => 'photo-1684609365994-a144ee021c88'],
        'brows' => ['nude' => 'photo-1516220362602-dba5272034e7', 'soft-pink' => 'photo-1728767593550-705d6ebbf1d6', 'luxury' => 'photo-1729886771704-3a9f5a613d40', 'bold' => 'photo-1738594399272-f05ad0a5ba8a', 'natural' => 'photo-1781503056004-53972080018b', 'cozy' => 'photo-1731284450430-484343eec537', 'urban' => 'photo-1725886260675-7156c4b56b84', 'editorial' => 'photo-1569597212855-f10721b2e1ac'],
        'hair' => ['nude' => 'photo-1562322140-8baeececf3df', 'romantic' => 'photo-1519699047748-de8e457a634e', 'brunette' => 'photo-1522337360788-8b13dee7a37e', 'bold' => 'photo-1607779097040-26e80aa78e66', 'bridal' => 'photo-1560869713-7d563336772e', 'cozy' => 'photo-1633681926022-84c23e8cb2d6', 'urban' => 'photo-1605497788044-5a32c7078486', 'chrome' => 'photo-1521590832167-7bcbfaa6381f'],
        'lashes' => ['nude' => 'photo-1516220362602-dba5272034e7', 'romantic' => 'photo-1728767593550-705d6ebbf1d6', 'luxury' => 'photo-1569597212855-f10721b2e1ac', 'glam' => 'photo-1738594399272-f05ad0a5ba8a', 'natural' => 'photo-1781503056004-53972080018b', 'soft' => 'photo-1725886260675-7156c4b56b84', 'urban' => 'photo-1729886771704-3a9f5a613d40', 'evening' => 'photo-1731284450430-484343eec537'],
        'makeup' => ['nude' => 'photo-1524504388940-b1c1722653e1', 'blush' => 'photo-1517841905240-472988babdf9', 'luxury' => 'photo-1544005313-94ddf0286df2', 'red' => 'photo-1534528741775-53994a69daeb', 'bridal' => 'photo-1488426862026-3ee34a7d66df', 'warm' => 'photo-1524250502761-1ac6f2e30d43', 'urban' => 'photo-1542909168-82c3e7fdca5c', 'editorial' => 'photo-1508214751196-bcfd4ca60f91'],
        'massage' => ['minimal' => 'photo-1540555700478-4be289fbecef', 'relax' => 'photo-1519823551278-64ac92734fb1', 'spa' => 'photo-1544161515-4ab6ce6db874', 'recovery' => 'photo-1512290923902-8a9f81dc236c', 'natural' => 'photo-1600334129128-685c5582fd35', 'cozy' => 'photo-1570172619644-dfd03ed5d881', 'urban' => 'photo-1540555700478-4be289fbecef', 'luxury' => 'photo-1544161515-4ab6ce6db874'],
        'skin' => ['clean' => 'photo-1713085085470-fba013d67e65', 'glow' => 'photo-1556228720-195a672e8a03', 'premium' => 'photo-1570172619644-dfd03ed5d881', 'bold' => 'photo-1611930022073-b7a4ba5fcccd', 'natural' => 'photo-1596462502278-27bfdc403348', 'ritual' => 'photo-1608248543803-ba4f8c70ae0b', 'urban' => 'photo-1620916566398-39f1143ab7be', 'advanced' => 'photo-1571781926291-c477ebfd024b'],
        'barber' => ['classic' => 'photo-1503951914875-452162b0f3f1', 'gentleman' => 'photo-1621605815971-fbc98d665033', 'luxury' => 'photo-1599351431202-1e0f0137899a', 'street' => 'photo-1521590832167-7bcbfaa6381f', 'beard' => 'photo-1596728325488-58c87691e9af', 'vintage' => 'photo-1512690459411-b9245aed614b', 'urban' => 'photo-1622286346003-cad4a2e4b6b7', 'creative' => 'photo-1503951914875-452162b0f3f1'],
        'smooth' => ['nude' => 'photo-1512290923902-8a9f81dc236c', 'soft' => 'photo-1608248543803-ba4f8c70ae0b', 'premium' => 'photo-1556228720-195a672e8a03', 'bold' => 'photo-1570172619644-dfd03ed5d881', 'natural' => 'photo-1540555700478-4be289fbecef', 'care' => 'photo-1600334129128-685c5582fd35', 'urban' => 'photo-1596462502278-27bfdc403348', 'luxury' => 'photo-1620916566398-39f1143ab7be'],
    ];
    public const FONTS = [
        'system' => 'Современный',
        'serif' => 'Редакционный',
        'rounded' => 'Мягкий',
        'elegant' => 'Элегантный',
        'classic' => 'Классический',
        'bold' => 'Акцентный',
        'mono' => 'Моноширинный',
    ];
    public const TEXT_COLORS = [
        '' => 'Авто (цвет шаблона)',
        '#FFFFFF' => 'Белый',
        '#171717' => 'Чёрный',
        '#303038' => 'Графит',
        '#FFF5E7' => 'Молочный',
        '#F6D7E3' => 'Пудровый',
        '#C52F43' => 'Красный BOSH',
        '#E3C58F' => 'Золотистый',
        '#FAF8F4' => 'Жемчужный',
        '#EAE1D6' => 'Лён',
        '#DFCAB8' => 'Песочный',
        '#A65A46' => 'Терракота',
        '#694A40' => 'Какао',
        '#8A727D' => 'Пыльная роза',
        '#A7B6A0' => 'Шалфей',
        '#566A5C' => 'Хвойный',
        '#B5C8D9' => 'Небесный',
        '#36465B' => 'Тёмно-синий',
        '#6E547F' => 'Сливовый',
        '#E5D69B' => 'Шампань',
    ];
    public const DEFAULTS = ['font' => 'system', 'scale' => 100, 'text_color' => '', 'image_position' => 'center', 'image_horizontal' => 'center', 'image_zoom' => 100, 'shade' => 0];

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
                    'image_position' => in_array($cfg['image_position'] ?? '', ['top', 'center', 'bottom'], true) ? $cfg['image_position'] : 'center',
                    'shade' => max(0, min(40, (int) ($cfg['shade'] ?? 0))),
                    'image_horizontal' => in_array($cfg['image_horizontal'] ?? '', ['left', 'center', 'right'], true) ? $cfg['image_horizontal'] : 'center',
                    'image_zoom' => max(100, min(150, (int) ($cfg['image_zoom'] ?? 100))),
                    'photo_url' => $path && Storage::disk('local')->exists($path)
                        ? route('stories.template.image', ['theme' => $theme, 'template' => $id, 'version' => pathinfo($path, PATHINFO_FILENAME)])
                        : 'https://images.unsplash.com/'.self::DEFAULT_PHOTOS[$theme][$id].'?auto=format&fit=crop&w=1080&h=1920&q=85',
                    'image_url' => $path && Storage::disk('local')->exists($path)
                        ? route('stories.template.image', ['theme' => $theme, 'template' => $id, 'version' => pathinfo($path, PATHINFO_FILENAME)])
                        : null,
                ];
            }
        }
        return $result;
    }

    public function categories()
    {
        $industries = config('industries', []);
        $counts = [];
        foreach (\App\Models\MasterProfile::query()
            ->select('industry', 'activity')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('industry')
            ->whereNotNull('activity')
            ->groupBy('industry', 'activity')
            ->get() as $group) {
            $counts[$group->industry][$group->activity] = (int) $group->total;
        }

        $uploaded = [];
        foreach (SiteSetting::query()->where('key', 'like', 'category-story.%')->get(['key', 'value']) as $entry) {
            $parts = explode('.', $entry->key);
            if (count($parts) !== 4) continue;
            [, $industry, $activity, $role] = $parts;
            if (! \App\Http\Controllers\CategoryStoryTemplateController::allowed($industry, $activity, $role)) continue;
            $saved = json_decode((string) $entry->value, true);
            $path = is_array($saved) ? ($saved['path'] ?? null) : null;
            if (is_string($path) && preg_match('~^category-stories/'.preg_quote($industry,'~').'/'.preg_quote($activity,'~').'/'.preg_quote($role,'~').'/[a-f0-9]{40}\.(?:jpg|jpeg|png|webp)$~D', $path)
                && Storage::disk('local')->exists($path)) {
                $uploaded[$industry][$activity][$role] = true;
            }
        }
        $photoCoverage = [];
        $photoTotal = 0;
        foreach ($industries as $industry => $spec) {
            foreach ($spec['activities'] as $activity => $_) {
                $filled = 0;
                foreach (array_keys(\App\Support\CategoryStoryCatalog::ROLES) as $role) {
                    if (($uploaded[$industry][$activity][$role] ?? false)
                        || \App\Support\CategoryStoryPhotoLibrary::get($industry, $activity, $role)) $filled++;
                }
                $photoCoverage[$industry][$activity] = $filled;
                $photoTotal += $filled;
            }
        }

        return view('admin.categories', [
            'photoCoverage' => $photoCoverage,
            'photoTotal' => $photoTotal,
            'industries' => $industries,
            'counts' => $counts,
            'storyThemes' => [
                'beauty' => [
                    'barber' => 'barber',
                    'spa' => 'massage',
                    'cosmetology' => 'skin',
                    'nails' => 'nails',
                    'brows-lashes' => 'brows',
                    'waxing' => 'smooth',
                    'kids-hair' => 'hair',
                ],
                'medicine' => [
                    'massage' => 'massage',
                    'aesthetic' => 'skin',
                ],
            ],
        ]);
    }

    public function categoryTemplates(string $industry, string $activity)
    {
        $category = \App\Support\CategoryStoryCatalog::forCategory($industry, $activity);
        abort_unless($category !== null, 404);

        $designs = [];
        foreach ($category['templates'] as $template) {
            $designs[$template['id']] = \App\Http\Controllers\CategoryStoryTemplateController::config($industry, $activity, $template['id']);
        }
        return view('admin.category-stories', ['category' => $category, 'designs' => $designs]);
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
