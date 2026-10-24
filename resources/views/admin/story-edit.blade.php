@extends('admin.layout')
@section('title', 'Редактор шаблона')
@section('content')
<p><a href="{{ route('admin.stories') }}">← Все шаблоны</a></p>
<h1>{{ $title }} · {{ $label }}</h1>
<p class="muted">Изменяйте оформление только этого шаблона. Данные мастера, услуга, дата, цена, ссылка и логотип BOSH подставляются автоматически.</p>
<style>
.story-edit-layout{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:24px;align-items:start;max-width:900px}
.story-edit-layout .panel{margin:14px 0}.story-edit-form label{margin:13px 0 6px}
.story-edit-form select{width:100%;border:1px solid #e5e5e5;border-radius:10px;background:white;padding:11px}
.story-edit-form input[type=file]{font-size:12px}.story-edit-form input[type=checkbox]{width:auto}
.story-edit-preview{width:100%;max-width:270px;aspect-ratio:9/16;border-radius:16px;display:block;background:#eee;box-shadow:0 4px 24px #00000015}
.story-edit-form button{margin-top:16px}.story-preview-status{font-size:12px;line-height:1.4;text-align:center;margin:8px 0;color:#666}
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
<label for="image_position">Кадрирование фотографии</label>
<select id="image_position" name="image_position">
@foreach(['top' => 'По верхнему краю', 'center' => 'По центру', 'bottom' => 'По нижнему краю'] as $value => $name)
<option value="{{ $value }}" @selected(($config['image_position'] ?? 'center') === $value)>{{ $name }}</option>
@endforeach
</select>
<p class="muted small">Выберите, какая часть изображения останется видна после обрезки до 9:16. Настройка применяется и к скачанному PNG.</p>
<label for="font">Шрифт сторис</label>
<select id="font" name="font">@foreach($fonts as $key => $titleFont)<option value="{{ $key }}" @selected($config['font']===$key)>{{ $titleFont }}</option>@endforeach</select>
<label for="scale">Размер заголовка</label>
<select id="scale" name="scale">@foreach([80,90,100,110,120] as $size)<option value="{{ $size }}" @selected($config['scale']===$size)>{{ $size }}%</option>@endforeach</select>
<label for="text_color">Цвет текста</label>
<input id="text_color" name="text_color" value="{{ $config['text_color'] }}" placeholder="Автоматически" maxlength="7" pattern="#[a-fA-F0-9]{6}">
<p class="muted small">Оставьте пустым для штатной цветовой палитры шаблона.</p>
@if($errors->any())<p role="alert" style="color:#b42318">{{ $errors->first() }}</p>@endif
<button class="primary" type="submit">Сохранить шаблон</button>
</form>
</section>
<section aria-label="Предпросмотр итоговой сторис">
<canvas class="story-edit-preview" id="storyPreview" width="1080" height="1920"></canvas>
<p id="previewMessage" class="story-preview-status" role="status">Подготовка предпросмотра…</p>
</section>
</div>
<script type="application/json" id="story-preview-data">@json([
    'theme' => $theme,
    'template' => $template,
    'photo_url' => $config['photo_url'],
    'image_url' => $config['image_url'],
    'image_position' => $config['image_position'],
    'font' => $config['font'],
    'scale' => $config['scale'],
    'text_color' => $config['text_color'],
])</script>
@vite('resources/js/admin-story-preview.ts')
@endsection
