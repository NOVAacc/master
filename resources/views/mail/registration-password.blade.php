<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Подтвердите почту — BOSH</title></head>
<body style="margin:0;padding:0;background:#f5f5f7;color:#202124;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif">
<div style="display:none;font-size:1px;color:#f5f5f7;max-height:0;overflow:hidden">Подтвердите почту, чтобы активировать профиль BOSH.</div>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f7"><tr><td align="center" style="padding:38px 14px">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:540px;background:#ffffff;border:1px solid #e8e8ed;border-radius:16px">
<tr><td style="padding:32px 36px 16px;font-size:24px;font-weight:800;letter-spacing:-1px">BOSH<span style="color:#ec4545">.</span></td></tr>
<tr><td style="padding:8px 36px 0"><h1 style="font-size:26px;line-height:1.25;letter-spacing:-.7px;margin:0 0 14px">Здравствуйте, {{ $name }}!</h1>
<p style="font-size:15px;line-height:1.7;color:#53545a;margin:0">Подтвердите электронную почту, чтобы активировать профиль BOSH.</p></td></tr>
<tr><td style="padding:28px 36px 12px"><a href="{{ $verifyUrl }}" style="display:block;text-align:center;text-decoration:none;background:#222327;color:#ffffff;padding:15px 18px;border-radius:10px;font-size:15px;font-weight:600">Подтвердить почту</a>
<p style="font-size:13px;color:#777883;line-height:1.6;margin:16px 0 0">Ссылка действует 60 минут. После подтверждения войдите в кабинет с паролем, который вы выбрали при регистрации.</p></td></tr>
<tr><td style="padding:12px 36px 32px"><p style="font-size:13px;color:#777883;line-height:1.6">Если ссылка истекла, запросите новое письмо на <a href="{{ route('login') }}" style="color:#222327">странице входа</a>. Если вы не регистрировались в BOSH, проигнорируйте это сообщение.</p>
<p style="font-size:12px;color:#92939b;line-height:1.6;margin:16px 0 0">Если кнопка не работает, откройте ссылку:<br><a href="{{ $verifyUrl }}" style="color:#595a64;word-break:break-all">{{ $verifyUrl }}</a></p></td></tr>
</table><p style="font-size:12px;color:#a0a0a8;margin:20px 0">BOSH · <a href="https://bosh.kg" style="color:#777883">bosh.kg</a></p></td></tr></table>
</body></html>
