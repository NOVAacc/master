@extends('admin.layout')
@section('title', 'Редактор сторис категории')
@section('content')
<p><a href="{{ route('admin.categories.stories', ['industry'=>$category['industry'], 'activity'=>$category['activity']]) }}">← {{ $category['activity_name'] }} · все 8 шаблонов</a></p>
<h1>{{ $concept['name'] }}</h1>
<p class="muted">Шаблон «{{ $category['activity_name'] }}». Редактируется отдельно. Сохраняется только в админке; мастера получат доступ после этапа 3.</p>
<style>
.category-edit-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(210px,290px);gap:26px;max-width:850px;align-items:start}
.category-edit-form select{width:100%;padding:11px;border:1px solid #dedede;border-radius:11px;background:#fff}
.category-edit-form input[type=checkbox]{width:auto}.category-edit-form label{margin:15px 0 6px}
.category-story-preview{width:100%;aspect-ratio:9/16;border-radius:16px;position:relative;overflow:hidden;background:#f2ebe6;color:#171717;box-shadow:0 5px 22px #00000018}
.category-story-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform-origin:center}
.category-story-tint{position:absolute;inset:0;background:#000;opacity:0}
.category-story-copy{position:absolute;z-index:2;inset:9% 9% 8%;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden}
.category-story-copy .brand{font-weight:800;font-size:clamp(12px,1.8vw,17px);letter-spacing:.02em}
.category-story-copy .center strong{display:block;font-size:clamp(21px,3.5vw,33px);line-height:1.08;overflow-wrap:anywhere}
.category-story-copy .center span{display:block;font-size:clamp(11px,1.5vw,14px);margin:7px 0}
.category-story-copy .bottom strong{display:block;font-size:clamp(26px,4vw,39px);line-height:1}
.category-story-copy .bottom span{display:block;font-size:clamp(10px,1.6vw,13px);margin:7px 0 14px}
.category-story-cta{display:block;border:1px solid currentColor;border-radius:50px;text-align:center;padding:10px 12px;font-size:13px;font-weight:700}
.category-story-preview.role-premium{background:linear-gradient(140deg,#181614,#55483b);color:#fff5e7}
.category-story-preview.role-editorial{background:linear-gradient(135deg,#f7f3ed,#ddd5cb)}
.category-story-preview.role-proof{background:linear-gradient(135deg,#2c343c,#6d7a7d);color:white}
.category-story-preview.role-warm{background:linear-gradient(135deg,#ead0ba,#d49c83)}
.category-story-preview.role-urban{background:linear-gradient(135deg,#202027,#8a727b);color:white}
.category-story-preview.role-soft{background:linear-gradient(135deg,#f7e6e9,#e2dad5)}
.category-story-preview.role-promo{background:linear-gradient(135deg,#a6223a,#ee6963);color:white}
.category-story-preview.role-seasonal{background:linear-gradient(135deg,#62775f,#c2ad8b);color:white}
.category-swatches{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}
.category-swatches button{padding:3px;min-width:34px;width:34px;height:34px;border:2px solid transparent;border-radius:50%;background:white}
.category-swatches button[aria-pressed=true]{border-color:#222}
.category-swatches button span{display:block;width:24px;height:24px;border:1px solid #ccc;border-radius:50%}
.category-preview-status{font-size:12px;color:#777;margin:10px 0;text-align:center}
@media(max-width:650px){.category-edit-grid{grid-template-columns:1fr}.category-edit-form{order:2}.category-preview-container{order:1;max-width:260px;margin:0 auto}}
</style>
<div class="category-edit-grid">
<section class="panel category-edit-form">
<form method="post" action="{{ route('admin.categories.stories.update', ['industry'=>$category['industry'],'activity'=>$category['activity'],'role'=>$role]) }}" enctype="multipart/form-data" id="categoryStoryForm">
@csrf
<label for="image">Фотография (JPG, PNG, WebP, до 4 МБ)</label>
<input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
<p class="muted small">Рекомендуем вертикальный кадр 9:16, живую фотографию процесса или результата. Фотография хранится отдельно для этого шаблона.</p>
<label style="display:flex;align-items:center;gap:8px;font-weight:400"><input id="remove_image" name="remove_image" type="checkbox" value="1"> Убрать фотографию и вернуть фон концепции</label>
<label for="font">Шрифт</label>
<select name="font" id="font">@foreach($fonts as $id=>$name)<option value="{{ $id }}" @selected($config['font']===$id)>{{ $name }}</option>@endforeach</select>
<label for="scale">Размер заголовка</label>
<select name="scale" id="scale">@foreach([80,90,100,110,120] as $v)<option value="{{ $v }}" @selected($config['scale']===$v)>{{ $v }}%</option>@endforeach</select>
<label for="text_color">Цвет текста</label>
<select name="text_color" id="text_color">@foreach($colors as $hex=>$name)<option value="{{ $hex }}" @selected($config['text_color']===$hex)>{{ $name }}</option>@endforeach</select>
<div class="category-swatches" aria-label="Цвет текста">
@foreach($colors as $hex=>$name)
<button type="button" data-color="{{ $hex }}" title="{{ $name }}" aria-label="{{ $name }}" aria-pressed="{{ $config['text_color']===$hex ? 'true':'false' }}"><span style="{{ $hex ? 'background:'.$hex : 'background:conic-gradient(#fff 0 25%,#222 0 50%,#fff 0 75%,#222 0)' }}"></span></button>
@endforeach
</div>
<label for="image_position">Вертикальное положение фотографии</label>
<select name="image_position" id="image_position">@foreach(['top'=>'Верх','center'=>'Центр','bottom'=>'Низ'] as $v=>$name)<option value="{{ $v }}" @selected($config['image_position']===$v)>{{ $name }}</option>@endforeach</select>
<label for="image_horizontal">Горизонтальное положение</label>
<select name="image_horizontal" id="image_horizontal">@foreach(['left'=>'Слева','center'=>'Центр','right'=>'Справа'] as $v=>$name)<option value="{{ $v }}" @selected($config['image_horizontal']===$v)>{{ $name }}</option>@endforeach</select>
<label for="image_zoom">Увеличение фото</label>
<select name="image_zoom" id="image_zoom">@foreach([100,110,120,130,140,150] as $v)<option value="{{ $v }}" @selected($config['image_zoom']===$v)>{{ $v }}%</option>@endforeach</select>
<label for="shade">Дополнительное затемнение</label>
<select name="shade" id="shade">@foreach([0,10,20,30,40] as $v)<option value="{{ $v }}" @selected($config['shade']===$v)>{{ $v }}%</option>@endforeach</select>
<button type="submit" class="primary" style="margin-top:23px">Сохранить шаблон</button>
</form>
</section>
<section class="category-preview-container" aria-label="Предпросмотр шаблона">
<div id="storyPreview" class="category-story-preview role-{{ $role }}">
<img id="storyPhoto" class="category-story-photo" src="{{ $config['image_url'] ?? '' }}" alt="" @if(!$config['image_url']) hidden @endif>
<div id="storyTint" class="category-story-tint"></div>
<div id="storyCopy" class="category-story-copy">
<div class="brand">BOSH</div>
<div class="center"><span>ЕСТЬ СВОБОДНОЕ ВРЕМЯ</span><strong id="storyHeading">{{ $category['activity_name'] }}</strong><span>Пример услуги</span></div>
<div class="bottom"><strong>16:30</strong><span>25 октября · 1 500 сом</span><div class="category-story-cta">Записаться</div></div>
</div>
</div>
<p class="category-preview-status" id="previewStatus" role="status">Макет предварительный: точный PNG будет проверен на этапе 3.</p>
</section>
</div>
<script>
(()=>{
 const form=document.getElementById('categoryStoryForm'),img=document.getElementById('storyPhoto'),preview=document.getElementById('storyPreview');
 const copy=document.getElementById('storyCopy'),heading=document.getElementById('storyHeading'),tint=document.getElementById('storyTint');
 const original=img.getAttribute('src')||'';
 let blob=null;
 const fonts={system:'system-ui,sans-serif',serif:'Georgia,serif',rounded:'Trebuchet MS,Arial,sans-serif',elegant:'Palatino,Georgia,serif',classic:'Baskerville,Georgia,serif',bold:'Arial Black,Arial,sans-serif',mono:'Courier New,monospace'};
 function update(){
  const color=form.text_color.value;
  copy.style.color=color||'';
  copy.style.fontFamily=fonts[form.font.value]||fonts.system;
  heading.style.fontSize='clamp(21px,'+(3.5*Number(form.scale.value)/100)+'vw,'+(33*Number(form.scale.value)/100)+'px)';
  const x={left:'left',center:'center',right:'right'}[form.image_horizontal.value]||'center';
  const y={top:'top',center:'center',bottom:'bottom'}[form.image_position.value]||'center';
  img.style.objectPosition=x+' '+y;
  img.style.transform='scale('+Number(form.image_zoom.value)/100+')';
  tint.style.opacity=String(Number(form.shade.value)/100);
  const hasCustom=Boolean(form.image.files.length);
  const shown=hasCustom||(!form.remove_image.checked&&Boolean(original));
  img.hidden=!shown;
  if(!shown)img.removeAttribute('src');
  else if(hasCustom&&blob)img.src=blob;
  else if(original&&img.getAttribute('src')!==original)img.src=original;
 }
 form.querySelectorAll('select').forEach(x=>x.addEventListener('change',update));
 form.image.addEventListener('change',()=>{
  if(blob)URL.revokeObjectURL(blob);blob=null;
  const file=form.image.files[0];
  if(file){
   if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>4194304){alert('Выберите JPG, PNG или WebP до 4 МБ');form.image.value='';}
   else{form.remove_image.checked=false;blob=URL.createObjectURL(file);}
  }
  update();
 });
 form.remove_image.addEventListener('change',()=>{
  if(form.remove_image.checked){form.image.value='';if(blob)URL.revokeObjectURL(blob);blob=null;}
  update();
 });
 const buttons=[...document.querySelectorAll('.category-swatches button')];
 buttons.forEach(button=>button.addEventListener('click',()=>{
  form.text_color.value=button.dataset.color;
  buttons.forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
  update();
 }));
 form.text_color.addEventListener('change',()=>buttons.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.color===form.text_color.value))));
 window.addEventListener('pagehide',()=>{if(blob)URL.revokeObjectURL(blob)},{once:true});
 update();
})();
</script>
@endsection