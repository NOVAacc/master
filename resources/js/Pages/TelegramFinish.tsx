import { Head, Link, useForm } from "@inertiajs/react";
import { useI18n } from "../i18n";
import PhoneInput from "../PhoneInput";
import { Brand, Errors, Field } from "../Shared";

export default function TelegramFinish({ name }: { name: string }) {
    const { t } = useI18n();
    const form = useForm({ name, email: "", phone: "" });
    return <main className="auth-wrap auth-wrap--login">
        <section className="auth-form panel">
            <Head title={t("Завершение регистрации")} />
            <Brand />
            <h1>{t("Завершите регистрацию")}</h1>
            <p className="muted">{t("Telegram подтвердил ваш аккаунт. Укажите почту и телефон для кабинета BOSH. Почту потребуется подтвердить по ссылке.")}</p>
            <form onSubmit={e => { e.preventDefault(); form.post("/auth/telegram/finish"); }}>
                <Errors errors={form.errors} />
                <Field label={t("Ваше имя")}><input required minLength={2} maxLength={100} value={form.data.name} onChange={e => form.setData("name", e.target.value)} /></Field>
                <Field label={t("Электронная почта")}><input required type="email" maxLength={255} autoComplete="email" value={form.data.email} onChange={e => form.setData("email", e.target.value)} /></Field>
                <Field label={t("Номер телефона")}><PhoneInput required fixedPrefix showHint={false} value={form.data.phone} onChange={value => form.setData("phone", value)} /></Field>
                <button className="primary wide" disabled={form.processing}>{form.processing ? t("Подождите…") : t("Создать кабинет")}</button>
            </form>
            <p className="muted"><Link href="/login">{t("Вернуться ко входу")}</Link></p>
        </section>
    </main>;
}
