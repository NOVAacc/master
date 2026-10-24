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
            'cosmetology' => [
                'editorial' => ['id' => 30809944, 'title' => 'Спокойный уход за лицом', 'author' => 'Mochammad Algi'],
                'proof' => ['id' => 5069584, 'title' => 'Деталь процедуры, нейтральный фон', 'author' => 'Anna Shvets'],
                'premium' => ['id' => 4586746, 'title' => 'Эстетическая процедура в клинике', 'author' => 'Anna Shvets'],
                'warm' => ['id' => 5659059, 'title' => 'Мастер и клиент в кабинете', 'author' => 'Ivan S'],
                'urban' => ['id' => 36930272, 'title' => 'Современный кабинет косметологии', 'author' => 'Kerim Eveyik'],
                'soft' => ['id' => 5069458, 'title' => 'Деликатный уход в светлой студии', 'author' => 'Anna Shvets'],
                'promo' => ['id' => 5069572, 'title' => 'Уход за лицом, свободное окно', 'author' => 'Anna Shvets'],
                'seasonal' => ['id' => 4586715, 'title' => 'Свежий уход и естественный свет', 'author' => 'Anna Shvets'],
            ],
            'brows-lashes' => [
                'editorial' => ['id' => 29151227, 'title' => 'Минималистичный портрет с макияжем бровей', 'author' => 'sahar photography'],
                'proof' => ['id' => 33580445, 'title' => 'Точная коррекция бровей в салоне', 'author' => 'Bulat843'],
                'premium' => ['id' => 36930354, 'title' => 'Макро ресниц в тёплых кофейных оттенках', 'author' => 'Kerim Eveyik'],
                'warm' => ['id' => 34930118, 'title' => 'Тёплая атмосфера наращивания ресниц', 'author' => 'José Antonio Otegui Auzmendi'],
                'urban' => ['id' => 33580450, 'title' => 'Работа визажиста в современной студии', 'author' => 'Bulat843'],
                'soft' => ['id' => 5128114, 'title' => 'Светлый минималистичный кабинет ресниц', 'author' => 'Nataliya Vaitkevich'],
                'promo' => ['id' => 33723106, 'title' => 'Выразительные ресницы крупным планом', 'author' => 'Layla Luany'],
                'seasonal' => ['id' => 38194465, 'title' => 'Современный акцентный уход за ресницами', 'author' => 'Visen Group'],
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
        'medicine' => [
            'massage' => [
                'editorial' => ['id' => 6628532, 'title' => 'Светлая массажная комната и спокойный интерьер', 'author' => 'KoolShooters'],
                'proof' => ['id' => 8312875, 'title' => 'Деталь работы массажиста', 'author' => 'RDNE Stock project'],
                'premium' => ['id' => 31234759, 'title' => 'Современный SPA-сеанс в нейтральных тонах', 'author' => 'Andrea Prochilo'],
                'warm' => ['id' => 5793781, 'title' => 'Тёплый дневной свет и работа специалиста', 'author' => 'Yan Krukau'],
                'urban' => ['id' => 31914690, 'title' => 'Массаж в современной SPA-студии', 'author' => 'KERO 97'],
                'soft' => ['id' => 5240739, 'title' => 'Нежный массаж и расслабление', 'author' => 'Anete Lusina'],
                'promo' => ['id' => 6560293, 'title' => 'Массаж горячими камнями — крупный план', 'author' => 'Anna Tarazevich'],
                'seasonal' => ['id' => 10976271, 'title' => 'Уютный SPA-ритуал в природных оттенках', 'author' => 'Ekaterina Mitkina'],
            ],
            'dentistry' => [
                'editorial' => ['id' => 6812453, 'title' => 'Минималистичная стоматология', 'author' => 'Pavel Danilyuk'],
                'proof' => ['id' => 18523995, 'title' => 'Работа стоматолога крупным планом', 'author' => 'Ayberk Mirza'],
                'premium' => ['id' => 6809645, 'title' => 'Чистый интерьер стоматологии', 'author' => 'Pavel Danilyuk'],
                'warm' => ['id' => 6812570, 'title' => 'Врач и пациент, доверительная атмосфера', 'author' => 'Pavel Danilyuk'],
                'urban' => ['id' => 19976601, 'title' => 'Современная стоматология и оборудование', 'author' => 'Arda Kaykısız'],
                'soft' => ['id' => 5622234, 'title' => 'Спокойный приём у стоматолога', 'author' => 'Gustavo Fring'],
                'promo' => ['id' => 5355903, 'title' => 'Запись к стоматологу', 'author' => 'Tima Miroshnichenko'],
                'seasonal' => ['id' => 3881319, 'title' => 'Светлая клиника, актуальный уход', 'author' => 'Andrea Piacquadio'],
            ],
        ],
        'education' => [
            'languages' => [
                'editorial' => ['id' => 6991507, 'title' => 'Бежевый блокнот с пространством для текста', 'author' => 'DS stories'],
                'proof' => ['id' => 37811262, 'title' => 'Ученики за занятиями, естественные цвета', 'author' => 'Eduard Perez'],
                'premium' => ['id' => 37220567, 'title' => 'Кинематографичный тёплый класс', 'author' => 'Raditya Amoret'],
                'warm' => ['id' => 6929203, 'title' => 'Учитель и ученик в диалоге', 'author' => 'Polina Tankilevitch'],
                'urban' => ['id' => 32394664, 'title' => 'Обучение с ноутбуком в современном классе', 'author' => 'Haidar Azmi'],
                'soft' => ['id' => 5088025, 'title' => 'Нейтральный образовательный flat lay', 'author' => 'olia danilevich'],
                'promo' => ['id' => 4126697, 'title' => 'Учёба с ноутбуком на спокойном бежевом фоне', 'author' => 'Ketut Subiyanto'],
                'seasonal' => ['id' => 37827719, 'title' => 'Современный живой процесс обучения', 'author' => 'Mica Bassa'],
            ],
        ],
        'household' => [
            'photo' => [
                'editorial' => ['id' => 35888640, 'title' => 'Фотограф в студии — светлый вертикальный портрет', 'author' => 'Davide Comunian'],
                'proof' => ['id' => 32092180, 'title' => 'Работа с камерой в студии', 'author' => 'kaleef lawal'],
                'premium' => ['id' => 37542491, 'title' => 'Кинематографический студийный портрет', 'author' => 'Vinícius Vieira ft'],
                'warm' => ['id' => 20419528, 'title' => 'Фотосъёмка в тёплой студии', 'author' => 'Lisett Kruusimäe'],
                'urban' => ['id' => 31921820, 'title' => 'Студийное освещение — графичная композиция', 'author' => 'Emanuel Pedro'],
                'soft' => ['id' => 11180109, 'title' => 'Минималистичная фотостудия, светлый нейтральный фон', 'author' => 'Aleksandra Norboeva'],
                'promo' => ['id' => 32054999, 'title' => 'Свободная фотостудия с нейтральным фоном', 'author' => 'Caio Mantovani'],
                'seasonal' => ['id' => 36593892, 'title' => 'Монохромный портрет фотографа', 'author' => 'Enes Türkoğlu'],
            ],
            'interior' => [
                'editorial' => ['id' => 35574723, 'title' => 'Светлая минималистичная гостиная', 'author' => 'Lydia Griva'],
                'proof' => ['id' => 38139754, 'title' => 'Дизайнер подбирает фактуры и материалы', 'author' => 'Jonathan Borba'],
                'premium' => ['id' => 34857065, 'title' => 'Нейтральная гостиная с дизайнерскими деталями', 'author' => 'Griselda Belba'],
                'warm' => ['id' => 35574733, 'title' => 'Тёплый интерьер с натуральным светом', 'author' => 'Lydia Griva'],
                'urban' => ['id' => 37070073, 'title' => 'Современный светлый интерьер', 'author' => 'UMUT RAW'],
                'soft' => ['id' => 36962663, 'title' => 'Бежевый интерьер и мягкий солнечный свет', 'author' => 'Luk Sauvage'],
                'promo' => ['id' => 6580007, 'title' => 'Образцы материалов и колор-карта дизайнера', 'author' => 'cottonbro studio'],
                'seasonal' => ['id' => 37734652, 'title' => 'Свежий тренд — минималистичная гостиная 9:16', 'author' => 'Misbaa eri'],
            ],
        ],
        'auto' => [
            'car-wash' => [
                'editorial' => ['id' => 30564616, 'title' => 'Монохромный автомобиль в пене, вертикальный кадр', 'author' => 'Borta'],
                'proof' => ['id' => 35149468, 'title' => 'Полировка кузова крупным планом', 'author' => 'Melih Can'],
                'premium' => ['id' => 31390691, 'title' => 'Деликатная чистка интерьера автомобиля', 'author' => 'Bulat843'],
                'warm' => ['id' => 33529540, 'title' => 'Живой процесс мойки серебристого автомобиля', 'author' => 'Tahir Xəlfəquliyev'],
                'urban' => ['id' => 31154214, 'title' => 'Чёрный автомобиль в современной мойке', 'author' => 'Bulat843'],
                'soft' => ['id' => 37112139, 'title' => 'Чёрно-белая фактура пены и колеса', 'author' => 'SHOX ART'],
                'promo' => ['id' => 35244782, 'title' => 'Светлая деталь колеса и ухода', 'author' => 'Mike Kutz'],
                'seasonal' => ['id' => 29504461, 'title' => 'Автомойка с белым автомобилем и пеной', 'author' => 'Bulat843'],
            ],
        ],
        'sport' => [
            'trainer' => [
                'editorial' => ['id' => 39480954, 'title' => 'Монохромная студия тренировок — свободное пространство', 'author' => 'Tom Jackson'],
                'proof' => ['id' => 37963563, 'title' => 'Тренер помогает клиенту выполнить упражнение', 'author' => 'Bulat843'],
                'premium' => ['id' => 28455439, 'title' => 'Силовая тренировка в тёмном зале', 'author' => 'Jimmy Elizarraras'],
                'warm' => ['id' => 4854259, 'title' => 'Поддержка клиента тренером в зале', 'author' => 'Ketut Subiyanto'],
                'urban' => ['id' => 30428668, 'title' => 'Персональное занятие на тренажёре', 'author' => 'more late'],
                'soft' => ['id' => 6516216, 'title' => 'Занятие фитнесом в минималистичной студии', 'author' => 'Polina Tankilevitch'],
                'promo' => ['id' => 4854256, 'title' => 'Персональная тренировка — запись на занятие', 'author' => 'Ketut Subiyanto'],
                'seasonal' => ['id' => 39219660, 'title' => 'Тренер обсуждает программу с клиентом', 'author' => 'Vitaly Gariev'],
            ],
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
