@extends('admin.layout')
@section('title', 'Категории мастеров')
@section('content')
<h1>Категории мастеров</h1>
<p class="muted">Направления и виды деятельности синхронизированы с регистрацией мастеров BOSH. Количество профилей рассчитывается из базы данных.</p>
<style>
.master-category-nav{display:flex;flex-wrap:wrap;gap:8px;margin:22px 0}
.master-category-nav a{border:1px solid #e7e7e7;border-radius:999px;padding:8px 12px;background:#fff;font-size:12px}
.master-category-nav a:hover{background:#f2f2f2;text-decoration:none}
.master-category-section{margin:30px 0;scroll-margin-top:24px}
.master-category-section h2{margin-bottom:14px}
.master-category-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.master-category-card{padding:15px 16px;background:#fff;border:1px solid #e9e9e9;border-radius:12px;min-width:0}
.master-category-card strong{display:block;font-size:13px;line-height:1.4}
.master-category-meta{margin-top:7px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:12px}
.master-category-meta a{text-decoration:underline;text-underline-offset:2px}
.master-category-total{font-weight:600;color:#555}
@media(max-width:620px){.master-category-grid{grid-template-columns:1fr}}
</style>
<nav class="master-category-nav" aria-label="Разделы категорий">
@foreach($industries as $industryId => $industry)
<a href="#industry-{{ $industryId }}">{{ $industry['name'] }} · {{ count($industry['activities']) }}</a>
@endforeach
</nav>
@foreach($industries as $industryId => $industry)
<section class="master-category-section" id="industry-{{ $industryId }}">
<h2>{{ $industry['name'] }} <span class="muted small">· {{ count($industry['activities']) }} категорий</span></h2>
<div class="master-category-grid">
@foreach($industry['activities'] as $activityId => $activityName)
@php($theme = $storyThemes[$industryId][$activityId] ?? null)
<article class="master-category-card">
<strong>{{ $activityName }}</strong>
<div class="master-category-meta">
<span class="master-category-total">{{ $counts[$industryId][$activityId] ?? 0 }} мастеров</span>
@if($theme)
<a href="{{ route('admin.stories') }}#{{ $theme }}">Шаблоны сторис</a>
@else
<span class="muted">Тематических сторис пока нет</span>
@endif
</div>
</article>
@endforeach
</div>
</section>
@endforeach
@endsection
