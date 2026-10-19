<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bios = [
            'demo-alina-makeup' => 'Естественный макияж и выразительные вечерние образы. Подберём оттенки под ваш тип внешности, пожелания и событие. Перед началом обсудим детали и желаемый результат.',
            'demo-timur-massage' => 'Расслабляющий массаж в спокойной обстановке. Можно выбрать отдельную зону или полноценный сеанс для отдыха после насыщенного дня. Интенсивность согласовывается перед процедурой.',
            'demo-azamat-barber' => 'Мужские стрижки и оформление бороды. Подберём удобную форму и покажем, как поддерживать укладку дома. В прайсе есть отдельные услуги и комплекс.',
            'demo-sofia-style' => 'Помогаю собрать гардероб под ваш образ жизни. Разбираем привычные сочетания, находим новые комплекты и составляем понятный список покупок в рамках вашего бюджета.',
            'demo-aida-skincare' => 'Бережный эстетический уход за кожей без инвазивных процедур. Подберём комфортную программу очищения и увлажнения с учётом ваших пожеланий и привычного домашнего ухода.',
            'demo-diana-nails' => 'Аккуратный маникюр, лаконичные формы и однотонное покрытие. Можно добавить снятие предыдущего покрытия или небольшой дизайн. Длительность каждой услуги указана в прайсе.',
            'demo-nurai-brows' => 'Оформление бровей с акцентом на естественную форму. Подберём оттенок и подходящую укладку. Коррекцию, окрашивание и ламинирование можно выбрать отдельно.',
            'demo-camila-hair' => 'Стрижки, укладки и косметический уход для волос. Обсудим желаемую форму и привычный способ укладки, чтобы результат был удобен каждый день.',
            'demo-elina-wax' => 'Деликатная депиляция с раздельным выбором зон. Способ процедуры и пожелания обсудим заранее. После процедуры — завершающий косметический уход.',
        ];
        DB::transaction(function () use ($bios) {
            foreach ($bios as $slug => $bio) {
                $master = \App\Models\MasterProfile::where('slug', $slug)->lockForUpdate()->first();
                if (! $master) continue;
                if ($master->user->email !== $slug.'@example.invalid') {
                    throw new \RuntimeException('Test profile ownership does not match.');
                }
                $master->update(['is_demo' => false, 'booking_enabled' => true,
                    'phone' => '996555000000', 'auto_confirm' => false,
                    'whatsapp_notifications' => false, 'address' => 'Бишкек', 'bio' => $bio]);
                $master->user->update(['name' => $master->display_name]);
            }
        });
    }

    public function down(): void
    {
        // Do not remove profiles or customer bookings when rolling back code.
    }
};
