@extends('admin.layout')
@section('title', 'Шаблоны категории')
@section('content')
<p><a href="{{ route('admin.categories') }}#industry-{{ $category['industry'] }}">← Все категории · {{ $category['industry_name'] }}</a></p>
<h1>{{ $category['activity_name'] }}</h1>
<p class="muted">Выберите один из 8 шаблонов для настройки. Подобранные фотографии BOSH используются автоматически, а загруженные вами изображения имеют приоритет. Шаблоны уже доступны мастерам соответствующей категории.</p>
<style>
.blueprints{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}
.blueprint{border:1px solid #e9e9e9;background:white;border-radius:15px;overflow:hidden}.blueprint:hover{border-color:#999;text-decoration:none}.blueprint-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.blueprint-art::before{content:'';position:absolute;inset:0;background:linear-gradient(#0005,#0002 45%,#0009);z-index:1;opacity:0}.blueprint-art:has(.blueprint-img)::before{opacity:1}.blueprint-art:has(.blueprint-img){color:white}
.blueprint-art{aspect-ratio:9/12;padding:18px;display:flex;flex-direction:column;justify-content:space-between;position:relative}
.blueprint-art span{position:relative;z-index:1;font-size:11px;font-weight:750;letter-spacing:.04em}
.blueprint-art strong{position:relative;z-index:1;font-size:25px;line-height:1.05;max-width:94%;overflow-wrap:anywhere}
.blueprint-info{padding:14px}.blueprint-info strong{font-size:13px;display:block;margin-bottom:5px}.blueprint-info p{font-size:12px;margin:0 0 8px;line-height:1.4}
.blueprint-info small{color:#777}
.blueprint-editorial{background:#f2eee9;color:#222}
.blueprint-proof{background:linear-gradient(135deg,#2c343c,#6d7a7d);color:white}
.blueprint-premium{background:linear-gradient(140deg,#141313,#3e3329);color:#f7e9d0}
.blueprint-warm{background:linear-gradient(135deg,#ead0ba,#d49c83);color:#523b31}
.blueprint-urban{background:linear-gradient(145deg,#202027,#8a727b);color:white}
.blueprint-soft{background:linear-gradient(150deg,#f7e6e9,#e2dad5);color:#4f3845}
.blueprint-promo{background:linear-gradient(160deg,#ba263c,#e05e62);color:white}
.blueprint-seasonal{background:linear-gradient(145deg,#62775f,#c2ad8b);color:white}
@media(max-width:850px){.blueprints{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:480px){.blueprints{grid-template-columns:1fr}}
</style>
<div class="blueprints">
@foreach($category['templates'] as $item)
@php($saved = $designs[$item['id']])
<a class="blueprint" href="{{ route('admin.categories.stories.edit', ['industry'=>$category['industry'],'activity'=>$category['activity'],'role'=>$item['id']]) }}">
<div class="blueprint-art blueprint-{{ $item['id'] }}" aria-hidden="true">
@if($saved['image_url'])<img class="blueprint-img" src="{{ $saved['image_url'] }}" alt="" loading="lazy">@endif
<span>BOSH</span>
<strong>{{ $category['activity_name'] }}</strong>
<span>16:30 · Запись открыта</span>
</div>
<div class="blueprint-info">
<strong>{{ $item['name'] }}</strong>
<p>{{ $item['description'] }}</p>
<small>{{ $saved['has_photo'] ? 'Своё фото · изменить' : ($saved['photo_origin'] === 'curated' ? 'Фото BOSH · можно заменить' : 'Без фотографии · настроить') }}</small>
</div>
</a>
@endforeach
</div>
@endsection
