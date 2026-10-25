@extends('admin.layout')
@section('title', 'Вход администратора')
@section('content')
<section class="panel login">
<h1>Вход администратора</h1>
<p class="muted">Управление BOSH</p>
<form method="post" action="{{ route('admin.login.submit') }}">
@csrf
<label for="username">Логин</label>
<input id="username" name="username" value="{{ old('username') }}" required maxlength="100" autocomplete="username" autofocus>
<label for="password">Пароль</label>
<input id="password" name="password" type="password" required maxlength="128" autocomplete="current-password">
@if($turnstileSiteKey && $errors->has('captcha'))
<div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<button class="primary">Войти</button>
</form>
</section>
@endsection
