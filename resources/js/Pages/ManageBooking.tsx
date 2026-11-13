import { localizedDateTimeFormat } from "../localized-date";
import LanguageSwitch from "../i18n";
import { useI18n } from "../i18n";
import { timezoneLabel } from "../timezone";
import { Head, router, useForm } from "@inertiajs/react";
import { FormEvent } from "react";
import { Brand, Errors, Notice } from "../Shared";

type Appointment = {
    id: number; status: string; starts_at: string; ends_at: string;
    service_name_snapshot: string; price_som: number; customer_reschedule_count: number;
};
type Master = { slug: string; display_name: string; timezone: string };

export default function ManageBooking({
    appointment, master, canChange, actionUrl,
}: { appointment: Appointment; master: Master; canChange: boolean; actionUrl: string }) {
    const { t: tr, locale } = useI18n();
    const form = useForm({ action: "reschedule", starts_at_local: "" });
    const date = localizedDateTimeFormat(locale, {
        dateStyle: "medium", timeStyle: "short", timeZone: master.timezone,
    }).format(new Date(appointment.starts_at));
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform(data => ({ ...data, action: "reschedule" }));
        form.post(actionUrl, { preserveScroll: true });
    }
    function cancel() {
        if (!window.confirm(tr("Отменить запись? Это действие нельзя отменить."))) return;
        router.post(actionUrl, { action: "cancel" }, { preserveScroll: true });
    }
    return (
        <main className="booking-wrap">
            <Head title={tr("Управление записью")}><meta name="referrer" content="no-referrer" /></Head>
            <div className="public-page-tools"><Brand /><LanguageSwitch /></div>
            <section className="panel">
                <p className="eyebrow">{tr("BOSH · ВАША ЗАПИСЬ")}</p>
                <h1>{tr("Управление записью")}</h1>
                <Notice />
                <h2>{master.display_name}</h2>
                <p>{appointment.service_name_snapshot}</p>
                <p><strong>{date}</strong> · {appointment.price_som.toLocaleString("ru-RU")} {tr("сом")}</p>
                <p className="muted">{tr("Статус:")}{" "}{{
                    pending: tr("ожидает подтверждения"),
                    confirmed: tr("подтверждена"),
                    cancelled: tr("отменена"),
                    completed: tr("завершена"),
                    no_show: tr("не пришли"),
                }[appointment.status] ?? appointment.status}</p>
                {canChange ? (
                    <>
                        <p className="muted">{tr("Изменения доступны не позднее чем за 2 часа до визита. Переносов осталось:")}{" "}{Math.max(0, 2 - appointment.customer_reschedule_count)}.</p>
                        {appointment.customer_reschedule_count < 2 && (
                            <form onSubmit={submit}>
                                <h3>{tr("Перенести запись")}</h3>
                                <label htmlFor="appointment-new-time">{tr("Новое время (часовой пояс")}{" "}{timezoneLabel(master.timezone)})</label>
                                <input id="appointment-new-time" type="datetime-local" required step={1800}
                                    value={form.data.starts_at_local}
                                    onChange={event => form.setData("starts_at_local", event.target.value)} />
                                <Errors errors={form.errors} />
                                <button type="submit" className="primary" disabled={form.processing || !form.data.starts_at_local}>
                                    {tr("Подтвердить перенос")}{" "}</button>
                            </form>
                        )}
                        <button type="button" className="ghost" onClick={cancel}>{tr("Отменить запись")}</button>
                    </>
                ) : <p className="muted">{tr("Самостоятельное изменение недоступно. При необходимости свяжитесь с мастером.")}</p>}
                <p><a href={`/m/${master.slug}`}>{tr("Вернуться на страницу мастера →")}</a></p>
            </section>
        </main>
    );
}
