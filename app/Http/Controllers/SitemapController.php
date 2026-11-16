<?php

namespace App\Http\Controllers;

use App\Services\Seo;

final class SitemapController extends Controller
{
    public function __invoke()
    {
        return response()->stream(function () {
            echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $emit = function (string $url) {
                echo '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
            };
            $emit(route('home'));
            foreach (['terms', 'privacy', 'cookies'] as $document) {
                $emit(route('legal.document', $document));
            }
            foreach (Seo::eligibleMasters()->select('id', 'slug')->orderBy('id')->cursor() as $master) {
                $emit(route('public.master', $master->slug));
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'X-Robots-Tag' => 'noindex']);
    }
}
