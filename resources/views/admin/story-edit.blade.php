@extends('admin.layout')
@section('title', 'Редактор шаблона')
@section('content')
<p><a href="{{ route('admin.stories') }}#{{ $theme }}">← Все шаблоны · {{ $title }}</a></p>
<h1>{{ $title }} · {{ $label }}</h1>
<p class="muted">Изменяйте оформление только этого шаблона. Данные мастера, услуга, дата, цена, ссылка и логотип BOSH подставляются автоматически.</p>
<style>
.story-edit-layout{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:24px;align-items:start;max-width:900px}
.story-edit-layout .panel{margin:14px 0}.story-edit-form label{margin:13px 0 6px}
.story-edit-form select{width:100%;border:1px solid #e5e5e5;border-radius:10px;background:white;padding:11px}
.story-edit-form input[type=file]{font-size:12px}.story-edit-form input[type=checkbox]{width:auto}
.story-edit-preview{width:100%;max-width:270px;aspect-ratio:9/16;border-radius:16px;display:block;background:#eee;box-shadow:0 4px 24px #00000015}
.story-color-swatches{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.story-edit-form .story-color-swatch{padding:3px;min-width:34px;width:34px;height:34px;border:2px solid transparent;border-radius:50%;background:white;box-shadow:none}
.story-edit-form .story-color-swatch[aria-pressed=true]{border-color:#222}
.story-color-dot{display:block;width:24px;height:24px;border:1px solid #d0d0d0;border-radius:50%}
.story-edit-form button[type=submit]{margin-top:16px}.story-preview-status{font-size:12px;line-height:1.4;text-align:center;margin:8px 0;color:#666}
@media(max-width:650px){.story-edit-layout{grid-template-columns:1fr}.story-edit-preview{margin:0 auto}.story-edit-layout .panel{margin:0}}
</style>
<div class="story-edit-layout">
<section class="panel">
<form method="post" action="{{ route('admin.stories.update', ['theme'=>$theme,'template'=>$template]) }}" enctype="multipart/form-data" class="story-edit-form" id="templateForm">
@csrf
<label for="image">Изображение JPG, PNG или WebP (до 4 МБ)</label>
<input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
<p class="muted small">Рекомендуется вертикальная фотография 9:16 с местом для текста.</p>
<label style="display:flex;gap:8px;align-items:center;font-weight:400"><input name="remove_image" type="checkbox" value="1" id="removeImage"> Вернуть исходное фото</label>
<label for="image_zoom">Увеличение фотографии</label>
<select id="image_zoom" name="image_zoom">
@foreach([100,110,120,130,140,150] as $size)
<option value="{{ $size }}" @selected(($config['image_zoom'] ?? 100) === $size)>{{ $size }}%</option>
@endforeach
</select>
<p class="muted small">Масштаб применяется только к фону. Текст и логотип остаются на месте.</p>
<label for="shade">Дополнительный контраст фотографии</label>
<select id="shade" name="shade">
@foreach([0 => 'Без изменений', 10 => 'Лёгкий — 10%', 20 => 'Средний — 20%', 30 => 'Сильный — 30%', 40 => 'Максимальный — 40%'] as $value => $description)
<option value="{{ $value }}" @selected(($config['shade'] ?? 0) === $value)>{{ $description }}</option>
@endforeach
</select>
<p class="muted small">Улучшает читаемость текста на светлых и тёмных фотографиях.</p>
<label for="font">Шрифт сторис</label>
<select id="font" name="font">@foreach($fonts as $key => $titleFont)<option value="{{ $key }}" @selected($config['font']===$key)>{{ $titleFont }}</option>@endforeach</select>
<label for="scale">Размер заголовка</label>
<select id="scale" name="scale">@foreach([80,90,100,110,120] as $size)<option value="{{ $size }}" @selected($config['scale']===$size)>{{ $size }}%</option>@endforeach</select>
<label for="text_color">Цвет текста</label>
<select id="text_color" name="text_color" aria-describedby="text-color-hint">
@foreach($textColors as $value => $name)
<option value="{{ $value }}" @selected(strtoupper($config['text_color']) === $value)>{{ $name }}</option>
@endforeach
@if($config['text_color'] !== '' && !array_key_exists(strtoupper($config['text_color']), $textColors))
<option value="{{ $config['text_color'] }}" selected>Ранее выбранный цвет</option>
@endif
</select>
<div class="story-color-swatches" role="group" aria-label="Быстрый выбор цвета текста">
@foreach($textColors as $value => $name)
<button type="button" class="story-color-swatch" data-color="{{ $value }}" aria-label="{{ $name }}" title="{{ $name }}" aria-pressed="{{ strtoupper($config['text_color']) === $value ? 'true' : 'false' }}">
<span class="story-color-dot" style="{{ $value === '' ? 'background:conic-gradient(#fff 0 25%,#242424 0 50%,#f2d4de 0 75%,#e3c58f 0);' : 'background:'.$value.';' }}"></span>
</button>
@endforeach
</div>
<p id="text-color-hint" class="muted small">«Авто» использует исходный цвет шаблона. Цвет можно менять одним нажатием.</p>
@if($errors->any())<p role="alert" style="color:#b42318">{{ $errors->first() }}</p>@endif
<button class="primary" type="submit">Сохранить шаблон</button>
</form>
</section>
<section aria-label="Предпросмотр итоговой сторис">
<canvas class="story-edit-preview" id="storyPreview" width="1080" height="1920"></canvas>
<p id="previewMessage" class="story-preview-status" role="status">Подготовка предпросмотра…</p>
</section>
</div>
@php
$storyPreviewData = [
    'theme' => $theme,
    'template' => $template,
    'photo_url' => $config['photo_url'],
    'default_photo_url' => 'https://images.unsplash.com/'.$photo.'?auto=format&fit=crop&w=1080&h=1920&q=85',
    'image_url' => $config['image_url'],
    'image_position' => $config['image_position'],
    'image_horizontal' => $config['image_horizontal'],
    'image_zoom' => $config['image_zoom'],
    'font' => $config['font'],
    'scale' => $config['scale'],
    'shade' => $config['shade'],
    'text_color' => $config['text_color'],
];
@endphp
<script type="application/json" id="story-preview-data">@json($storyPreviewData)</script>
<script>
document.querySelectorAll('.story-color-swatch').forEach(button=>{
 button.addEventListener('click',()=>{
  const field=document.getElementById('text_color');
  field.value=button.dataset.color;
  field.dispatchEvent(new Event('input',{bubbles:true}));
  document.querySelectorAll('.story-color-swatch').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));
 });
});
document.getElementById('text_color').addEventListener('change',event=>{
 document.querySelectorAll('.story-color-swatch').forEach(item=>item.setAttribute('aria-pressed',String(item.dataset.color===event.target.value)));
});
</script>
@vite('resources/js/admin-story-preview.ts')
@endsection
