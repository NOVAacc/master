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

/* Admin story layout editor: proportionate 9:16 composition, bounded draggable blocks. */
.story-items{grid-template-columns:1fr}
.story-item{grid-template-columns:180px minmax(0,1fr);max-width:900px;align-items:start}
.story-frame{width:180px;max-width:100%;touch-action:none;cursor:grab}
.story-frame:active{cursor:grabbing}
.story-frame strong{top:7%;left:9%;font-size:13px;max-width:80%}
.story-frame .story-edit-title,.story-frame .story-edit-details{position:absolute;z-index:2;left:9%;width:82%;color:#28232a;line-height:1.18;cursor:move;user-select:none;touch-action:none;text-shadow:0 1px 2px #ffffffa3}
.story-edit-title{top:30%;font-size:9px;font-weight:600}
.story-edit-title b{display:inline-block;margin-top:5px;font-size:19px;line-height:1.1}
.story-edit-title small{display:inline-block;margin-top:5px;font-size:11px}
.story-frame .story-edit-details{top:63.5%;font-size:22px;font-weight:800}
.story-edit-details small{font-size:10px;display:block;margin-top:6px}
.story-edit-details span{position:static;bottom:auto;left:auto;z-index:auto;display:block;margin-top:18px;text-align:center;padding:8px 4px;background:#2a2a2c;color:white;border-radius:50px;font-size:12px;text-shadow:none}
.story-edit-title:hover,.story-edit-details:hover{outline:1px dashed #717171;border-radius:3px}
.story-positions{border:1px solid #e5e5e5;border-radius:12px;margin-top:12px;padding:10px}
.story-positions legend{padding:0 5px;font-size:12px;font-weight:650}
.story-position-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px 12px}
.story-position-grid label{display:flex;justify-content:space-between;gap:8px;font-size:11px;margin:4px 0}
.story-position-grid output{color:#888;white-space:nowrap}
.story-position-grid input{width:100%}
.story-reset{font-size:11px;padding:6px 11px;margin-top:9px}
@media(max-width:600px){.story-item{grid-template-columns:1fr}.story-frame{width:180px;margin:0 auto}.story-position-grid{grid-template-columns:1fr 1fr}}
@media(max-width:390px){.story-frame{width:170px}.story-position-grid{grid-template-columns:1fr}}
</style>
<div class="story-groups">
@foreach($collections as $theme => $collection)
<section class="panel" id="collection-{{ $theme }}">
<h2>{{ $collection['title'] }}</h2>
<div class="story-items">
@foreach($collection['ids'] as $template)
@php($config = $catalog[$theme][$template])
<article class="story-item">
@php($previewImage = $config['image_url'] ?: 'https://images.unsplash.com/'.$photos[$theme][$template].'?auto=format&fit=crop&w=200&h=360&q=60')
<div class="story-frame" role="group" aria-label="Интерактивное позиционирование фотографии и текстовых блоков" style="background-image:url('{{ $previewImage }}');background-position:{{ $config['focal_x'] }}% {{ $config['focal_y'] }}%">
<strong>BOSH</strong>
<div class="story-edit-title" data-drag="title" title="Перетащите, чтобы сместить заголовок">ЕСТЬ СВОБОДНОЕ ВРЕМЯ<br><b>{{ $collections[$theme]['title'] }}</b><br><small>Услуга мастера</small></div>
<div class="story-edit-details" data-drag="details" title="Перетащите, чтобы сместить детали">12:30<br><small>25 октября · 1 500 сом</small><span>Записаться</span></div>
</div>
<div>
<h3>{{ $labels[$theme][$template] ?? $template }}</h3><p class="muted">ID: {{ $template }}</p>
<p class="muted">{{ $config['image_url'] ? 'Загружено своё изображение' : 'Используется стандартное фото' }}</p>
<form action="{{ route('admin.stories.update', ['theme' => $theme, 'template' => $template]) }}" method="post" enctype="multipart/form-data">
@csrf
<label>Фотография JPG / PNG / WebP, до 4 МБ</label>
<input name="image" type="file" accept="image/jpeg,image/png,image/webp">
<p class="muted small">На изображении можно перетаскивать заголовок и блок времени. Потяните за свободную часть фотографии, чтобы сместить кадр.</p>
<div class="story-controls">
<div><label>Шрифт</label><select name="font">@foreach($fonts as $font => $label)<option value="{{ $font }}" @selected($config['font'] === $font)>{{ $label }}</option>@endforeach</select></div>
<div><label>Размер заголовка</label><select name="scale">@foreach([80,90,100,110,120] as $size)<option value="{{ $size }}" @selected($config['scale'] === $size)>{{ $size }}%</option>@endforeach</select></div>
<div><label>Цвет текста (необязательно)</label><input name="text_color" type="text" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="Автоматически" value="{{ $config['text_color'] }}"></div>
<div><label>Сброс фотографии</label><label style="display:flex;align-items:center;gap:6px;font-weight:400"><input type="checkbox" name="remove_image" value="1" style="width:auto"> Стандартная</label></div>
</div>
<fieldset class="story-positions"><legend>Композиция</legend><div class="story-position-grid">
<div><label>Фото по горизонтали <output data-value="focal_x">{{ $config['focal_x'] }}%</output></label><input type="range" name="focal_x" min="0" max="100" value="{{ $config['focal_x'] }}"></div>
<div><label>Фото по вертикали <output data-value="focal_y">{{ $config['focal_y'] }}%</output></label><input type="range" name="focal_y" min="0" max="100" value="{{ $config['focal_y'] }}"></div>
<div><label>Заголовок X <output data-value="title_x">{{ $config['title_x'] }}</output></label><input type="range" name="title_x" min="-50" max="50" value="{{ $config['title_x'] }}"></div>
<div><label>Заголовок Y <output data-value="title_y">{{ $config['title_y'] }}</output></label><input type="range" name="title_y" min="-50" max="50" value="{{ $config['title_y'] }}"></div>
<div><label>Детали X <output data-value="details_x">{{ $config['details_x'] }}</output></label><input type="range" name="details_x" min="-50" max="50" value="{{ $config['details_x'] }}"></div>
<div><label>Детали Y <output data-value="details_y">{{ $config['details_y'] }}</output></label><input type="range" name="details_y" min="-30" max="30" value="{{ $config['details_y'] }}"></div>
</div><button type="button" class="story-reset">Сбросить расположение</button></fieldset>
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
 const form=card.querySelector('form'),frame=card.querySelector('.story-frame');
 const title=frame.querySelector('.story-edit-title'),details=frame.querySelector('.story-edit-details'),wordmark=frame.querySelector('strong');
 const control=name=>form.elements.namedItem(name);
 const inputs=['font','scale','text_color','image','focal_x','focal_y','title_x','title_y','details_x','details_y'];
 const initialImage=frame.style.backgroundImage;
 let previewUrl=null,gesture=null;
 const clamp=(n,min,max)=>Math.min(max,Math.max(min,n));
 const read=name=>Number(control(name).value);
 const render=()=>{
  const font={system:'system-ui,sans-serif',serif:'Georgia,serif',rounded:'Trebuchet MS,sans-serif'}[control('font').value]||'system-ui,sans-serif';
  [wordmark,title,details].forEach(node=>node.style.fontFamily=font);
  const color=/^#[a-fA-F0-9]{6}$/.test(control('text_color').value)?control('text_color').value:'#29272b';
  [wordmark,title,details].forEach(node=>node.style.color=color);
  title.querySelector('b').style.fontSize=(19*read('scale')/100)+'px';
  const factor=frame.clientWidth/1080;
  title.style.transform='translate('+(read('title_x')*factor)+'px,'+(read('title_y')*factor)+'px)';
  details.style.transform='translate('+(read('details_x')*factor)+'px,'+(read('details_y')*factor)+'px)';
  frame.style.backgroundPosition=read('focal_x')+'% '+read('focal_y')+'%';
  inputs.slice(4).forEach(name=>{const out=form.querySelector('[data-value="'+name+'"]');if(out)out.textContent=control(name).value+(name.startsWith('focal')?'%':'');});
 };
 inputs.filter(name=>name!=='image').forEach(name=>control(name).addEventListener('input',render));
 control('image').addEventListener('change',()=>{
  if(previewUrl) URL.revokeObjectURL(previewUrl);
  previewUrl=null;
  const file=control('image').files[0];
  if(file&&['image/jpeg','image/png','image/webp'].includes(file.type)){
   previewUrl=URL.createObjectURL(file);
   frame.style.backgroundImage='url("'+previewUrl+'")';
  }else frame.style.backgroundImage=initialImage;
  render();
 });
 frame.addEventListener('pointerdown',event=>{
  if(event.button!==0)return;
  const drag=event.target.closest('[data-drag]');
  const kind=drag?.dataset.drag||'photo';
  gesture={kind,x:event.clientX,y:event.clientY,
   initialX:read(kind==='photo'?'focal_x':kind+'_x'),
   initialY:read(kind==='photo'?'focal_y':kind+'_y')};
  frame.setPointerCapture(event.pointerId);
  event.preventDefault();
 });
 frame.addEventListener('pointermove',event=>{
  if(!gesture)return;
  const ratio=1080/frame.clientWidth,dx=(event.clientX-gesture.x)*ratio,dy=(event.clientY-gesture.y)*ratio;
  const keyX=gesture.kind==='photo'?'focal_x':gesture.kind+'_x';
  const keyY=gesture.kind==='photo'?'focal_y':gesture.kind+'_y';
  const scale=gesture.kind==='photo'?.12:1;
  [keyX,keyY].forEach((key,i)=>{
   const input=control(key),delta=i===0?dx:dy;
   input.value=String(Math.round(clamp(gesture[i===0?'initialX':'initialY']+delta*scale*(gesture.kind==='photo'?-1:1),Number(input.min),Number(input.max))));
  });
  render();
 });
 const stop=()=>{gesture=null;};
 frame.addEventListener('pointerup',stop);frame.addEventListener('pointercancel',stop);frame.addEventListener('lostpointercapture',stop);
 card.querySelector('.story-reset').addEventListener('click',()=>{
  for(const name of inputs.slice(4))control(name).value=name.startsWith('focal')?'50':'0';
  render();
 });
 window.addEventListener('pagehide',()=>{if(previewUrl)URL.revokeObjectURL(previewUrl);},{once:true});
 render();
});
</script>
@endsection
