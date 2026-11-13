import LanguageSwitch from "../i18n";
import { useI18n } from "../i18n";
import { Head, Link, useForm } from "@inertiajs/react";
import { Brand, Errors, Field } from "../Shared";
import PasswordHints from "../PasswordHints";

export default function PasswordRecovery({ reset, token = "", email = "" }: {
    reset: boolean; token?: string; email?: string;
}) {
    const { t } = useI18n();
    const f = useForm({ email, token, password: "", password_confirmation: "" });
    return (
        <main className="auth-wrap">
            <section className="auth-intro">
                <Brand />
                <h1>{t("Вернитесь к своему делу.")}</h1>
                <p>{t("Восстановление пароля — только через подтверждённую почту.")}</p>
            </section>
            <section className="auth-form panel">
            <div className="auth-language-header"><Link href="/" aria-label={t("BOSH — главная страница")}>BOSH</Link><LanguageSwitch /></div>
                <Head title={reset ? t("Новый пароль") : t("Восстановление пароля")} />
                <h1>{reset ? t("Установить новый пароль") : t("Забыли пароль?")}</h1>
                <p className="muted">{reset ?
                    t("Введите новый пароль для входа в BOSH.") :
                    t("Укажите электронную почту, которую вы подтвердили в настройках аккаунта.")}</p>
                <form onSubmit={e => {
                    e.preventDefault();
                    f.transform(d => reset
                        ? ({ email: d.email, token: d.token, password: d.password, password_confirmation: d.password_confirmation })
                        : ({ email: d.email }));
                    f.post(reset ? "/reset-password" : "/forgot-password");
                }}>
                    <Errors errors={f.errors} />
                    <Field label={t("Электронная почта")}>
                        <input type="email" required autoComplete="email"
                            value={f.data.email} onChange={e => f.setData("email", e.target.value)} />
                    </Field>
                    {reset && <>
                        <Field label={t("Новый пароль")}>
                            <input type="password" required minLength={6} maxLength={128}
                                autoComplete="new-password" value={f.data.password}
                                onChange={e => f.setData("password", e.target.value)} />
                        </Field>
                        <Field label={t("Подтвердите пароль")}>
                            <input type="password" required minLength={6} maxLength={128}
                                autoComplete="new-password" value={f.data.password_confirmation}
                                onChange={e => f.setData("password_confirmation", e.target.value)} />
                        </Field>
                        <PasswordHints password={f.data.password} confirmation={f.data.password_confirmation} />
                    </>}
                    <button className="primary wide" disabled={f.processing}>
                        {f.processing ? t("Подождите…") : reset ? t("Сохранить новый пароль") : t("Получить ссылку на почту")}
                    </button>
                </form>
                {f.recentlySuccessful && !reset && (
                    <p role="status" className="notice">{t("Если указана подтверждённая почта, ссылка для восстановления отправлена.")}</p>
                )}
                <p className="muted"><Link href="/login">{t("Вернуться ко входу")}</Link></p>
            </section>
        </main>
    );
}
