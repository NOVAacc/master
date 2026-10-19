import { Head, Link, useForm } from "@inertiajs/react";
import { FormEvent } from "react";
import { Brand, Errors } from "../Shared";

type Client = { id: number; name: string; phone_digits: string; notes: string | null };
type Visit = { id: number; starts_at: string; ends_at: string; status: string; price_som: number; service_name_snapshot: string };
const statusLabels: Record<string, string> = {
    pending: "Ожидает",
    confirmed: "Подтверждена",
    cancelled: "Отменена",
    completed: "Завершена",
    no_show: "Не пришёл",
};

export default function ClientDetails({
    client, timezone, visits, visitsShown,
}: { client: Client; timezone: string; visits: Visit[]; visitsShown: number }) {
    const form = useForm({ notes: client.notes ?? "" });
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put(`/clients/${client.id}`, { preserveScroll: true });
    }
    function formatDate(value: string) {
        return new Intl.DateTimeFormat("ru", {
            timeZone: timezone, dateStyle: "medium", timeStyle: "short",
        }).format(new Date(value));
    }
    return (
        <div className="shell">
            <Head title={`Клиент · ${client.name}`} />
            <aside className="sidebar">
                <Brand />
                <nav><Link href="/dashboard" className="text-button">← Назад в кабинет</Link></nav>
            </aside>
            <main className="main">
                <div className="panel">
                    <p className="eyebrow">КАРТОЧКА КЛИЕНТА</p>
                    <h1>{client.name}</h1>
                    <p><a href={`tel:+${client.phone_digits}`}>+{client.phone_digits}</a></p>
                    <form onSubmit={submit}>
                        <label htmlFor="client-notes">Личные заметки мастера</label>
                        <textarea id="client-notes" maxLength={2000} rows={4}
                            value={form.data.notes}
                            onChange={event => form.setData("notes", event.target.value)}
                            placeholder="Предпочтения клиента или важная информация" />
                        <Errors errors={form.errors} />
                        <button className="primary" disabled={form.processing} type="submit">
                            {form.processing ? "Сохранение…" : "Сохранить заметку"}
                        </button>
                        {form.recentlySuccessful && <p role="status">Сохранено</p>}
                    </form>
                </div>
                <div className="panel">
                    <h2>История посещений</h2>
                    <p className="muted">Показаны последние {visitsShown} записей этого клиента.</p>
                    {visits.length === 0 ? <p className="muted">История пока пуста.</p> : visits.map(visit => (
                        <article key={visit.id} className="appointment">
                            <div>
                                <strong>{formatDate(visit.starts_at)}</strong>
                                <p>{visit.service_name_snapshot} · {visit.price_som.toLocaleString("ru-RU")} сом</p>
                                <p className="muted">{statusLabels[visit.status] ?? visit.status}</p>
                            </div>
                        </article>
                    ))}
                </div>
            </main>
        </div>
    );
}
