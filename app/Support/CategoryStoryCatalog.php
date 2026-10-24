<?php

namespace App\Support;

/**
 * Stage 1: canonical category story blueprint. No photos or publication yet.
 * Keep existing nine collections and current client exports unchanged until stage 2.
 */
final class CategoryStoryCatalog
{
    public const ROLES = [
        'editorial' => ['name' => 'Чистый editorial', 'description' => 'Воздух, крупная типографика, чистая композиция', 'font' => 'classic', 'text_color' => '#171717', 'shade' => 0],
        'proof' => ['name' => 'Деталь и результат', 'description' => 'Крупный план процесса или результата услуги', 'font' => 'system', 'text_color' => '#FFFFFF', 'shade' => 20],
        'premium' => ['name' => 'Премиум', 'description' => 'Кинематографичный тёмный кадр и контраст', 'font' => 'elegant', 'text_color' => '#FFF5E7', 'shade' => 25],
        'warm' => ['name' => 'Тёплая атмосфера', 'description' => 'Человечность, живые моменты и естественный свет', 'font' => 'rounded', 'text_color' => '#171717', 'shade' => 10],
        'urban' => ['name' => 'Городской стиль', 'description' => 'Смелая типографика и современная композиция', 'font' => 'bold', 'text_color' => '#FFFFFF', 'shade' => 20],
        'soft' => ['name' => 'Мягкий стиль', 'description' => 'Приглушённая палитра и спокойное настроение', 'font' => 'serif', 'text_color' => '#303038', 'shade' => 5],
        'promo' => ['name' => 'Промо и запись', 'description' => 'Максимально заметные дата, время и призыв записаться', 'font' => 'system', 'text_color' => '#FFFFFF', 'shade' => 25],
        'seasonal' => ['name' => 'Свежий тренд', 'description' => 'Обновляемый сезонный стиль и актуальная фотография', 'font' => 'elegant', 'text_color' => '#FFFFFF', 'shade' => 20],
    ];

    public static function hasCategory(string $industry, string $activity): bool
    {
        $industries = config('industries', []);

        return isset($industries[$industry]['activities'][$activity]);
    }

    public static function forCategory(string $industry, string $activity): ?array
    {
        if (! self::hasCategory($industry, $activity)) {
            return null;
        }

        $source = config('industries')[$industry];

        return [
            'industry' => $industry,
            'industry_name' => $source['name'],
            'activity' => $activity,
            'activity_name' => $source['activities'][$activity],
            'templates' => array_map(
                static fn (string $id, array $definition): array => [
                    'id' => $id,
                    'key' => $industry.'.'.$activity.'.'.$id,
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'font' => $definition['font'],
                    'text_color' => $definition['text_color'],
                    'shade' => $definition['shade'],
                    'status' => 'planned',
                    'image_url' => null,
                ],
                array_keys(self::ROLES),
                array_values(self::ROLES),
            ),
        ];
    }

    public static function all(): array
    {
        $result = [];
        foreach (config('industries', []) as $industry => $config) {
            foreach ($config['activities'] as $activity => $name) {
                $result[$industry][$activity] = self::forCategory($industry, $activity);
            }
        }

        return $result;
    }
}
