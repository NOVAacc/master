@extends('admin.layout')
@section('title', 'Редактор шаблона')
@section('content')
<p><a href="{{ route('admin.stories') }}">← Все шаблоны</a></p>
<h1>{{ $title }} · {{ $label }}</h1>
<p class="muted">Изменяйте оформление только этого шаблона. Данные мастера, услуга, дата, цена, ссылка и логотип BOSH подставляются автоматически.</p>
<style>
.story-edit-layout{display:grid;grid-template-columns:minmax(0,1fr) 230px;gap:24px;align-items:start;max-width:820px}
.story-edit-layout .panel{margin-top:14px}.story-edit-form label{margin:13px 0 6px}
.story-edit-form select{width:100%;border:1px solid #e5e5e5;border-radius:10px;background:white;padding:11px}
.story-edit-form input[type=file]{font-size:12px}.story-edit-form input[type=checkbox]{width:auto}
.story-edit-preview{width:230px;aspect-ratio:9/16;position:relative;background-size:cover;background-position:center;border-radius:16px;overflow:hidden;background-color:#eee;box-shadow:0 4px 24px #00000015}
.story-edit-preview:before{content:"";position:absolute;inset:0;background:linear-gradient(#ffffffc2,#ffffff55 45%,#ffffffe0)}
.story-edit-preview .text{position:absolute;z-index:1;left:9%;right:9%;font:500 12px system-ui,sans-serif;color:#30292b}
.story-edit-preview .brand{top:8%;font-weight:800;letter-spacing:-.4px}
.story-edit-preview .headline{top:33%;font-size:11px}
.story-edit-preview .headline strong{font-size:24px;line-height:1.1;display:block;margin-top:8px}
.story-edit-preview .meta{top:64%;font-weight:800;font-size:25px}
.story-edit-preview .meta span{display:block;font-size:12px;font-weight:500;margin-top:10px}
.story-edit-preview .cta{top:82%;padding:10px 5px;text-align:center;color:white!important;background:#29282c;border-radius:24px;font-weight:700}
.story-edit-form button{margin-top:16px}
@media(max-width:650px){.story-edit-layout{grid-template-columns:1fr}.story-edit-preview{grid-row:1;margin:0 auto}.story-edit-layout .panel{margin:0}}
</style>
@php($preview = $config['image_url'] ?: 'https://images.unsplash.com/'.$photo.'?auto=format&fit=crop&w=460&h=820&q=75')
<div class="story-edit-layout">
<section class="panel">
<form method="post" action="{{ route('admin.stories.update', ['theme'=>$theme,'template'=>$template]) }}" enctype="multipart/form-data" class="story-edit-form" id="templateForm">
@csrf
<label for="image">Изображение JPG, PNG или WebP (до 4 МБ)</label>
<input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
<p class="muted small">Рекомендуется вертикальная фотография 9:16 с местом для текста.</p>
<label style="display:flex;gap:8px;align-items:center;font-weight:400"><input name="remove_image" type="checkbox" value="1" id="removeImage"> Вернуть исходное фото</label>
<label for="font">Шрифт сторис</label>
<select id="font" name="font">@foreach($fonts as $key => $titleFont)<option value="{{ $key }}" @selected($config['font']===$key)>{{ $titleFont }}</option>@endforeach</select>
<label for="scale">Размер заголовка</label>
<select id="scale" name="scale">@foreach([80,90,100,110,120] as $size)<option value="{{ $size }}" @selected($config['scale']===$size)>{{ $size }}%</option>@endforeach</select>
<label for="text_color">Цвет текста</label>
<input id="text_color" name="text_color" value="{{ $config['text_color'] }}" placeholder="Автоматически" maxlength="7" pattern="#[a-fA-F0-9]{6}">
<p class="muted small">Оставьте пустым для штатной цветовой палитры шаблона.</p>
<button class="primary" type="submit">Сохранить шаблон</button>
</form>
</section>
<div class="story-edit-preview" id="storyPreview" style="background-image:url('{{ $preview }}')">
<div class="text brand">BOSH</div>
<div class="text headline">ЕСТЬ СВОБОДНОЕ ВРЕМЯ<strong>{{ $title }}</strong>Пример услуги</div>
<div class="text meta">16:30<span>25 октября · 1 500 сом</span></div>
<div class="text cta">Записаться</div>
</div>
</div>
<script>
(()=>{
const form=document.getElementById('templateForm'),preview=document.getElementById('storyPreview');
const initial=preview.style.backgroundImage;
let blobUrl;
const render=()=>{
 const fonts={system:'system-ui,sans-serif',serif:'Georgia,serif',rounded:'Trebuchet MS,sans-serif'};
 const color=/^#[a-fA-F0-9]{6}$/.test(form.text_color.value)?form.text_color.value:'#30292b';
 preview.querySelectorAll('.text').forEach(el=>{el.style.fontFamily=fonts[form.font.value]||fonts.system;if(!el.classList.contains('cta'))el.style.color=color;});
 preview.querySelector('.headline strong').style.fontSize=(24*Number(form.scale.value)/100)+'px';
};
form.font.addEventListener('change',render);form.scale.addEventListener('change',render);form.text_color.addEventListener('input',render);
form.image.addEventListener('change',()=>{
 if(blobUrl)URL.revokeObjectURL(blobUrl);
 blobUrl=null;
 const file=form.image.files[0];
 if(file&&['image/jpeg','image/png','image/webp'].includes(file.type)){
   blobUrl=URL.createObjectURL(file);
   preview.style.backgroundImage='url("'+blobUrl+'")';
 }else preview.style.backgroundImage=initial;
});
form.remove_image.addEventListener('change',()=>{
 if(form.remove_image.checked){form.image.value='';if(blobUrl)URL.revokeObjectURL(blobUrl);blobUrl=null;preview.style.backgroundImage='url("https://images.unsplash.com/{{ $photo }}?auto=format&fit=crop&w=460&h=820&q=75")';}
 else preview.style.backgroundImage=initial;
});
window.addEventListener('pagehide',()=>{if(blobUrl)URL.revokeObjectURL(blobUrl)},{once:true});
render();
})();
</script>
@endsection
