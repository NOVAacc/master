@extends('admin.layout')
@section('title', 'Шаблоны сторис')
@section('content')
<h1>Шаблоны сторис</h1>
<p class="muted">Управление фотографиями и текстом поверх изображений. Все настройки видны мастерам сразу после сохранения; исходные данные записи остаются автоматическими.</p>
<style>
.story-groups{display:grid;gap:16px}.story-items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.story-item{display:grid;grid-template-columns:96px minmax(0,1fr);gap:14px;border:1px solid #e8e8e8;border-radius:13px;padding:12px;min-width:0}
.story-item form{min-width:0}.story-item label{margin:8px 0 3px}.story-item select{width:100%;padding:8px;border-radius:8px;border:1px solid #dedede;background:#fff}.story-item input[type=file]{font-size:11px;padding:8px}.story-item input[type=range]{padding:0}.story-item input[type=color]{padding:3px;width:50px;height:37px}
.story-frame{width:96px;aspect-ratio:9/16;border-radius:9px;background:#eae1dc;overflow:hidden;background-position:center;background-size:cover;position:relative}
.story-frame:after{content:"";inset:0;position:absolute;background:linear-gradient(#ffffff99,#ffffff18 40%,#ffffffa0)}
.story-frame strong{position:absolute;z-index:1;top:14px;left:8px;font-size:13px;max-width:80px;overflow-wrap:anywhere}
.story-frame span{position:absolute;z-index:1;bottom:14px;left:8px;font-size:11px;font-weight:800}
.story-item h3{margin:0 0 3px;font-size:15px}.story-item .muted{font-size:11px}
.story-controls{display:grid;grid-template-columns:1fr 1fr;gap:6px 10px}.story-controls label{font-size:12px}.story-item .row{margin-top:10px}
@media(max-width:780px){.story-items{grid-template-columns:1fr}}
@media(max-width:390px){.story-item{grid-template-columns:75px minmax(0,1fr)}.story-frame{width:75px}.story-controls{grid-template-columns:1fr}}
</style>
<div class="story-groups">
@foreach($collections as $theme => $collection)
<section class="panel" id="collection-{{ $theme }}">
<h2>{{ $collection['title'] }}</h2>
<div class="story-items">
@foreach($collection['ids'] as $template)
@php($config = $catalog[$theme][$template])
<article class="story-item">
<div class="story-frame" @if($config['image_url']) style="background-image:url('{{ $config['image_url'] }}')" @endif>
<strong style="font-family:{{ $config['font'] === 'serif' ? 'Georgia,serif' : ($config['font'] === 'rounded' ? 'Trebuchet MS,sans-serif' : 'system-ui,sans-serif') }};color:{{ $config['text_color'] ?: '#272727' }}">BOSH</strong>
<span>12:30</span>
</div>
<div>
<h3>{{ $template }}</h3>
<p class="muted">{{ $config['image_url'] ? 'Загружено своё изображение' : 'Используется стандартное фото' }}</p>
<form action="{{ route('admin.stories.update', ['theme' => $theme, 'template' => $template]) }}" method="post" enctype="multipart/form-data">
@csrf
<label>Фотография JPG / PNG / WebP, до 4 МБ</label>
<input name="image" type="file" accept="image/jpeg,image/png,image/webp">
<div class="story-controls">
<div><label>Шрифт</label><select name="font">@foreach($fonts as $font => $label)<option value="{{ $font }}" @selected($config['font'] === $font)>{{ $label }}</option>@endforeach</select></div>
<div><label>Размер заголовка</label><select name="scale">@foreach([80,90,100,110,120] as $size)<option value="{{ $size }}" @selected($config['scale'] === $size)>{{ $size }}%</option>@endforeach</select></div>
<div><label>Цвет текста (необязательно)</label><input name="text_color" type="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="Автоматически" value="{{ $config['text_color'] }}"></div>
<div><label>Сброс фотографии</label><label style="display:flex;align-items:center;gap:6px;font-weight:400"><input type="checkbox" name="remove_image" value="1" style="width:auto"> Стандартная</label></div>
</div>
<div class="row"><button class="primary" type="submit">Сохранить</button></div>
</form>
</div>
</article>
@endforeach
</div>
</section>
@endforeach
</div>
<p class="muted small">Текст в генераторе: название услуги, мастер, дата, время, цена и ссылка формируются автоматически. Новые фотографии показываются и в превью, и в PNG; при ошибке загрузки используется исходный шаблон.</p>
<script>
document.querySelectorAll('.story-item').forEach(card => {
    const form = card.querySelector('form');
    const frame = card.querySelector('.story-frame');
    const wordmark = frame.querySelector('strong');
    const controls = {font:form.elements.namedItem('font'),scale:form.elements.namedItem('scale'),color:form.elements.namedItem('text_color'),image:form.elements.namedItem('image')};
    const initialImage = frame.style.backgroundImage;
    let previewUrl = null;
    const render = () => {
        const fonts = {system:'system-ui,sans-serif',serif:'Georgia,serif',rounded:'Trebuchet MS,sans-serif'};
        wordmark.style.fontFamily = fonts[controls.font.value] || fonts.system;
        wordmark.style.fontSize = String(13 * (Number(controls.scale.value) / 100)) + 'px';
        if (/^#[a-fA-F0-9]{6}$/.test(controls.color.value)) wordmark.style.color = controls.color.value;
    };
    ['font','scale','color'].forEach(key => controls[key].addEventListener('input',render));
    controls.image.addEventListener('change',() => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        const file = controls.image.files[0];
        if (file && ['image/jpeg','image/png','image/webp'].includes(file.type)) {
            previewUrl = URL.createObjectURL(file);
            frame.style.backgroundImage = 'url("' + previewUrl + '")';
        } else frame.style.backgroundImage = initialImage;
    });
    render();
});
</script>
@endsection
