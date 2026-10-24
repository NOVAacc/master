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
            'barber' => [
                'editorial' => ['id' => 35157699, 'title' => 'Мужская стрижка — редакционный портрет', 'author' => 'mk photos'],
                'proof' => ['id' => 33448215, 'title' => 'Fade — точная работа триммером', 'author' => 'Kerim Isazade'],
                'premium' => ['id' => 32351044, 'title' => 'Точная стрижка в барбершопе', 'author' => 'Sephina Cornwall'],
                'warm' => ['id' => 35157692, 'title' => 'Живой момент — клиент и барбер', 'author' => 'mk photos'],
                'urban' => ['id' => 32329615, 'title' => 'Современная стрижка в барбершопе', 'author' => 'Bulat843'],
                'soft' => ['id' => 7697320, 'title' => 'Спокойная атмосфера барбершопа', 'author' => 'RDNE Stock project'],
                'promo' => ['id' => 32351047, 'title' => 'Оформление бороды — крупный план', 'author' => 'Sephina Cornwall'],
                'seasonal' => ['id' => 32351045, 'title' => 'Актуальный барбер-образ', 'author' => 'Sephina Cornwall'],
            ],
        ],
        'sport' => [
            'yoga' => [
                'editorial' => ['id' => 38641908, 'title' => 'Студия йоги — современный портрет', 'author' => 'Luis Pelegrin'],
                'proof' => ['id' => 37182827, 'title' => 'Инструктор помогает на растяжке', 'author' => 'Robert Ortner'],
                'premium' => ['id' => 31160034, 'title' => 'Утончённая йога и медитация', 'author' => 'Elen Rau'],
                'warm' => ['id' => 8436757, 'title' => 'Тёплая атмосфера занятий', 'author' => 'Yan Krukau'],
                'urban' => ['id' => 8436598, 'title' => 'Групповое занятие в современной студии', 'author' => 'Yan Krukau'],
                'soft' => ['id' => 32794957, 'title' => 'Медитация на природе', 'author' => 'Anil Sharma'],
                'promo' => ['id' => 8436411, 'title' => 'Инструктор и клиент — персональное занятие', 'author' => 'Yan Krukau'],
                'seasonal' => ['id' => 34395954, 'title' => 'Свежий сезон — йога в парке', 'author' => 'Zulfugar Karimov'],
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
