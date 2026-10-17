import { Head, Link, useForm } from "@inertiajs/react";
import { Brand, Errors, Field } from "../Shared";

export default function PasswordRecovery({ reset, token = "", email = "" }: {
    reset: boolean; token?: string; email?: string;
}) {
    const f = useForm({ email, token, password: "", password_confirmation: "" });
    return (
        <main className="auth-wrap">
            <section className="auth-intro">
                <Brand />
                <h1>Вернитесь к своему делу.</h1>
                <p>Восстановление пароля — только через подтверждённую почту.</p>
            </section>
            <section className="auth-form panel">
                <Head title={reset ? "Новый пароль" : "Восстановление пароля"} />
                <h1>{reset ? "Установить новый пароль" : "Забыли пароль?"}</h1>
                <p className="muted">{reset ?
                    "Введите новый пароль для входа в BOSH." :
                    "Укажите электронную почту, которую вы подтвердили в настройках аккаунта."}</p>
                <form onSubmit={e => {
                    e.preventDefault();
                    f.transform(d => reset
                        ? ({ email: d.email, token: d.token, password: d.password, password_confirmation: d.password_confirmation })
                        : ({ email: d.email }));
                    f.post(reset ? "/reset-password" : "/forgot-password");
                }}>
                    <Errors errors={f.errors} />
                    <Field label="Электронная почта">
                        <input type="email" required autoComplete="email"
                            value={f.data.email} onChange={e => f.setData("email", e.target.value)} />
                    </Field>
                    {reset && <>
                        <Field label="Новый пароль (от 5 символов)">
                            <input type="password" required minLength={5} maxLength={128}
                                autoComplete="new-password" value={f.data.password}
                                onChange={e => f.setData("password", e.target.value)} />
                        </Field>
                        <Field label="Подтвердите пароль">
                            <input type="password" required minLength={5} maxLength={128}
                                autoComplete="new-password" value={f.data.password_confirmation}
                                onChange={e => f.setData("password_confirmation", e.target.value)} />
                        </Field>
                    </>}
                    <button className="primary wide" disabled={f.processing}>
                        {f.processing ? "Подождите…" : reset ? "Сохранить новый пароль" : "Получить ссылку на почту"}
                    </button>
                </form>
                {f.recentlySuccessful && !reset && (
                    <p role="status" className="notice">Если указана подтверждённая почта, ссылка для восстановления отправлена.</p>
                )}
                <p className="muted"><Link href="/login">Вернуться ко входу</Link></p>
            </section>
        </main>
    );
}
