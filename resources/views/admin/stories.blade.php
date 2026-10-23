@extends('admin.layout')
@section('title', 'Шаблоны сторис')
@section('content')
<h1>Шаблоны сторис</h1>
<p class="muted">Выберите тематику и один шаблон. Для изменения фотографии и шрифта откроется отдельный редактор.</p>
<style>
.story-category{margin:20px 0 25px}.story-category h2{margin-bottom:12px}
.story-choices{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.story-choice{display:flex;align-items:center;gap:11px;padding:9px;border:1px solid #e8e8e8;border-radius:12px;background:#fff;min-width:0}
.story-choice:hover{border-color:#a4a4a4;text-decoration:none}
.story-choice img{width:49px;height:72px;flex:none;object-fit:cover;border-radius:7px;background:#eee}
.story-choice strong{font-size:12px;line-height:1.25;display:block;overflow-wrap:anywhere}.story-choice .muted{font-size:10px}
@media(max-width:850px){.story-choices{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:440px){.story-choices{grid-template-columns:1fr}.story-choice img{height:60px;width:42px}}
</style>
@foreach($collections as $theme => $collection)
<section class="story-category" aria-labelledby="story-{{ $theme }}">
<h2 id="story-{{ $theme }}">{{ $collection['title'] }}</h2>
<div class="story-choices">
@foreach($collection['ids'] as $template)
@php($config = $catalog[$theme][$template])
@php($preview = $config['image_url'] ?: 'https://images.unsplash.com/'.$photos[$theme][$template].'?auto=format&fit=crop&w=140&h=210&q=55')
<a class="story-choice" href="{{ route('admin.stories.edit', ['theme'=>$theme,'template'=>$template]) }}">
<img src="{{ $preview }}" alt="" loading="lazy">
<div><strong>{{ $labels[$theme][$template] ?? $template }}</strong><span class="muted">{{ $config['image_url'] ? 'Своя фотография' : 'Стандартное фото' }}</span></div>
</a>
@endforeach
</div>
</section>
@endforeach
@endsection
