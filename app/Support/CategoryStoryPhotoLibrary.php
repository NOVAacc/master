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
            'tanning' => [
                'editorial' => ['id' => 16645419, 'title' => 'Солярий и зона ухода — спокойные синие тона', 'author' => 'Boris Hamer'],
                'proof' => ['id' => 14256895, 'title' => 'Процедура в вертикальном солярии, крупный план', 'author' => 'John (Giannis) Tekeridis'],
                'premium' => ['id' => 16645412, 'title' => 'Зона солярия и халат в прохладном свете', 'author' => 'Boris Hamer'],
                'warm' => ['id' => 14256892, 'title' => 'Клиент солярия в мягком студийном свете', 'author' => 'John (Giannis) Tekeridis'],
                'urban' => ['id' => 14256894, 'title' => 'Вертикальный солярий, свет и графичная атмосфера', 'author' => 'John (Giannis) Tekeridis'],
                'soft' => ['id' => 14256893, 'title' => 'Клиентка возле солярия и нейтральный фон', 'author' => 'John (Giannis) Tekeridis'],
                'promo' => ['id' => 3736281, 'title' => 'Подбор косметического ухода в салоне', 'author' => 'Polina Tankilevitch'],
                'seasonal' => ['id' => 3736398, 'title' => 'Средства ухода за кожей на светлом фоне', 'author' => 'Polina Tankilevitch'],
            ],
            'spa' => [
                'editorial' => ['id' => 36837602, 'title' => 'Спокойный SPA-интерьер с полотенцем и зеленью', 'author' => 'aiseleer'],
                'proof' => ['id' => 19572635, 'title' => 'SPA-масла и роллер на мягком полотенце', 'author' => 'Tuğba ÖZTÜRK'],
                'premium' => ['id' => 7795398, 'title' => 'Янтарные косметические флаконы и нейтральный текстиль', 'author' => 'Alesia Kozik'],
                'warm' => ['id' => 8842696, 'title' => 'Бежевый SPA-натюрморт с полотенцами', 'author' => 'Karen Laårk Boshoff'],
                'urban' => ['id' => 7795756, 'title' => 'Масло на бежевом полотенце при естественном свете', 'author' => 'Alesia Kozik'],
                'soft' => ['id' => 7795409, 'title' => 'Текстура льна и натуральная SPA-косметика', 'author' => 'Alesia Kozik'],
                'promo' => ['id' => 19167421, 'title' => 'Свеча и светлые полотенца в расслабляющей атмосфере', 'author' => 'Natalia Yamboglo'],
                'seasonal' => ['id' => 7795662, 'title' => 'Мягкий свет, уходовая косметика и полотенца', 'author' => 'Alesia Kozik'],
            ],
            'tattoo' => [
                'editorial' => ['id' => 20509829, 'title' => 'Тату-машинка в руках мастера на тёмном фоне', 'author' => 'Franco Monsalvo'],
                'proof' => ['id' => 6593477, 'title' => 'Деталь процесса нанесения татуировки', 'author' => 'Pavel Danilyuk'],
                'premium' => ['id' => 20267349, 'title' => 'Чёрно-белый кадр профессиональной тату-сессии', 'author' => 'Fatih Doğrul'],
                'warm' => ['id' => 20531502, 'title' => 'Портрет тату-мастера за работой', 'author' => 'Franco Monsalvo'],
                'urban' => ['id' => 13346118, 'title' => 'Монохромная тату-сессия крупным планом', 'author' => 'Hirsh Philippe'],
                'soft' => ['id' => 35833285, 'title' => 'Чёрные чернила и детальная работа тату-мастера', 'author' => 'Matheus Bertelli'],
                'promo' => ['id' => 20519298, 'title' => 'Татуировка в студии, контрастные детали', 'author' => 'Franco Monsalvo'],
                'seasonal' => ['id' => 37023021, 'title' => 'Современный графичный тату-процесс', 'author' => 'Mayara Caroline Mombelli'],
            ],
            'waxing' => [
                'editorial' => ['id' => 35103884, 'title' => 'Восковая депиляция в салоне, спокойный свет', 'author' => 'Larysa Stratiichuk'],
                'proof' => ['id' => 6763621, 'title' => 'Депиляция восковой полоской, крупный план', 'author' => 'Tima Miroshnichenko'],
                'premium' => ['id' => 6763570, 'title' => 'Восковая полоска на мягком розовом фоне', 'author' => 'Tima Miroshnichenko'],
                'warm' => ['id' => 6763116, 'title' => 'Деликатный уход за кожей ног с восковой полоской', 'author' => 'Tima Miroshnichenko'],
                'urban' => ['id' => 6763618, 'title' => 'Работа с восковой полоской, естественные тона', 'author' => 'Tima Miroshnichenko'],
                'soft' => ['id' => 15764070, 'title' => 'Воск в ёмкости и работа специалиста', 'author' => 'Grove Brands'],
                'promo' => ['id' => 7795397, 'title' => 'Натуральная уходовая косметика на льняном фоне', 'author' => 'Alesia Kozik'],
                'seasonal' => ['id' => 26093497, 'title' => 'Инструменты косметолога на нейтральном фоне', 'author' => 'Leticia Curvelo'],
            ],
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
            'driving' => [
                'editorial' => ['id' => 37112146, 'title' => 'Учебный автомобиль с опознавательным знаком 2026', 'author' => 'SHOX ART'],
                'proof' => ['id' => 9518015, 'title' => 'Инструктор объясняет управление автомобилем', 'author' => 'Ron Lach'],
                'premium' => ['id' => 9518246, 'title' => 'Ученик контролирует дорогу через зеркало', 'author' => 'Ron Lach'],
                'warm' => ['id' => 9518018, 'title' => 'Практика вождения с наставником', 'author' => 'Ron Lach'],
                'urban' => ['id' => 9518016, 'title' => 'Ученик за рулём во время урока', 'author' => 'Ron Lach'],
                'soft' => ['id' => 9518240, 'title' => 'Вручение ключей и знакомство с автомобилем', 'author' => 'Ron Lach'],
                'promo' => ['id' => 9518017, 'title' => 'Инструктор с учеником в машине', 'author' => 'Ron Lach'],
                'seasonal' => ['id' => 9518025, 'title' => 'Объяснение правил вождения в салоне машины', 'author' => 'Ron Lach'],
            ],
            'music' => [
                'editorial' => ['id' => 32218646, 'title' => 'Тёплая музыкальная комната с пианино и гитарой', 'author' => 'Flávia Vicentini'],
                'proof' => ['id' => 8520439, 'title' => 'Деталь урока фортепиано — руки учителя и ученика', 'author' => 'Yan Krukau'],
                'premium' => ['id' => 7521299, 'title' => 'Нотная грамота — спокойная студийная композиция', 'author' => 'Pavel Danilyuk'],
                'warm' => ['id' => 8520467, 'title' => 'Урок музыки с педагогом в домашней атмосфере', 'author' => 'Yan Krukau'],
                'urban' => ['id' => 10222304, 'title' => 'Современный индивидуальный урок пианино', 'author' => 'cottonbro studio'],
                'soft' => ['id' => 7714141, 'title' => 'Мягкий свет на занятии фортепиано', 'author' => 'Boris Pavlikovsky'],
                'promo' => ['id' => 6671621, 'title' => 'Онлайн-урок музыки со скрипкой', 'author' => 'Tima Miroshnichenko'],
                'seasonal' => ['id' => 10222313, 'title' => 'Нотная практика, уютная музыкальная студия', 'author' => 'cottonbro studio'],
            ],

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
            'cleaning' => [
                'editorial' => ['id' => 36273684, 'title' => 'Минималистичный домашний кабинет и инвентарь для уборки', 'author' => 'Alec Adriano'],
                'proof' => ['id' => 28576626, 'title' => 'Средства для уборки на спокойном сером фоне', 'author' => 'Jonathan Borba'],
                'premium' => ['id' => 36730122, 'title' => 'Уборка стильной гостиной — интерьер 2026', 'author' => 'Vitaly Gariev'],
                'warm' => ['id' => 6195198, 'title' => 'Заботливая уборка светлой гостиной', 'author' => 'Tima Miroshnichenko'],
                'urban' => ['id' => 6195274, 'title' => 'Профессиональная команда уборки — современный интерьер', 'author' => 'Tima Miroshnichenko'],
                'soft' => ['id' => 10554825, 'title' => 'Бежевое бельё и чистая комната в мягком свете', 'author' => 'Ron Lach'],
                'promo' => ['id' => 9462621, 'title' => 'Подготовка аккуратной спальни к приёму гостей', 'author' => 'Liliana Drew'],
                'seasonal' => ['id' => 36715256, 'title' => 'Свежая уборка дома — естественный свет 2026', 'author' => 'Vitaly Gariev'],
            ],
            'pets' => [
                'editorial' => ['id' => 20329050, 'title' => 'Серый кот — спокойный домашний уход за шерстью', 'author' => 'Nadin Sh'],
                'proof' => ['id' => 6816850, 'title' => 'Груминг собаки — деликатное расчёсывание', 'author' => 'Gustavo Fring'],
                'premium' => ['id' => 6131568, 'title' => 'Стрижка собаки в профессиональном салоне', 'author' => 'Tima Miroshnichenko'],
                'warm' => ['id' => 6865159, 'title' => 'Рыжий кот и мягкая щётка — домашняя забота', 'author' => 'cottonbro studio'],
                'urban' => ['id' => 6130986, 'title' => 'Профессиональный уход за котом в салоне', 'author' => 'Tima Miroshnichenko'],
                'soft' => ['id' => 20329051, 'title' => 'Нежный груминг серого кота крупным планом', 'author' => 'Nadin Sh'],
                'promo' => ['id' => 6131165, 'title' => 'Купание питомца в салоне груминга', 'author' => 'Tima Miroshnichenko'],
                'seasonal' => ['id' => 19145882, 'title' => 'Забота о собаке — спа-груминг', 'author' => 'Goochie Poochie Grooming'],
            ],

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
            'tires' => [
                'editorial' => ['id' => 34357281, 'title' => 'Шины и колесо в сдержанной серой композиции', 'author' => 'Erik Mclean'],
                'proof' => ['id' => 34642048, 'title' => 'Мастер ремонтирует шину на станке', 'author' => 'Aayurdha Binoy'],
                'premium' => ['id' => 8985859, 'title' => 'Чёрно-белое колесо в мастерской, кадр 9:16', 'author' => 'Artem Podrez'],
                'warm' => ['id' => 5260115, 'title' => 'Точная замена автомобильного колеса', 'author' => 'Enis Yavuz'],
                'urban' => ['id' => 8470260, 'title' => 'Современный шиномонтаж — механик проверяет колесо', 'author' => 'Anastasia Shuraeva'],
                'soft' => ['id' => 33814735, 'title' => 'Автомастерская в спокойных индустриальных тонах', 'author' => 'Renee Razumov'],
                'promo' => ['id' => 8986134, 'title' => 'Монохромный автосервис — мастер у подъёмника', 'author' => 'Artem Podrez'],
                'seasonal' => ['id' => 17600886, 'title' => 'Замена колеса в профессиональной мастерской', 'author' => 'Frans van Heerden'],
            ],

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
            'stretching' => [
                'editorial' => ['id' => 35553892, 'title' => 'Стретчинг на реформере в студии 2026', 'author' => 'khezez | خزاز'],
                'proof' => ['id' => 25596675, 'title' => 'Занятие растяжкой на пилатес-реформере', 'author' => 'Ahmet Kurt'],
                'premium' => ['id' => 35553891, 'title' => 'Пилатес и растяжка в современной студии', 'author' => 'khezez | خزاز'],
                'warm' => ['id' => 18499513, 'title' => 'Растяжка на оборудовании в сером зале', 'author' => 'Lê Đức'],
                'urban' => ['id' => 25596676, 'title' => 'Спокойная техника растяжки на реформере', 'author' => 'Ahmet Kurt'],
                'soft' => ['id' => 18499516, 'title' => 'Мягкое освещение студии растяжки', 'author' => 'Lê Đức'],
                'promo' => ['id' => 31509830, 'title' => 'Пилатес в минималистичной студии', 'author' => 'Flexity Yoga & Pilates'],
                'seasonal' => ['id' => 36242676, 'title' => 'Новая студия растяжки — занятие 2026', 'author' => 'Stiven Rivera'],
            ],
            'dance' => [
                'editorial' => ['id' => 35538273, 'title' => 'Студийный танцевальный портрет в чёрном', 'author' => 'Miguel Rodríguez'],
                'proof' => ['id' => 30861353, 'title' => 'Современная танцевальная поза в мягком свете', 'author' => 'Freddy Rezvanian'],
                'premium' => ['id' => 32178419, 'title' => 'Танец и тени — графичная спокойная композиция', 'author' => 'Israyosoy S.'],
                'warm' => ['id' => 7318684, 'title' => 'Танцовщица в светлой бежевой студии', 'author' => 'MART PRODUCTION'],
                'urban' => ['id' => 36189008, 'title' => 'Современный танец в минималистичной студии', 'author' => 'Ana Gonzalez'],
                'soft' => ['id' => 7318762, 'title' => 'Мягкий свет и балетная поза в нейтральном зале', 'author' => 'MART PRODUCTION'],
                'promo' => ['id' => 5742990, 'title' => 'Танцевальное занятие на светлом фоне', 'author' => 'cottonbro studio'],
                'seasonal' => ['id' => 7814850, 'title' => 'Минималистичная современная хореография в белом', 'author' => 'cottonbro studio'],
            ],

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
