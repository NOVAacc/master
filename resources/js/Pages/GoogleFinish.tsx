import { Head, Link, useForm } from "@inertiajs/react";
import { useI18n } from "../i18n";
import PhoneInput from "../PhoneInput";
import { Brand, Errors, Field } from "../Shared";

export default function GoogleFinish({ email, name }: { email: string; name: string }) {
    const { t } = useI18n();
    const form = useForm({ phone: "" });
    return <main className="auth-wrap auth-wrap--login">
        <section className="auth-form panel">
            <Head title={t("Завершение регистрации")} />
            <Brand />
            <h1>{t("Завершите регистрацию")}</h1>
            <p className="muted">{t("Google подтвердил ваш email. Добавьте номер телефона для записи клиентов.")}</p>
            <p><strong>{name}</strong><br />{email}</p>
            <form onSubmit={e => { e.preventDefault(); form.post("/auth/google/finish"); }}>
                <Errors errors={form.errors} />
                <Field label={t("Номер телефона")}>
                    <PhoneInput required fixedPrefix showHint={false} value={form.data.phone} onChange={value => form.setData("phone", value)} />
                </Field>
                <button className="primary wide" disabled={form.processing}>
                    {form.processing ? t("Подождите…") : t("Создать кабинет")}
                </button>
            </form>
            <p className="muted"><Link href="/login">{t("Вернуться ко входу")}</Link></p>
        </section>
    </main>;
}
