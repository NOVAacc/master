<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=bosh-20261022">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=bosh-20261022">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · BOSH</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#fafafa;color:#252525;font:14px/1.5 Inter,system-ui,-apple-system,sans-serif}
        a{color:inherit;text-decoration:none}a:hover{text-decoration:underline}button,input{font:inherit}
        button,a,input{outline-offset:4px}button{cursor:pointer}main{max-width:1100px;padding:32px 24px;margin:auto}
        header{border-bottom:1px solid #e8e8e8;background:#fff}header>div{max-width:1100px;margin:auto;padding:16px 24px;display:flex;gap:24px;align-items:center;flex-wrap:wrap}
        .wordmark{font-weight:800;font-size:20px;letter-spacing:-1px}.muted{color:#777}.small{font-size:12px}
        nav{display:flex;gap:20px;align-items:center;flex:1}nav a[aria-current=page]{font-weight:650}h1{font-size:28px;letter-spacing:-.8px;margin:0 0 8px}h2{font-size:18px;margin:0 0 18px}
        .panel{background:white;border:1px solid #e8e8e8;border-radius:16px;padding:24px;margin:24px 0}
        .row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.between{justify-content:space-between}.button,button{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:999px;padding:10px 18px;background:#f1f1f1;color:#333;white-space:nowrap}
        .primary{background:#252525;color:#fff}label{display:block;font-weight:600;font-size:13px;margin:18px 0 6px}input{width:100%;min-width:0;border:1px solid #e5e5e5;border-radius:12px;padding:11px 12px;background:#fff;color:#252525}
        .login{max-width:440px;margin:8vh auto}.login button{margin-top:24px;width:100%}.search{display:flex;gap:10px;margin:24px 0;max-width:520px}
        .cards{display:grid;gap:10px}.user{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:18px;border:1px solid #e8e8e8;border-radius:14px;background:#fff}.user strong{font-size:15px}.user p{margin:5px 0;overflow-wrap:anywhere}.user>div{min-width:0}
        .badge{display:inline-block;background:#f1f1f1;border-radius:999px;padding:3px 9px;font-size:11px}.pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:24px 0}.errors{padding:12px 16px;border-radius:12px;background:#fff0f0;color:#a42e3b;margin:18px 0}.notice{padding:12px 16px;background:#e4f5ed;color:#216c4b;border-radius:12px;margin:18px 0}
        .danger{background:#fff0f0;color:#a42e3b}.danger-primary{background:#b8323e;color:#fff}.deletion-confirmation{display:flex;align-items:flex-start;gap:10px;font-weight:400}.deletion-confirmation input{width:18px;height:18px;flex-shrink:0;margin-top:2px}.delete-summary{padding-left:20px}.delete-actions{margin-top:24px}
        .password-form{max-width:460px}.password-form button{margin-top:24px}
        @media(max-width:600px){main{padding:24px 12px}header>div{padding:14px 12px;gap:14px}header .wordmark{display:none}nav{gap:14px}.panel{padding:20px}.user{display:block}.user .user-side{margin-top:12px}.user-side{text-align:left}.pagination{font-size:12px}}
    </style>
</head>
<body>
<header><div><span class="wordmark">BOSH</span><span class="muted small">Администрирование</span>
@auth('admin')
<nav aria-label="Администрирование">
<a href="{{ route('admin.users') }}" @if(request()->routeIs('admin.users')) aria-current="page" @endif>Пользователи</a>
<a href="{{ route('admin.categories') }}" @if(request()->routeIs('admin.categories')) aria-current="page" @endif>Категории</a>
<a href="{{ route('admin.settings') }}" @if(request()->routeIs('admin.settings')) aria-current="page" @endif>Настройки</a>
</nav>
<form method="post" action="{{ route('admin.logout') }}">@csrf<button>Выйти</button></form>
@endauth
</div></header>
<main>
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('content')
</main>
</body>
</html>
