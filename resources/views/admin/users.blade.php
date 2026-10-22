@extends('admin.layout')
@section('title', 'Пользователи')
@section('content')
<h1>Пользователи</h1>
<p class="muted">Всего: {{ $total }} · Зарегистрировано сегодня: {{ $registeredToday }}</p>
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
<p class="muted small">Регистрация {{ $user->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
@if($user->slug)
<p><span class="badge">{{ $user->booking_enabled ? 'Приём записей открыт' : 'Приём записей закрыт' }}</span></p>
<a class="small" href="{{ route('public.master', $user->slug) }}" target="_blank" rel="noopener">Открыть страницу мастера ↗</a>
@else
<span class="badge">Профиль не заполнен</span>
@endif
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
