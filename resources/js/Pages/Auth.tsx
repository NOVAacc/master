import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { Brand, Errors, Field } from "../Shared";
import PhoneInput from "../PhoneInput";

export default function Auth({ register }: { register: boolean }) {
    const [smsMode, setSmsMode] = useState(register);
    const [sent, setSent] = useState(false);
    const [emailLogin, setEmailLogin] = useState(false);
    const f = useForm({
        name: "", phone: "", code: "",
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
                    {!smsMode ? (emailLogin ? "Введите почту и постоянный пароль." : "Введите номер телефона и постоянный пароль.") :
                     sent ? "Введите пятизначный код из SMS. Он действует 3 минуты." :
                     "Подтвердите телефон через SMS один раз, а затем установите постоянный пароль."}
                </p>
                <form onSubmit={submit}>
                    <Errors errors={f.errors} />
                    {!smsMode ? <>
                        <div className="display-switch text-switch auth-login-switch" role="group" aria-label="Способ входа">
                            {[{ email: false, label: "Телефон" }, { email: true, label: "Почта" }].map(option => (
                                <button key={option.label} type="button" aria-pressed={emailLogin === option.email}
                                    disabled={f.processing} onClick={() => {
                                        if (emailLogin !== option.email) {
                                            setEmailLogin(option.email);
                                            f.setData("identifier", "");
                                            f.clearErrors();
                                        }
                                    }}>{option.label}</button>
                            ))}
                        </div>
                        <div className="auth-login-fields">
                            <Field label={emailLogin ? "Электронная почта" : "Номер телефона"}>
                                {emailLogin ? (
                                    <input key="email" required type="email" autoComplete="username" value={f.data.identifier}
                                        onChange={e => f.setData("identifier", e.target.value)} placeholder="mail@example.com" />
                                ) : (
                                    <PhoneInput required showHint={false} autoComplete="username" value={f.data.identifier}
                                        onChange={value => f.setData("identifier", value)} label="Номер телефона для входа" />
                                )}
                            </Field>
                            <Field label="Пароль">
                                <input required type="password" autoComplete="current-password"
                                    value={f.data.password}
                                    onChange={e => f.setData("password", e.target.value)} />
                            </Field>
                        </div>
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
                            <PhoneInput required showHint={false} fixedPrefix={register} value={f.data.phone}
                                onChange={value => f.setData("phone", value)} />
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
