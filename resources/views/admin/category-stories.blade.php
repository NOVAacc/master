@extends('admin.layout')
@section('title', 'Шаблоны категории')
@section('content')
<p><a href="{{ route('admin.categories') }}#industry-{{ $category['industry'] }}">← Все категории · {{ $category['industry_name'] }}</a></p>
<h1>{{ $category['activity_name'] }}</h1>
<p class="muted">Этап 1 из 3. Для этой категории подготовлено 8 направлений оформления. Это заготовки, пока без фотографий и без публикации в кабинете мастера. Редактирование изображений будет добавлено на этапе 2.</p>
<style>
.blueprints{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}
.blueprint{border:1px solid #e9e9e9;background:white;border-radius:15px;overflow:hidden}
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
<article class="blueprint">
<div class="blueprint-art blueprint-{{ $item['id'] }}" aria-hidden="true">
<span>BOSH</span>
<strong>{{ $category['activity_name'] }}</strong>
<span>16:30 · Запись открыта</span>
</div>
<div class="blueprint-info">
<strong>{{ $item['name'] }}</strong>
<p>{{ $item['description'] }}</p>
<small>Заготовка · фото будет на этапе 2</small>
</div>
</article>
@endforeach
</div>
@endsection
