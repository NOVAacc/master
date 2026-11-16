<?php

namespace App\Services;

use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class Seo
{
    public static function eligibleMasters()
    {
        return MasterProfile::query()->where('booking_enabled', true)->where('is_demo', false)
            ->whereHas('user', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->whereHas('services', fn ($query) => $query->where('is_active', true));
    }

    public static function forRequest(Request $request): array
    {
        $title = 'BOSH — бесплатная онлайн-запись для мастеров в Кыргызстане';
        $description = 'Бесплатная онлайн-запись для мастеров красоты и специалистов: страница с услугами и ценами, календарь, клиентская база и уведомления в Telegram.';
        $canonical = url('/');
        $index = $request->routeIs('home');
        if (! $index) $title = 'BOSH';
        $schema = null;
        if ($index) {
            $schema = ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication',
                'name' => 'BOSH', 'url' => $canonical, 'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web', 'description' => $description,
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'KGS']];
        }
        if ($request->routeIs('public.master') && ($master = $request->route('master')) instanceof MasterProfile) {
            $canonical = route('public.master', $master->slug);
            $title = $master->display_name.($master->specialty ? ' — '.$master->specialty : '').' — запись онлайн | BOSH';
            $description = Str::limit(trim(strip_tags($master->bio ?: 'Запишитесь онлайн: '.$master->display_name.'. '.($master->specialty ?? '').'. Услуги, цены и свободное время.')), 170);
            $index = self::eligibleMasters()->whereKey($master->id)->exists();
            $schema = $index ? ['@context' => 'https://schema.org', '@type' => 'ProfilePage',
                'url' => $canonical, 'name' => $title,
                'mainEntity' => ['@type' => 'Person', 'name' => $master->display_name,
                    'description' => $description, 'url' => $canonical]] : null;
        } elseif ($request->routeIs('legal.document')) {
            $titles = ['terms' => 'Условия использования', 'privacy' => 'Политика конфиденциальности', 'cookies' => 'Политика Cookie'];
            $title = ($titles[$request->route('document')] ?? 'Документы').' | BOSH';
            $description = $title.'. Условия работы сервиса онлайн-записи BOSH.';
            $canonical = url($request->path());
            $index = true;
        }
        return compact('title', 'description', 'canonical', 'index', 'schema');
    }
}
