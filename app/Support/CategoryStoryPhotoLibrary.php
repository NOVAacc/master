<?php

namespace App\Support;

/**
 * Human-curated, category-specific photographs from the Pexels free collection.
 * Only verified photo IDs and canonical Pexels source pages are allowed.
 * A design always uses its uploaded photo before these curated defaults.
 */
final class CategoryStoryPhotoLibrary
{
    public const COLLECTIONS = [
        'beauty' => [
            'nails' => [
                'editorial' => ['id' => 35533836, 'title' => 'Маникюр и украшения — крупный план', 'author' => 'Murat IŞIK'],
                'proof' => ['id' => 34132695, 'title' => 'Работа мастера — процесс маникюра 9:16', 'author' => 'Bulat843'],
                'premium' => ['id' => 34885842, 'title' => 'Цветочный nail art — премиальная фактура', 'author' => 'Salim Da'],
                'warm' => ['id' => 34930100, 'title' => 'Живой процесс в салоне', 'author' => 'José Antonio Otegui Auzmendi'],
                'urban' => ['id' => 38283820, 'title' => 'Современный разноцветный маникюр', 'author' => 'Ssümçiğ'],
                'soft' => ['id' => 34930165, 'title' => 'Светлый салон — мягкое оформление', 'author' => 'José Antonio Otegui Auzmendi'],
                'promo' => ['id' => 39365946, 'title' => 'Красный маникюр — яркий акцент', 'author' => 'Zulfugar Karimov'],
                'seasonal' => ['id' => 34835286, 'title' => 'Розовый маникюр и блеск — свежий сезон', 'author' => 'Salim Da'],
            ],
        ],
    ];

    public static function get(string $industry, string $activity, string $role): ?array
    {
        $photo = self::COLLECTIONS[$industry][$activity][$role] ?? null;
        if (! $photo) return null;
        $id = $photo['id'];
        return array_merge($photo, [
            'page' => 'https://www.pexels.com/photo/'.$id.'/',
            'url' => 'https://images.pexels.com/photos/'.$id.'/pexels-photo-'.$id.'.jpeg?auto=compress&cs=tinysrgb&w=1080&h=1920&fit=crop',
            'version' => sha1('pexels:'.$id),
            'license' => 'Pexels Free',
        ]);
    }

    public static function count(string $industry, string $activity): int
    {
        return count(self::COLLECTIONS[$industry][$activity] ?? []);
    }
}
