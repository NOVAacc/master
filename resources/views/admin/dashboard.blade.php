@extends('admin.layout')
@section('title', 'Обзор')
@section('content')
<h1>Обзор</h1>
<p class="muted">Основные показатели BOSH</p>
<section class="admin-stats" aria-label="Статистика пользователей">
    <div class="panel admin-stat">
        <p class="admin-stat-label">Всего пользователей</p>
        <strong class="admin-stat-number">{{ number_format($totalUsers, 0, ',', ' ') }}</strong>
    </div>
    <div class="panel admin-stat">
        <p class="admin-stat-label">Зарегистрировано сегодня</p>
        <strong class="admin-stat-number">{{ number_format($registeredToday, 0, ',', ' ') }}</strong>
    </div>
    <div class="panel admin-stat">
        <p class="admin-stat-label">Профилей мастеров</p>
        <strong class="admin-stat-number">{{ number_format($masters, 0, ',', ' ') }}</strong>
        <p class="muted small">Без тестовых профилей</p>
    </div>
</section>
<div class="admin-dashboard-actions">
    <a class="primary button" href="{{ route('admin.users') }}">Пользователи</a>
    <a class="button" href="{{ route('admin.categories') }}">Категории</a>
</div>
@endsection
