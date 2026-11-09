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
        <p class="admin-stat-label">Актуальные записи клиентов</p>
        <strong class="admin-stat-number">{{ number_format($upcomingAppointments, 0, ',', ' ') }}</strong>
        <p class="muted small">Ожидают или подтверждены · ещё не закончились</p>
    </div>
</section>
<div class="admin-dashboard-actions">
    <a class="primary button" href="{{ route('admin.users') }}">Пользователи</a>
    <a class="button" href="{{ route('admin.categories') }}">Категории</a>
</div>
@endsection
