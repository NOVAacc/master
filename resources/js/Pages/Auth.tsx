import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { Brand, Errors, Field } from "../Shared";

export default function Auth({ register }: { register: boolean }) {
    const [smsMode, setSmsMode] = useState(register);
    const [sent, setSent] = useState(false);
    const f = useForm({
        name: "", phone: "+996 ", code: "",
        mode: register ? "register" : "login",
        identifier: "", password: "",
    });
    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        if (!smsMode) {
            f.transform(d => ({ identifier: d.identifier, password: d.password }));
            f.post("/login");
        } else if (sent) {
            f.transform(d => ({ code: d.code }));
            f.post("/auth/verify-code");
        } else {
            f.transform(d => ({ name: d.name, phone: d.phone, mode: d.mode }));
            f.post("/auth/send-code", { onSuccess: () => setSent(true) });
        }
    }
    return (
        <main className="auth-wrap">
            <section className="auth-intro">
                <Brand />
                <h1>Время для вашего дела.</h1>
                <p>Услуги, расписание и запись клиентов — в одном кабинете.</p>
                <div className="intro-card">
                    <span>Всё по плану</span>
                    <h2>Ваш следующий клиент<br />уже выбирает время.</h2>
                    <p>Начните принимать записи за пару минут.</p>
                </div>
            </section>
            <section className="auth-form panel">
                <Head title={register ? "Регистрация" : "Вход"} />
                <p className="eyebrow">BOSH · КАБИНЕТ МАСТЕРА</p>
                <h1>{register ? "Создать аккаунт" : "Войти в BOSH"}</h1>
                <p className="muted">
                    {!smsMode ? "Введите номер телефона и постоянный пароль." :
                     sent ? "Введите пятизначный код из SMS. Он действует 3 минуты." :
                     "Подтвердите телефон через SMS один раз, а затем установите постоянный пароль."}
                </p>
                <form onSubmit={submit}>
                    <Errors errors={f.errors} />
                    {!smsMode ? <>
                        <Field label="Номер телефона или почта">
                            <input required autoComplete="username" value={f.data.identifier}
                                onChange={e => f.setData("identifier", e.target.value)}
                                placeholder="+996 555 123 456" />
                        </Field>
                        <Field label="Пароль">
                            <input required type="password" autoComplete="current-password"
                                value={f.data.password}
                                onChange={e => f.setData("password", e.target.value)} />
                        </Field>
                        <p className="muted"><Link href="/forgot-password">Забыли пароль?</Link></p>
                    </> : sent ? <>
                        <Field label="Код подтверждения">
                            <input autoFocus required inputMode="numeric" autoComplete="one-time-code"
                                minLength={5} maxLength={5} pattern="[0-9]{5}"
                                value={f.data.code}
                                onChange={e => f.setData("code", e.target.value.replace(/\D/g, "").slice(0, 5))} />
                        </Field>
                        <p className="muted">Код отправлен на {f.data.phone}.</p>
                    </> : <>
                        {register && <Field label="Ваше имя">
                            <input required maxLength={100} autoComplete="name" value={f.data.name}
                                onChange={e => f.setData("name", e.target.value)} />
                        </Field>}
                        <Field label="Номер телефона">
                            <input required type="tel" autoComplete="tel"
                                value={f.data.phone} onChange={e => f.setData("phone", e.target.value)} />
                        </Field>
                    </>}
                    <button className="primary wide" disabled={f.processing}>
                        {f.processing ? "Подождите…" : !smsMode ? "Войти" : sent ? "Подтвердить" : "Получить SMS"}
                    </button>
                </form>
                {sent && smsMode && <button type="button" className="secondary wide"
                    onClick={() => { setSent(false); f.clearErrors(); }}>Изменить номер / отправить повторно</button>}
                {!register && <button type="button" className="secondary wide"
                    onClick={() => { setSmsMode(!smsMode); setSent(false); f.clearErrors(); }}>
                    {smsMode ? "Войти по паролю" : "Первый вход после SMS-регистрации"}
                </button>}
                <p className="muted">
                    {register ? "Уже зарегистрированы?" : "Первый раз здесь?"}{" "}
                    <Link href={register ? "/login" : "/register"}>{register ? "Войти" : "Создать аккаунт"}</Link>
                </p>
            </section>
        </main>
    );
}
