@extends('admin.layout')
@section('title', 'Настройки')
@section('content')
<h1>Настройки</h1>
<section class="panel" style="max-width:720px">
<h2>Telegram · Уведомления о записях</h2>
<p class="muted">Интервалы применяются ко всем новым и предстоящим записям. Уведомления отправляются только подключённым пользователям. Отменённые записи исключаются, повторные сообщения блокируются.</p>
<form method="post" action="{{ route('admin.settings.notifications') }}">
@csrf
@foreach ([
 ['client_24h', 'Клиент · первое напоминание', 'client_24h_minutes', 10080],
 ['client_2h', 'Клиент · второе напоминание', 'client_2h_minutes', 10080],
 ['master_30m', 'Мастер · перед приёмом', 'master_30m_minutes', 1440]
] as [$key, $title, $minutesKey, $limit])
<div style="display:flex;gap:16px;align-items:end;flex-wrap:wrap;padding:14px 0;border-bottom:1px solid #eee">
<div style="flex:1;min-width:180px">
<label for="{{ $key }}_enabled" style="margin-top:0">{{ $title }}</label>
<input type="hidden" name="{{ $key }}_enabled" value="0">
<input type="checkbox" id="{{ $key }}_enabled" name="{{ $key }}_enabled" value="1" style="width:20px;height:20px" @checked(old($key.'_enabled', $notifications[$key.'_enabled']))>
</div>
<div style="width:180px">
<label for="{{ $minutesKey }}">За сколько минут</label>
<input id="{{ $minutesKey }}" name="{{ $minutesKey }}" type="number" min="1" max="{{ $limit }}" required value="{{ old($minutesKey, $notifications[$minutesKey]) }}">
</div>
</div>
@endforeach
<div style="display:flex;gap:16px;align-items:end;flex-wrap:wrap;padding:14px 0">
<div style="flex:1;min-width:180px">
<label for="master_digest_enabled">Мастер · сводка на завтра</label>
<input type="hidden" name="master_digest_enabled" value="0">
<input type="checkbox" id="master_digest_enabled" name="master_digest_enabled" value="1" style="width:20px;height:20px" @checked(old('master_digest_enabled', $notifications['master_digest_enabled']))>
</div>
<div style="width:180px">
<label for="master_digest_time">Время отправки</label>
<input id="master_digest_time" name="master_digest_time" type="time" required value="{{ old('master_digest_time', $notifications['master_digest_time']) }}">
</div>
</div>
<p class="muted small">Сводка отправляется по местному времени мастера, только при наличии записей на завтра. Для интервалов действует окно доставки до 10 минут после планового времени.</p>
<button class="primary" style="margin-top:18px">Сохранить уведомления</button>
</form>
</section>
<section class="panel password-form">
<h2>Социальные сети</h2>
<form method="post" action="{{ route('admin.settings.social') }}">
@csrf
<label for="instagram_username">Instagram BOSH</label>
<input id="instagram_username" name="instagram_username" value="{{ old('instagram_username', $instagramUsername) }}" required maxlength="31" autocomplete="off" autocapitalize="none" spellcheck="false">
<p class="muted small">Имя аккаунта, например bosh.kgz. Ссылка отображается в подвале сайта.</p>
<button class="primary">Сохранить Instagram</button>
</form>
</section>
<section class="panel password-form">
<h2>Смена пароля</h2>
<form method="post" action="{{ route('admin.password') }}">
@csrf
<label for="current_password">Текущий пароль</label>
<input id="current_password" name="current_password" type="password" required maxlength="128" autocomplete="current-password">
<label for="password">Новый пароль</label>
<input id="password" name="password" type="password" required minlength="10" maxlength="128" autocomplete="new-password">
<p class="muted small">Не менее 10 символов.</p>
<label for="password_confirmation">Повторите новый пароль</label>
<input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" maxlength="128" autocomplete="new-password">
<button class="primary">Сохранить пароль</button>
</form>
</section>
@endsection
