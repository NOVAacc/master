@extends('admin.layout')
@section('title', 'Настройки')
@section('content')
<h1>Настройки</h1>
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
