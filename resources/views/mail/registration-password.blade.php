<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Добро пожаловать в BOSH</title></head>
<body style="margin:0;padding:0;background:#f5f5f7;color:#202124;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif">
<div style="display:none;font-size:1px;color:#f5f5f7;max-height:0;overflow:hidden">Подтвердите почту одним нажатием, затем войдите с временным паролем.</div>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f7"><tr><td align="center" style="padding:38px 14px">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:540px;background:#ffffff;border:1px solid #e8e8ed;border-radius:20px">
<tr><td style="padding:34px 38px 18px"><span style="font-size:23px;font-weight:800;letter-spacing:-1px">BOSH<span style="color:#ec4545">.</span></span></td></tr>
<tr><td style="padding:12px 38px 0"><h1 style="font-size:28px;line-height:1.2;letter-spacing:-.8px;margin:0 0 14px;font-weight:700">Здравствуйте, {{ $name }}!</h1>
<p style="font-size:15px;line-height:1.7;color:#53545a;margin:0">Ваша страница мастера в BOSH почти готова. Осталось подтвердить электронную почту и войти в кабинет.</p></td></tr>
<tr><td style="padding:28px 38px 10px"><div style="font-size:12px;letter-spacing:.07em;color:#777883;font-weight:700;margin-bottom:12px">ШАГ 1 · ПОДТВЕРДИТЕ ПОЧТУ</div>
<a href="{{ $verifyUrl }}" style="display:block;text-align:center;text-decoration:none;background:#222327;color:#ffffff;padding:15px 18px;border-radius:12px;font-size:15px;font-weight:650">Подтвердить почту</a>
<p style="font-size:12px;color:#84858e;line-height:1.6;margin:12px 0 0">Кнопка действует 60 минут. После нажатия откроется страница входа.</p></td></tr>
<tr><td style="padding:23px 38px 0"><div style="border-top:1px solid #ededf0;padding-top:22px">
<div style="font-size:12px;letter-spacing:.07em;color:#777883;font-weight:700;margin-bottom:12px">ШАГ 2 · ВОЙДИТЕ В КАБИНЕТ</div>
<p style="font-size:14px;line-height:1.6;margin:0 0 12px;color:#53545a">На странице входа используйте вашу почту или телефон и временный пароль:</p>
<div style="border:1px solid #e6e6e9;background:#f9f9fb;border-radius:12px;padding:18px">
<div style="font-size:11px;letter-spacing:.08em;color:#797b83;text-transform:uppercase">Временный пароль</div>
<div style="font-size:27px;letter-spacing:3px;font-family:Consolas,Monaco,monospace;font-weight:700;margin-top:7px;color:#222327">{{ $temporaryPassword }}</div>
</div>
<p style="font-size:13px;line-height:1.7;color:#6b6c74">Телефон: {{ $phone }}. После входа рекомендуем изменить пароль в разделе «Безопасность аккаунта».</p>
</div></td></tr>
<tr><td style="padding:12px 38px 36px"><p style="font-size:13px;color:#757780;line-height:1.7;margin:0">Если вы не регистрировались в BOSH, просто проигнорируйте письмо.</p>
<p style="font-size:12px;color:#92939b;line-height:1.6;margin:16px 0 0">Если кнопка не работает, скопируйте ссылку в браузер:<br><a href="{{ $verifyUrl }}" style="color:#595a64;word-break:break-all">{{ $verifyUrl }}</a></p></td></tr>
</table><p style="font-size:12px;color:#a0a0a8;margin:20px 0">BOSH · Время для вашего дела · <a href="https://bosh.kg" style="color:#777883">bosh.kg</a></p>
</td></tr></table>
</body></html>