@extends('admin.layout')
@section('title', 'Удаление аккаунта')
@section('content')
<section class="panel password-form">
<h1>Удалить аккаунт?</h1>
<p><strong>{{ $master?->display_name ?: $user->name }}</strong> <span class="muted">#{{ $user->id }}</span></p>
<p class="muted">{{ $user->phone ?: $user->email ?: 'Контакты не указаны' }}</p>
<p>Аккаунт будет удалён без возможности восстановления через сайт.</p>
@if($master)
<p>Также удалятся страница мастера, расписание, фотографии и все связанные данные:</p>
<ul class="delete-summary">@foreach($counts as $label => $count)<li>{{ $label }}: {{ $count }}</li>@endforeach</ul>
@endif
<p class="muted small">Вход в удалённый аккаунт станет недоступен.</p>
<form method="post" action="{{ route('admin.users.destroy', $user->id) }}">
@csrf
@method('DELETE')
<input type="hidden" name="confirm_user_id" value="{{ $user->id }}">
<label class="deletion-confirmation"><input type="checkbox" name="confirm_deletion" value="1" required>Я подтверждаю удаление этого аккаунта и связанных данных.</label>
<div class="row delete-actions">
<a class="button" href="{{ route('admin.users') }}">Отмена</a>
<button class="danger-primary">Удалить навсегда</button>
</div>
</form>
</section>
@endsection
