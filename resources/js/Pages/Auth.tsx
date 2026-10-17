import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Brand, Errors, Field } from "../Shared";

export default function Auth({ register }: { register: boolean }) {
    const [legacy, setLegacy] = useState(false);
    const [sent, setSent] = useState(false);
    const { flash } = usePage<{ flash?: { otp_sent?: boolean } }>().props;
    const f = useForm({ name: "", phone: "+996 ", code: "", mode: register ? "register" : "login", email: "", password: "" });
    const codeSent = sent || !!flash?.otp_sent;
    return (
        <main className="auth-wrap">
            <section className="auth-intro">
                <Brand />
                <h1>Время для вашего дела.</h1>
                <p>Услуги, расписание и запись клиентов — в одном кабинете.</p>
                <div className="intro-card">
                    <span>Всё по плану</span>
                    <h2>Ваш следующий клиент<br/>уже выбирает время.</h2>
                    <p>Начните принимать записи за пару минут.</p>
                </div>
            </section>
            <section className="auth-form panel">
                <Head title={register ? "Регистрация" : "Вход"} />
                <p className="eyebrow">BOSH · КАБИНЕТ МАСТЕРА</p>
                <h1>{register ? "Создать аккаунт" : "Войти в BOSH"}</h1>
                <p className="muted">{codeSent && !legacy ? "Введите код из SMS." : register ? "Только имя и номер телефона." : "Введите номер телефона, чтобы получить код."}</p>
                <form onSubmit={e => {
                    e.preventDefault();
                    if (legacy) { f.post("/login"); return; }
                    if (codeSent) { f.post("/auth/verify-code"); return; }
                    f.transform(data => ({ name: data.name, phone: data.phone, mode: register ? "register" : "login" }))
                      .post("/auth/send-code", { onSuccess: () => setSent(true) });
                }}>
                    <Errors errors={f.errors} />
                    {legacy ? <>
                        <Field label="Email"><input type="email" required value={f.data.email} onChange={e => f.setData("email", e.target.value)} autoComplete="username" /></Field>
                        <Field label="Пароль"><input type="password" required value={f.data.password} onChange={e => f.setData("password", e.target.value)} autoComplete="current-password" /></Field>
                    </> : codeSent ? <>
                        <Field label="Код подтверждения"><input autoComplete="one-time-code" inputMode="numeric" autoFocus required minLength={4} maxLength={10} placeholder="Код из SMS" value={f.data.code} onChange={e => f.setData("code", e.target.value.replace(/[^a-zA-Z0-9]/g, ""))} /></Field>
                        <p className="muted">SMS отправлено на {f.data.phone}.</p>
                    </> : <>
                        {register && <Field label="Ваше имя"><input autoComplete="name" required maxLength={100} value={f.data.name} onChange={e => f.setData("name", e.target.value)} placeholder="Как к вам обращаться" /></Field>}
                        <Field label="Номер телефона"><input autoComplete="tel" type="tel" inputMode="tel" required value={f.data.phone} onChange={e => f.setData("phone", e.target.value)} placeholder="+996 555 123 456" /></Field>
                    </>}
                    <button className="primary wide" disabled={f.processing}>{f.processing ? "Подождите…" : legacy ? "Войти" : codeSent ? "Подтвердить" : "Получить SMS-код"}</button>
                </form>
                {codeSent && !legacy && <button type="button" className="secondary wide" onClick={() => { setSent(false); f.clearErrors(); }}>Изменить номер / отправить код повторно</button>}
                {!register && <button type="button" className="secondary wide" onClick={() => { setLegacy(!legacy); setSent(false); f.clearErrors(); }}>{legacy ? "Войти по SMS" : "Войти по email и паролю"}</button>}
                <p className="muted">{register ? "Уже зарегистрированы?" : "Первый раз здесь?"}{" "}<Link href={register ? "/login" : "/register"}>{register ? "Войти" : "Создать аккаунт"}</Link></p>
                {register && <small className="muted">Продолжая, вы подтверждаете согласие с условиями сервиса и обработкой номера для входа.</small>}
            </section>
        </main>
    );
}
