import { useForm, router } from "@inertiajs/react";
import { useState } from "react";
import { Errors, Field } from "./Shared";

export type SecurityStatus = {
    email: string | null;
    emailVerified: boolean;
    passwordSet: boolean;
};

export default function AccountSecurity({ status }: { status: SecurityStatus }) {
    const [dismissPassword, setDismissPassword] = useState(false);
    const emailForm = useForm({ email: status.email || "" });
    const passForm = useForm({ current_password: "", password: "", password_confirmation: "" });
    return (
        <section className="panel" aria-label="Безопасность аккаунта">
            <p className="eyebrow">БЕЗОПАСНОСТЬ АККАУНТА</p>
            <h2>Вход и восстановление</h2>
            {!status.emailVerified && (
                <div className="notice" role="status">
                    <strong>{status.email ? "Подтвердите вашу почту" : "Добавьте электронную почту"}</strong>
                    <p>Почта нужна для восстановления пароля. Пока она не подтверждена, сброс пароля недоступен. Это напоминание останется до подтверждения.</p>
                </div>
            )}
            {!status.passwordSet && !dismissPassword && (
                <div className="notice">
                    <strong>Настройте постоянный пароль</strong>
                    <p>При следующих входах SMS не понадобится. Пароль может состоять из 5 символов, но более длинный безопаснее.</p>
                    <button type="button" className="ghost" onClick={() => setDismissPassword(true)}>Напомнить позже</button>
                </div>
            )}
            <div className="profile-settings-layout">
                <form onSubmit={e => {
                    e.preventDefault();
                    emailForm.post("/security/email", { preserveScroll: true });
                }}>
                    <h3>Электронная почта</h3>
                    <Errors errors={emailForm.errors} />
                    <Field label="Адрес для восстановления">
                        <input required type="email" autoComplete="email" maxLength={255}
                            value={emailForm.data.email}
                            onChange={e => emailForm.setData("email", e.target.value)} />
                    </Field>
                    <p className="muted small">{status.emailVerified ? "Почта подтверждена." : "После сохранения мы отправим письмо со ссылкой для подтверждения."}</p>
                    <button className="primary" disabled={emailForm.processing}>Сохранить почту</button>
                    {!!status.email && !status.emailVerified && (
                        <button type="button" className="secondary" onClick={() => router.post("/security/email/resend")}>
                            Повторить письмо
                        </button>
                    )}
                </form>
                <form onSubmit={e => {
                    e.preventDefault();
                    passForm.post("/security/password", { preserveScroll: true,
                        onSuccess: () => passForm.reset() });
                }}>
                    <h3>{status.passwordSet ? "Изменить пароль" : "Создать пароль для входа"}</h3>
                    <Errors errors={passForm.errors} />
                    {status.passwordSet && (
                        <Field label="Текущий пароль">
                            <input type="password" autoComplete="current-password" required
                                value={passForm.data.current_password}
                                onChange={e => passForm.setData("current_password", e.target.value)} />
                        </Field>
                    )}
                    <Field label="Новый пароль (от 5 символов)">
                        <input type="password" required minLength={5} maxLength={128}
                            autoComplete="new-password"
                            value={passForm.data.password}
                            onChange={e => passForm.setData("password", e.target.value)} />
                    </Field>
                    <Field label="Повторите пароль">
                        <input type="password" required minLength={5} maxLength={128}
                            autoComplete="new-password"
                            value={passForm.data.password_confirmation}
                            onChange={e => passForm.setData("password_confirmation", e.target.value)} />
                    </Field>
                    <p className="muted small">После 5 неверных попыток вход будет заблокирован на 1 час.</p>
                    <button className="primary" disabled={passForm.processing}>
                        {status.passwordSet ? "Изменить пароль" : "Установить пароль"}
                    </button>
                </form>
            </div>
        </section>
    );
}
