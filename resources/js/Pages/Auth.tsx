import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { Brand, Errors, Field, Notice } from "../Shared";
import PhoneInput from "../PhoneInput";

export default function Auth({ register }: { register: boolean }) {
    const [smsMode, setSmsMode] = useState(false);
    const [sent, setSent] = useState(false);
    const f = useForm({ phone: "", email: "", code: "", identifier: "", password: "" });
    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        if (register) {
            f.transform(d => ({ phone: d.phone, email: d.email }));
            f.post("/register");
        } else if (!smsMode) {
            f.transform(d => ({ identifier: d.identifier, password: d.password }));
            f.post("/login", { onFinish: () => f.setData("password", "") });
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
            <p className="muted">{register ? "Укажите телефон и почту. Пароль для входа отправим письмом." :
                !smsMode ? "Введите телефон или почту и ваш пароль." :
                sent ? "Введите пятизначный код из SMS. Он действует 3 минуты." :
                "Для аккаунтов, зарегистрированных раньше по SMS и ещё не установивших пароль."}</p>
            <Notice />
            <form onSubmit={submit}>
                <Errors errors={f.errors} />
                {register ? <>
                    <Field label="Номер телефона"><PhoneInput required fixedPrefix showHint={false} value={f.data.phone} onChange={value => f.setData("phone", value)} /></Field>
                    <Field label="Электронная почта"><input required type="email" maxLength={255} autoComplete="email" placeholder="mail@example.com" value={f.data.email} onChange={e => f.setData("email", e.target.value)} /></Field>
                </> : !smsMode ? <>
                    <div className="auth-login-fields">
                        <Field label="Телефон или почта"><input required type="text" autoComplete="username" autoCapitalize="none" spellCheck={false} maxLength={255} value={f.data.identifier} onChange={e => f.setData("identifier", e.target.value)} /></Field>
                        <Field label="Пароль"><input required type="password" autoComplete="current-password" value={f.data.password} onChange={e => f.setData("password", e.target.value)} /></Field>
                    </div>
                    <p className="muted"><Link href="/forgot-password">Забыли пароль?</Link></p>
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
