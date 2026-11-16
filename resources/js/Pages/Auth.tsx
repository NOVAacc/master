import LanguageSwitch from "../i18n";
import { useI18n } from "../i18n";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { Brand, Errors, Field, Notice } from "../Shared";
import PhoneInput from "../PhoneInput";
import PasswordHints from "../PasswordHints";

function Turnstile({ siteKey, onToken }: { siteKey: string; onToken: (token: string) => void }) {
    const { t } = useI18n();
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
        {loadError && <p className="muted" role="alert">{t("Не удалось загрузить проверку Cloudflare. Отключите блокировщик содержимого для этого сайта и обновите страницу.")}</p>}
    </div>;
}

export default function Auth({ register, turnstileSiteKey }: { register: boolean; turnstileSiteKey?: string | null }) {
    const { t } = useI18n();
    const [captchaRequired, setCaptchaRequired] = useState(false);
    const [captchaRevision, setCaptchaRevision] = useState(0);
    const f = useForm({ name: "", phone: "", email: "", identifier: "", password: "", password_confirmation: "", "cf-turnstile-response": "" });
    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        if (register) {
            f.transform(d => ({ name: d.name, phone: d.phone, email: d.email, password: d.password, password_confirmation: d.password_confirmation }));
            f.post("/register", { onFinish: () => { f.setData("password", ""); f.setData("password_confirmation", ""); } });
        } else {
            f.transform(d => ({ identifier: d.identifier, password: d.password, "cf-turnstile-response": d["cf-turnstile-response"] }));
            f.post("/login", { onError: errors => { if (errors.captcha) setCaptchaRequired(true); setCaptchaRevision(value => value + 1); f.setData("cf-turnstile-response", ""); }, onFinish: () => f.setData("password", "") });
        }
    }
    return <main className={register ? "auth-wrap" : "auth-wrap auth-wrap--login"}>
        {register && <section className="auth-intro">
            <Brand />
            <h1>{t("Время для вашего дела.")}</h1>
            <p>{t("Услуги, расписание и запись клиентов — в одном кабинете.")}</p>
            <div className="intro-card">
                <span>{t("Начните с простого")}</span>
                <h2>{t("Ваше дело.")}<br />{t("Ваше расписание.")}</h2>
                <p>{t("Создайте аккаунт, заполните профиль и принимайте записи. BOSH — бесплатно.")}</p>
            </div>
        </section>}
        <section className="auth-form panel">
            <div className="auth-language-header"><Link href="/" aria-label={t("BOSH — главная страница")}>BOSH</Link><LanguageSwitch /></div>
            <Head title={register ? t("Регистрация") : t("Вход")} />
            {register ? <>
                <p className="eyebrow">{t("BOSH · КАБИНЕТ МАСТЕРА")}</p>
                <h1>{t("Начать работу")}</h1>
                <p className="muted">{t("Укажите контакты и придумайте пароль. Для активации профиля подтвердите почту по ссылке из письма.")}</p>
            </> : <>
                <div className="auth-login-brand">BOSH</div>
                <h1>{t("Вход")}</h1>
            </>}
            <Notice />
            <a className="google-signin" href="/auth/google" style={{display:"flex",justifyContent:"center",alignItems:"center",gap:10,border:"1px solid #d4d4d8",borderRadius:12,padding:"13px 16px",textDecoration:"none",color:"inherit",fontWeight:600,marginBottom:18}}>
                <svg width="19" height="19" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 5.38 6.51 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.28 5.48-4.82 7.18l7.73 6C44.36 38.03 46.98 31.87 46.98 24.55z"/><path fill="#FBBC05" d="M10.53 28.59A14.41 14.41 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.2A23.89 23.89 0 0 0 0 24c0 3.87.93 7.52 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6C30.03 37.64 27.29 38.5 24 38.5c-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                {t("Продолжить с Google")}
            </a>
            <div className="muted" style={{textAlign:"center",marginBottom:18}}>{t("или по электронной почте")}</div>
            <form onSubmit={submit}>
                <Errors errors={f.errors} />
                {register ? <>
                    <Field label={t("Ваше имя")}><input required minLength={2} maxLength={100} autoComplete="given-name" value={f.data.name} onChange={e => f.setData("name", e.target.value)} placeholder={t("Как к вам обращаться")} /></Field>
                    <Field label={t("Номер телефона")}><PhoneInput required fixedPrefix showHint={false} value={f.data.phone} onChange={value => f.setData("phone", value)} /></Field>
                    <Field label={t("Электронная почта")}><input required type="email" maxLength={255} autoComplete="email" placeholder="mail@example.com" value={f.data.email} onChange={e => f.setData("email", e.target.value)} /></Field>
                    <Field label={t("Пароль")}><input required type="password" minLength={6} maxLength={128} autoComplete="new-password" pattern="[!-~]{6,128}" title={t("Минимум 6 символов: латинские буквы, цифры или знаки без пробелов")} value={f.data.password} onChange={e => f.setData("password", e.target.value)} /></Field>
                    <Field label={t("Повторите пароль")}><input required type="password" minLength={6} maxLength={128} autoComplete="new-password" value={f.data.password_confirmation} onChange={e => f.setData("password_confirmation", e.target.value)} /></Field>
                    <PasswordHints password={f.data.password} confirmation={f.data.password_confirmation} />
                </> : <>
                    <div className="auth-login-fields">
                        <Field label={t("Электронная почта")}><input required type="email" autoComplete="username" autoCapitalize="none" spellCheck={false} maxLength={255} placeholder="mail@example.com" value={f.data.identifier} onChange={e => f.setData("identifier", e.target.value)} /></Field>
                        <Field label={t("Пароль")}><input required type="password" autoComplete="current-password" value={f.data.password} onChange={e => f.setData("password", e.target.value)} /></Field>
                    </div>
                    <div className="auth-login-recovery"><Link href="/forgot-password">{t("Забыли пароль?")}</Link></div>
                    <button type="button" className="text-button" disabled={f.processing || !f.data.identifier.trim()} onClick={() => router.post("/email/verification/resend", { email: f.data.identifier.trim() }, { preserveScroll: true })}>{t("Отправить ссылку подтверждения повторно")}</button>
                    {(captchaRequired || !!(f.errors as Record<string, string | undefined>).captcha) && (turnstileSiteKey ? <Turnstile key={captchaRevision} siteKey={turnstileSiteKey} onToken={token => f.setData("cf-turnstile-response", token)} /> : <p className="muted" role="alert">{t("Проверка Cloudflare недоступна: сайт не получил публичный ключ Turnstile. Обновите страницу; если ошибка повторится, необходимо проверить конфигурацию Laravel на сервере.")}</p>)}
                </>}
                <button className="primary wide" disabled={f.processing}>{f.processing ? t("Подождите…") : register ? t("Создать аккаунт") : t("Войти")}</button>
            </form>
            {register
                ? <p className="muted">{t("Уже есть аккаунт?")}{" "}<Link href="/login">{t("Войти")}</Link></p>
                : <div className="auth-login-register">{t("Нет аккаунта?")}{" "}<Link href="/register">{t("Зарегистрироваться")}</Link></div>}
        </section>
    </main>;
}
