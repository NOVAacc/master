@extends('admin.layout')
@section('title', 'Шаблоны сторис')
@section('content')
<h1>Шаблоны сторис</h1>
<p class="muted">Выберите тематику и один шаблон. Для изменения фотографии и шрифта откроется отдельный редактор.</p>
<style>
.story-category{margin:20px 0 25px}.story-category[hidden]{display:none}.story-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0 18px}.story-tab{border:1px solid #e6e6e6;background:#fff;padding:8px 14px;border-radius:999px;color:#303030;font-size:12px}.story-tab[aria-selected=true]{background:#252525;color:#fff;border-color:#252525}.story-tab:focus-visible{outline:2px solid #555;outline-offset:2px}.story-category h2{margin-bottom:12px}
.story-choices{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.story-choice{display:flex;align-items:center;gap:11px;padding:9px;border:1px solid #e8e8e8;border-radius:12px;background:#fff;min-width:0}
.story-choice:hover{border-color:#a4a4a4;text-decoration:none}
.story-choice img{width:49px;height:72px;flex:none;object-fit:cover;border-radius:7px;background:#eee}
.story-choice strong{font-size:12px;line-height:1.25;display:block;overflow-wrap:anywhere}.story-choice .muted{font-size:10px}
@media(max-width:850px){.story-choices{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:440px){.story-choices{grid-template-columns:1fr}.story-choice img{height:60px;width:42px}}
</style>
<div class="story-tabs" role="tablist" aria-label="Тематики сторис">
@foreach($collections as $theme => $collection)
<button type="button" role="tab" class="story-tab" id="tab-{{ $theme }}" aria-controls="story-collection-{{ $theme }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-theme="{{ $theme }}">{{ $collection['title'] }}</button>
@endforeach
</div>
@foreach($collections as $theme => $collection)
<section class="story-category" id="story-collection-{{ $theme }}" role="tabpanel" aria-labelledby="tab-{{ $theme }}" @if(!$loop->first) hidden @endif>
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
<script>
(()=>{
 const tabs=[...document.querySelectorAll('.story-tab')];
 const select=(theme,focus=false)=>{
  const selected=tabs.find(tab=>tab.dataset.theme===theme)||tabs[0];
  if(!selected)return;
  tabs.forEach(tab=>{const active=tab===selected;tab.setAttribute('aria-selected',String(active));tab.tabIndex=active?0:-1;document.getElementById('story-collection-'+tab.dataset.theme).hidden=!active;});
  if(focus)selected.focus();
 };
 tabs.forEach((tab,i)=>{
  tab.addEventListener('click',()=>{select(tab.dataset.theme);history.replaceState(null,'','#'+tab.dataset.theme);});
  tab.addEventListener('keydown',e=>{if(!['ArrowRight','ArrowLeft','Home','End'].includes(e.key))return;e.preventDefault();const next=e.key==='Home'?0:e.key==='End'?tabs.length-1:(i+(e.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;const theme=tabs[next].dataset.theme;select(theme,true);history.replaceState(null,'','#'+theme);});
 });
 select(decodeURIComponent(location.hash.slice(1)));
 window.addEventListener('hashchange',()=>select(decodeURIComponent(location.hash.slice(1))));
})();
</script>
@endsection
