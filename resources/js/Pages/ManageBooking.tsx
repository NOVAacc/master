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
    const form = useForm({ action: "reschedule", starts_at_local: "" });
    const date = new Intl.DateTimeFormat("ru-RU", {
        dateStyle: "medium", timeStyle: "short", timeZone: master.timezone,
    }).format(new Date(appointment.starts_at));
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform(data => ({ ...data, action: "reschedule" }));
        form.post(actionUrl, { preserveScroll: true });
    }
    function cancel() {
        if (!window.confirm("Отменить запись? Это действие нельзя отменить.")) return;
        router.post(actionUrl, { action: "cancel" }, { preserveScroll: true });
    }
    return (
        <main className="booking-wrap">
            <Head title="Управление записью"><meta name="referrer" content="no-referrer" /></Head>
            <Brand />
            <section className="panel">
                <p className="eyebrow">BOSH · ВАША ЗАПИСЬ</p>
                <h1>Управление записью</h1>
                <Notice />
                <h2>{master.display_name}</h2>
                <p>{appointment.service_name_snapshot}</p>
                <p><strong>{date}</strong> · {appointment.price_som.toLocaleString("ru-RU")} сом</p>
                <p className="muted">Статус: {{
                    pending: "ожидает подтверждения",
                    confirmed: "подтверждена",
                    cancelled: "отменена",
                    completed: "завершена",
                    no_show: "не пришли",
                }[appointment.status] ?? appointment.status}</p>
                {canChange ? (
                    <>
                        <p className="muted">Изменения доступны не позднее чем за 2 часа до визита. Переносов осталось: {Math.max(0, 2 - appointment.customer_reschedule_count)}.</p>
                        {appointment.customer_reschedule_count < 2 && (
                            <form onSubmit={submit}>
                                <h3>Перенести запись</h3>
                                <label htmlFor="appointment-new-time">Новое время (часовой пояс {master.timezone})</label>
                                <input id="appointment-new-time" type="datetime-local" required step={1800}
                                    value={form.data.starts_at_local}
                                    onChange={event => form.setData("starts_at_local", event.target.value)} />
                                <Errors errors={form.errors} />
                                <button type="submit" className="primary" disabled={form.processing || !form.data.starts_at_local}>
                                    Подтвердить перенос
                                </button>
                            </form>
                        )}
                        <button type="button" className="ghost" onClick={cancel}>Отменить запись</button>
                    </>
                ) : <p className="muted">Самостоятельное изменение недоступно. При необходимости свяжитесь с мастером.</p>}
                <p><a href={`/m/${master.slug}`}>Вернуться на страницу мастера →</a></p>
            </section>
        </main>
    );
}
