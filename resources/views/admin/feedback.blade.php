@extends('admin.layout')
@section('title', 'Обратная связь')
@section('content')
<h1>Обратная связь</h1>
<p class="muted">Идеи, замечания и ошибки от мастеров · Новых: {{ $newCount }}</p>
<div class="row">
@foreach(['' => 'Все', 'new' => 'Новые', 'reviewed' => 'Просмотренные'] as $value => $label)
<a class="button {{ ($status ?? '') === $value ? 'primary' : '' }}" href="{{ route('admin.feedback', $value ? ['status' => $value] : []) }}">{{ $label }}</a>
@endforeach
</div>
@forelse($feedback as $item)
<article class="panel">
<div class="row between"><strong>{{ ['idea' => 'Идея', 'bug' => 'Ошибка', 'other' => 'Замечание'][$item->category] }}</strong><span class="badge">{{ $item->status === 'new' ? 'Новое' : 'Просмотрено' }}</span></div>
<p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $item->message }}</p>
<p class="small muted">{{ $item->user?->name ?? 'Аккаунт удалён' }} · {{ $item->user?->email }} · {{ $item->user?->phone }}<br>{{ $item->created_at->timezone('Asia/Bishkek')->format('d.m.Y H:i') }} · {{ $item->page }}</p>
<form method="post" action="{{ route('admin.feedback.update', $item) }}">@csrf @method('PATCH')
<input type="hidden" name="status" value="{{ $item->status === 'new' ? 'reviewed' : 'new' }}">
<button>{{ $item->status === 'new' ? 'Отметить просмотренным' : 'Вернуть в новые' }}</button>
</form>
</article>
@empty
<div class="panel">{{ $status ? 'В этом разделе пока нет обращений.' : 'Пока нет обращений. Новые сообщения мастеров появятся здесь.' }}</div>
@endforelse
<nav class="pagination" aria-label="Страницы обращений">
@if($feedback->previousPageUrl())<a href="{{ $feedback->previousPageUrl() }}">← Назад</a>@else<span></span>@endif
<span>{{ $feedback->currentPage() }} / {{ $feedback->lastPage() }}</span>
@if($feedback->nextPageUrl())<a href="{{ $feedback->nextPageUrl() }}">Далее →</a>@endif
</nav>
@endsection
