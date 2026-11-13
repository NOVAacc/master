import DeleteAccount from "./DeleteAccount";
import { useI18n } from "./i18n";
import { useForm, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Errors, Field } from "./Shared";
import PasswordHints from "./PasswordHints";

export type SecurityStatus = {
    email: string | null;
    emailVerified: boolean;
    passwordSet: boolean;
};

export default function AccountSecurity({ status }: { status: SecurityStatus }) {
    const { t: tr, locale } = useI18n();
    const { auth } = usePage<{ auth: { user: { id: number } } }>().props;
    const [dismissPassword, setDismissPassword] = useState(false);
    const emailForm = useForm({ email: status.email || "" });
    const passForm = useForm({ current_password: "", password: "", password_confirmation: "" });
    return (
        <><section className="panel" aria-label={tr("Безопасность аккаунта")}>
            <p className="eyebrow">{tr("БЕЗОПАСНОСТЬ АККАУНТА")}</p>
            <h2>{tr("Вход и восстановление")}</h2>
            <div className="account-id-card"><div><span>{tr("ID аккаунта")}</span><strong className="account-id-number">#{auth.user.id}</strong></div><p>{tr("Ваш постоянный внутренний номер. Не меняется при изменении имени, почты или адреса страницы.")}</p></div>
            {!status.emailVerified && (
                <div className="notice" role="status">
                    <strong>{status.email ? tr("Подтвердите вашу почту") : tr("Добавьте электронную почту")}</strong>
                    <p>{tr("Почта нужна для восстановления пароля. Пока она не подтверждена, сброс пароля недоступен. Это напоминание останется до подтверждения.")}</p>
                </div>
            )}
            {!status.passwordSet && !dismissPassword && (
                <div className="notice">
                    <strong>{tr("Настройте постоянный пароль")}</strong>
                    <p>{tr("При следующих входах SMS не понадобится. Минимум 6 символов: латиница, цифры или знаки без пробелов.")}</p>
                    <button type="button" className="ghost" onClick={() => setDismissPassword(true)}>{tr("Напомнить позже")}</button>
                </div>
            )}
            <div className="profile-settings-layout">
                <form onSubmit={e => {
                    e.preventDefault();
                    emailForm.post("/security/email", { preserveScroll: true });
                }}>
                    <h3>{tr("Электронная почта")}</h3>
                    <Errors errors={emailForm.errors} />
                    <Field label={tr("Адрес для восстановления")}>
                        <input required type="email" autoComplete="email" maxLength={255}
                            value={emailForm.data.email}
                            onChange={e => emailForm.setData("email", e.target.value)} />
                    </Field>
                    <p className="muted small">{status.emailVerified ? tr("Почта подтверждена.") : tr("После сохранения мы отправим письмо со ссылкой для подтверждения.")}</p>
                    <button className="primary" disabled={emailForm.processing}>{tr("Сохранить почту")}</button>
                    {!!status.email && !status.emailVerified && (
                        <button type="button" className="secondary" onClick={() => router.post("/security/email/resend")}>
                            {tr("Повторить письмо")}{" "}</button>
                    )}
                </form>
                <form onSubmit={e => {
                    e.preventDefault();
                    passForm.post("/security/password", { preserveScroll: true,
                        onSuccess: () => passForm.reset() });
                }}>
                    <h3>{status.passwordSet ? tr("Изменить пароль") : tr("Создать пароль для входа")}</h3>
                    <Errors errors={passForm.errors} />
                    {status.passwordSet && (
                        <Field label={tr("Текущий пароль")}>
                            <input type="password" autoComplete="current-password" required
                                value={passForm.data.current_password}
                                onChange={e => passForm.setData("current_password", e.target.value)} />
                        </Field>
                    )}
                    <Field label={tr("Новый пароль")}>
                        <input type="password" required minLength={6} maxLength={128}
                            autoComplete="new-password"
                            value={passForm.data.password}
                            onChange={e => passForm.setData("password", e.target.value)} />
                    </Field>
                    <Field label={tr("Повторите пароль")}>
                        <input type="password" required minLength={6} maxLength={128}
                            autoComplete="new-password"
                            value={passForm.data.password_confirmation}
                            onChange={e => passForm.setData("password_confirmation", e.target.value)} />
                    </Field>
                    <PasswordHints password={passForm.data.password} confirmation={passForm.data.password_confirmation} />
                    <p className="muted small">{tr("После 5 неверных попыток вход будет заблокирован на 1 час.")}</p>
                    <button className="primary" disabled={passForm.processing}>
                        {status.passwordSet ? tr("Изменить пароль") : tr("Установить пароль")}
                    </button>
                </form>
            </div>
        </section>
        <DeleteAccount accountId={auth.user.id} passwordSet={status.passwordSet} /></>
    );
}
