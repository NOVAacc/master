import { Head, Link, useForm } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { Brand, Errors, Field, Notice } from "../Shared";
import PhoneInput from "../PhoneInput";

function Turnstile({ siteKey, onToken }: { siteKey: string; onToken: (token: string) => void }) {
    const container = useRef<HTMLDivElement>(null);
    const [loadError, setLoadError] = useState(false);
    useEffect(() => {
        let active = true;
        let widgetId: string | undefined;
        const apiUrl = "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";
        const render = () => {
            const api = (window as any).turnstile;
            if (!active || !container.current || !api || widgetId !== undefined) return;
            try {
                widgetId = api.render(container.current, {
                    sitekey: siteKey,
                    callback: (token: string) => { if (active) onToken(token); },
                    "expired-callback": () => { if (active) onToken(""); },
                    "error-callback": () => { if (active) { onToken(""); setLoadError(true); } },
                });
                setLoadError(false);
            } catch {
                setLoadError(true);
            }
        };
        let script = document.querySelector<HTMLScriptElement>('script[data-bosh-turnstile]');
        const onError = () => { if (active) setLoadError(true); };
        if (!script) {
            script = document.createElement("script");
            script.src = apiUrl;
            script.async = true;
            script.dataset.boshTurnstile = "1";
            document.head.appendChild(script);
        }
        script.addEventListener("load", render);
        script.addEventListener("error", onError);
        if ((window as any).turnstile) render();
        return () => {
            active = false;
            script?.removeEventListener("load", render);
            script?.removeEventListener("error", onError);
            if (widgetId !== undefined) {
                try { (window as any).turnstile?.remove(widgetId); } catch {}
            }
        };
    }, [siteKey]);
    return <div className="auth-captcha">
        <div ref={container} />
        {loadError && <p className="muted" role="alert">Не удалось загрузить проверку Cloudflare. Отключите блокировщик содержимого для этого сайта и обновите страницу.</p>}
    </div>;
}

export default function Auth({ register, turnstileSiteKey }: { register: boolean; turnstileSiteKey?: string | null }) {
    const [smsMode, setSmsMode] = useState(false);
    const [sent, setSent] = useState(false);
    const [captchaRequired, setCaptchaRequired] = useState(false);
    const [captchaRevision, setCaptchaRevision] = useState(0);
    const f = useForm({ name: "", phone: "", email: "", code: "", identifier: "", password: "", "cf-turnstile-response": "" });
    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        if (register) {
            f.transform(d => ({ name: d.name, phone: d.phone, email: d.email }));
            f.post("/register");
        } else if (!smsMode) {
            f.transform(d => ({ identifier: d.identifier, password: d.password, "cf-turnstile-response": d["cf-turnstile-response"] }));
            f.post("/login", { onError: errors => { if (errors.captcha) setCaptchaRequired(true); setCaptchaRevision(value => value + 1); f.setData("cf-turnstile-response", ""); }, onFinish: () => f.setData("password", "") });
        } else if (sent) {
            f.transform(d => ({ code: d.code }));
            f.post("/auth/verify-code");
        } else {
            f.transform(d => ({ phone: d.phone, mode: "login" }));
            f.post("/auth/send-code", { onSuccess: () => setSent(true) });
        }
    }
    function switchSms() {
        setSmsMode(!smsMode); setSent(false); f.clearErrors();
    }
    return <main className="auth-wrap">
        <section className="auth-intro">
            <Brand />
            <h1>Время для вашего дела.</h1>
            <p>Услуги, расписание и запись клиентов — в одном кабинете.</p>
            <div className="intro-card">
                <span>Начните с простого</span>
                <h2>Ваше дело.<br />Ваше расписание.</h2>
                <p>Создайте аккаунт, заполните профиль и принимайте записи. BOSH — бесплатно.</p>
            </div>
        </section>
        <section className="auth-form panel">
            <Head title={register ? "Регистрация" : "Вход"} />
            <p className="eyebrow">BOSH · КАБИНЕТ МАСТЕРА</p>
            <h1>{register ? "Начать работу" : "Войти в BOSH"}</h1>
            <p className="muted">{register ? "Укажите имя, телефон и почту. В письме будет кнопка подтверждения и временный пароль." :
                !smsMode ? "Введите телефон или почту и ваш пароль." :
                sent ? "Введите пятизначный код из SMS. Он действует 3 минуты." :
                "Для аккаунтов, зарегистрированных раньше по SMS и ещё не установивших пароль."}</p>
            <Notice />
            <form onSubmit={submit}>
                <Errors errors={f.errors} />
                {register ? <>
                    <Field label="Ваше имя"><input required minLength={2} maxLength={100} autoComplete="given-name" value={f.data.name} onChange={e => f.setData("name", e.target.value)} placeholder="Как к вам обращаться" /></Field>
                    <Field label="Номер телефона"><PhoneInput required fixedPrefix showHint={false} value={f.data.phone} onChange={value => f.setData("phone", value)} /></Field>
                    <Field label="Электронная почта"><input required type="email" maxLength={255} autoComplete="email" placeholder="mail@example.com" value={f.data.email} onChange={e => f.setData("email", e.target.value)} /></Field>
                </> : !smsMode ? <>
                    <div className="auth-login-fields">
                        <Field label="Телефон или почта"><input required type="text" autoComplete="username" autoCapitalize="none" spellCheck={false} maxLength={255} value={f.data.identifier} onChange={e => f.setData("identifier", e.target.value)} /></Field>
                        <Field label="Пароль"><input required type="password" autoComplete="current-password" value={f.data.password} onChange={e => f.setData("password", e.target.value)} /></Field>
                    </div>
                    <p className="muted"><Link href="/forgot-password">Забыли пароль?</Link></p>
                    {(captchaRequired || !!(f.errors as Record<string, string | undefined>).captcha) && (turnstileSiteKey ? <Turnstile key={captchaRevision} siteKey={turnstileSiteKey} onToken={token => f.setData("cf-turnstile-response", token)} /> : <p className="muted" role="alert">Проверка Cloudflare недоступна: сайт не получил публичный ключ Turnstile. Обновите страницу; если ошибка повторится, необходимо проверить конфигурацию Laravel на сервере.</p>)}
                </> : sent ? <>
                    <Field label="Код подтверждения"><input autoFocus required inputMode="numeric" autoComplete="one-time-code" minLength={5} maxLength={5} pattern="[0-9]{5}" value={f.data.code} onChange={e => f.setData("code", e.target.value.replace(/\D/g, "").slice(0, 5))} /></Field>
                    <p className="muted">Код отправлен на {f.data.phone}.</p>
                </> : <Field label="Номер телефона"><PhoneInput required showHint={false} value={f.data.phone} onChange={value => f.setData("phone", value)} /></Field>}
                <button className="primary wide" disabled={f.processing}>{f.processing ? "Подождите…" : register ? "Получить пароль на почту" : !smsMode ? "Войти" : sent ? "Подтвердить" : "Получить SMS"}</button>
            </form>
            {sent && smsMode && <button type="button" className="secondary wide" disabled={f.processing} onClick={() => { setSent(false); f.clearErrors(); }}>Изменить номер / отправить повторно</button>}
            <p className="muted">{register ? "Уже есть аккаунт?" : "Ещё нет аккаунта?"} <Link href={register ? "/login" : "/register"}>{register ? "Войти" : "Начать работу"}</Link></p>
            {!register && <button type="button" className="ghost" disabled={f.processing} onClick={switchSms}>{smsMode ? "Вернуться ко входу по паролю" : "Ранее регистрировались по SMS?"}</button>}
        </section>
    </main>;
}
