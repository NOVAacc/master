@extends('admin.layout')
@section('title', 'Пользователи')
@section('content')
<h1>Пользователи</h1>
<p class="muted">Всего: {{ $total }} · Зарегистрировано сегодня: {{ $registeredToday }}</p>
<div class="panel">
<strong>Тест регистрации мастера</strong>
<p class="muted small">Создаёт отдельный демонстрационный аккаунт с пустыми сферой и направлением. Пароль показывается один раз. Записи клиентов отключены.</p>
<form method="post" action="{{ route('admin.users.onboarding-test') }}">
@csrf
<button type="submit" class="button">Создать тестовый профиль</button>
</form>
@if(session('test_onboarding_credentials'))
@php($test = session('test_onboarding_credentials'))
<div class="panel" role="status">
<strong>Данные тестового входа — сохраните сейчас, пароль повторно не показывается</strong>
<p>Почта: <code>{{ $test['email'] }}</code></p>
<p>Пароль: <code>{{ $test['password'] }}</code></p>
<p><a href="{{ route('login') }}" target="_blank" rel="noopener">Открыть страницу входа</a></p>
</div>
@endif
</div>
<form method="get" action="{{ route('admin.users') }}" class="search" role="search">
<input name="q" value="{{ $search }}" maxlength="100" aria-label="Поиск пользователей" placeholder="Имя, телефон, почта или адрес страницы">
<button class="primary">Найти</button>
@if($search !== '')<a class="button" href="{{ route('admin.users') }}">Сбросить</a>@endif
</form>
<p class="muted small">Найдено: {{ $users->total() }}</p>
<div class="cards">
@forelse($users as $user)
<article class="user">
<div>
<strong>{{ $user->display_name ?: $user->name }}</strong> <span class="muted small">#{{ $user->id }}</span>
@if($user->display_name && $user->display_name !== $user->name)<p class="muted small">{{ $user->name }}</p>@endif
<p>{{ $user->phone ?: 'Телефон не указан' }} @if($user->phone_verified_at)<span class="badge">Подтверждён</span>@endif</p>
<p class="muted small">{{ $user->email ?: 'Почта не указана' }} @if($user->email_verified_at)· Подтверждена@endif</p>
</div>
<div class="user-side">
@if($user->login_locked_until && \Illuminate\Support\Carbon::parse($user->login_locked_until)->isFuture())
<p class="muted small" role="status">Вход заблокирован до {{ \Illuminate\Support\Carbon::parse($user->login_locked_until)->format('d.m.Y H:i') }} (UTC +6)</p>
@elseif($user->failed_login_attempts > 0)
<p class="muted small">Ошибок входа: {{ $user->failed_login_attempts }} из 5</p>
@endif
@if($user->failed_login_attempts > 0 || ($user->login_locked_until && \Illuminate\Support\Carbon::parse($user->login_locked_until)->isFuture()))
<form method="post" action="{{ route('admin.users.unlock', $user->id) }}" onsubmit="return confirm('Снять блокировку входа для этого пользователя?')">
@csrf
<button class="button small" type="submit">Снять блокировку входа</button>
</form>
@endif
<p class="muted small">Регистрация {{ $user->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
@if($user->slug)
<p><span class="badge">{{ $user->booking_enabled ? 'Приём записей открыт' : 'Приём записей закрыт' }}</span></p>
<a class="small" href="{{ route('public.master', $user->slug) }}" target="_blank" rel="noopener">Открыть страницу мастера ↗</a>
@else
<span class="badge">Профиль не заполнен</span>
@endif
@if($user->slug && \Illuminate\Support\Str::startsWith($user->slug, 'test-onboarding-') && \Illuminate\Support\Str::endsWith((string) $user->email, '@example.invalid'))
<form method="post" action="{{ route('admin.users.onboarding-reset', $user->id) }}">
@csrf
<button type="submit" class="button small">Сбросить заполнение профиля</button>
</form>
@endif
<p><a class="button danger small" href="{{ route('admin.users.delete', $user->id) }}">Удалить аккаунт</a></p>
</div>
</article>
@empty
<div class="panel muted">Пользователи не найдены.</div>
@endforelse
</div>
@if($users->hasPages())
<nav class="pagination" aria-label="Страницы пользователей">
@if($users->previousPageUrl())<a class="button" href="{{ $users->previousPageUrl() }}">Назад</a>@else<span></span>@endif
<span>{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
@if($users->nextPageUrl())<a class="button" href="{{ $users->nextPageUrl() }}">Далее</a>@else<span></span>@endif
</nav>
@endif
@endsection
