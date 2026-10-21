import axios from "axios";
import { useEffect, useRef, useState } from "react";
import type { Service } from "./types";

type Client = { id: number; name: string; phone_digits: string; notes: string | null };
type Visit = { id: number; starts_at: string; ends_at: string; status: string; price_som: number; service_name_snapshot: string };
type Detail = { client: Client; visits: Visit[]; timezone: string; visitCount: number };
const labels: Record<string, string> = {
    pending: "Ожидает", confirmed: "Подтверждена", cancelled: "Отменена",
    completed: "Завершена", no_show: "Не пришёл",
};

export default function ClientCard({ client, services, onClose, onBook, onSaved, onChanged }: {
    client: Client;
    services: Service[];
    onClose: () => void;
    onBook: (client: Client) => void;
    onSaved: (client: Client) => void;
    onChanged: () => void;
}) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [detail, setDetail] = useState<Detail | null>(null);
    const [name, setName] = useState(client.name);
    const [phone, setPhone] = useState(client.phone_digits);
    const [notes, setNotes] = useState(client.notes ?? "");
    const [editing, setEditing] = useState(false);
    const [history, setHistory] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(true);
    const [menu, setMenu] = useState(false);
    const [action, setAction] = useState<"merge" | "archive" | null>(null);
    const [candidates, setCandidates] = useState<Client[]>([]);
    const [query, setQuery] = useState("");
    const [sourceId, setSourceId] = useState<number | null>(null);
    const [actionBusy, setActionBusy] = useState(false);
    const [actionError, setActionError] = useState("");
    const savedNotes = useRef(client.notes ?? "");

    useEffect(() => {
        const controller = new AbortController();
        dialog.current?.showModal();
        axios.get<Detail>(`/clients/${client.id}`, { headers: { Accept: "application/json" }, signal: controller.signal })
            .then(({ data }) => {
                setDetail(data);
                setName(data.client.name);
                setPhone(data.client.phone_digits);
                setNotes(data.client.notes ?? "");
                savedNotes.current = data.client.notes ?? "";
            })
            .catch(e => { if (!axios.isCancel(e)) setError("Не удалось загрузить карточку клиента."); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [client.id]);

    useEffect(() => {
        if (action !== "merge") return;
        const controller = new AbortController();
        axios.get<Client[]>("/clients/" + client.id + "/merge-candidates", {
            params: { q: query }, signal: controller.signal,
        }).then(({ data }) => setCandidates(data))
            .catch(e => { if (!axios.isCancel(e)) setActionError("Не удалось загрузить список клиентов."); });
        return () => controller.abort();
    }, [action, client.id, query]);

    async function confirmAction() {
        if (!action || (action === "merge" && !sourceId)) return;
        setActionBusy(true);
        setActionError("");
        try {
            if (action === "merge") {
                await axios.post("/clients/" + client.id + "/merge", { source_id: sourceId }, { headers: { Accept: "application/json" } });
            } else {
                await axios.delete("/clients/" + client.id, { headers: { Accept: "application/json" } });
            }
            onChanged();
        } catch (e) {
            setActionError(axios.isAxiosError(e) && e.response?.status === 422
                ? "Не удалось объединить: проверьте карточки и длину заметок."
                : "Действие не выполнено. Повторите попытку.");
        } finally {
            setActionBusy(false);
        }
    }

    async function save(patch: Partial<Client>) {
        setSaving(true);
        setError("");
        try {
            await axios.put(`/clients/${client.id}`, patch, { headers: { Accept: "application/json" } });
            const updated = { ...(detail?.client ?? client), ...patch };
            setDetail(old => old ? { ...old, client: updated } : old);
            onSaved(updated);
            if (patch.notes !== undefined) savedNotes.current = patch.notes ?? "";
            if (patch.name !== undefined || patch.phone_digits !== undefined) setEditing(false);
        } catch (e) {
            if (axios.isAxiosError(e) && e.response?.status === 422) {
                setError("Проверьте имя и номер телефона. Возможно, этот номер уже занят.");
            } else setError("Не удалось сохранить. Повторите попытку.");
        } finally {
            setSaving(false);
        }
    }

    const visits = detail?.visits ?? [];
    const completed = visits.filter(v => v.status === "completed");
    const latest = completed[0];
    const upcoming = visits.filter(v => ["pending", "confirmed"].includes(v.status) && new Date(v.starts_at) > new Date())
        .sort((a, b) => Date.parse(a.starts_at) - Date.parse(b.starts_at))[0];
    const daysSince = latest ? Math.floor((Date.now() - Date.parse(latest.starts_at)) / 86400000) : null;
    const status = !detail?.visitCount ? "Новый клиент" : daysSince !== null && daysSince >= 60 ? "Давно не посещал" : "Постоянный клиент";
    const format = (value: string) => new Intl.DateTimeFormat("ru", {
        timeZone: detail?.timezone || "Asia/Bishkek", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit",
    }).format(new Date(value));
    const contact = detail?.client ?? client;

    return <dialog ref={dialog} className="service-dialog client-card-dialog" aria-labelledby="client-card-heading"
        onCancel={e => { e.preventDefault(); if (!saving && !actionBusy) onClose(); }}>
        <div className="service-dialog-header">
            <span className="client-card-eyebrow">КЛИЕНТ</span>
            <button className="ghost" aria-label="Закрыть карточку" type="button" onClick={onClose} disabled={actionBusy}>×</button>
        </div>
        <div className="client-card-body">
            {loading ? <p className="muted" role="status">Загрузка…</p> : error && !detail ? <p role="alert" className="errors">{error}</p> : <>
                <div className="client-card-identity">
                    {editing ? <form onSubmit={e => { e.preventDefault(); void save({ name: name.trim(), phone_digits: phone.replace(/\D/g, "") }); }}>
                        <label htmlFor="client-card-name">Имя</label>
                        <input id="client-card-name" required maxLength={100} value={name} onChange={e => setName(e.target.value)} />
                        <label htmlFor="client-card-phone">Телефон без +</label>
                        <input id="client-card-phone" required inputMode="tel" maxLength={25} value={phone} onChange={e => setPhone(e.target.value)} />
                        <div className="row wrap"><button type="submit" className="primary" disabled={saving}>Сохранить</button>
                            <button type="button" className="ghost" onClick={() => { setEditing(false); setName(contact.name); setPhone(contact.phone_digits); }}>Отмена</button></div>
                    </form> : <>
                        <div className="row between">
                            <h2 id="client-card-heading">{contact.name}</h2>
                            <button type="button" className="ghost" onClick={() => setEditing(true)} aria-label="Редактировать клиента">Изменить</button>
                        </div>
                        <a href={`tel:+${contact.phone_digits}`}>+{contact.phone_digits}</a>
                        <p className="client-card-status">{status}</p>
                    </>}
                </div>
                <div className="client-card-stats">
                    <div><span>Посещений</span><strong>{detail?.visitCount ?? 0}</strong></div>
                    <div><span>Последний визит</span><strong>{latest ? format(latest.starts_at).split(",")[0] : "—"}</strong></div>
                </div>
                {upcoming && <section className="client-card-section">
                    <h3>Ближайшая запись</h3>
                    <div className="client-card-visit"><strong>{upcoming.service_name_snapshot}</strong>
                        <span>{format(upcoming.starts_at)} · {Number(upcoming.price_som).toLocaleString("ru-RU")} сом</span></div>
                </section>}
                <section className="client-card-section">
                    <label htmlFor="client-card-notes">Заметка мастера</label>
                    <textarea id="client-card-notes" rows={2} maxLength={2000} placeholder="Предпочтения и важные детали"
                        value={notes} onChange={e => setNotes(e.target.value)}
                        onBlur={() => { if (notes !== savedNotes.current) void save({ notes }); }} />
                    {notes !== savedNotes.current && <button type="button" className="ghost" disabled={saving}
                        onClick={() => void save({ notes })}>{saving ? "Сохранение…" : "Сохранить заметку"}</button>}
                </section>
                <section className="client-card-section">
                    <button type="button" className="client-card-history-toggle" aria-expanded={history} onClick={() => setHistory(v => !v)}>
                        История записей <span>{history ? "Скрыть" : "Показать"}</span>
                    </button>
                    {history && <div className="client-card-history">
                        {visits.length === 0 ? <p className="muted">Записей пока нет.</p> :
                            visits.map(v => <div key={v.id} className="client-card-visit">
                                <strong>{v.service_name_snapshot}</strong>
                                <span>{format(v.starts_at)} · {Number(v.price_som).toLocaleString("ru-RU")} сом · {labels[v.status] ?? v.status}</span>
                            </div>)}
                    </div>}
                </section>
                {error && <p className="errors" role="alert">{error}</p>}
                <div className="client-card-actions">
                    <button className="primary" type="button" disabled={!services.some(s => s.is_active)} onClick={() => onBook(contact)}>+ Записать</button>
                    <a className="ghost" aria-label="Позвонить" title="Позвонить" href={`tel:+${contact.phone_digits}`}>Телефон</a>
                    <a className="ghost" aria-label="WhatsApp" title="WhatsApp" href={`https://wa.me/${contact.phone_digits}`} target="_blank" rel="noreferrer">WhatsApp</a>
                    <button className="ghost" type="button" aria-expanded={menu} aria-label="Дополнительные действия" onClick={() => setMenu(v => !v)}>···</button>
                </div>
                {menu && !action && <div className="client-card-menu">
                    <button type="button" className="ghost" onClick={() => { setAction("merge"); setMenu(false); setActionError(""); }}>Объединить с другим клиентом</button>
                    <button type="button" className="ghost" onClick={() => { setAction("archive"); setMenu(false); setActionError(""); }}>Удалить из списка</button>
                </div>}
                {action === "merge" && <section className="client-card-confirm">
                    <strong>Объединение клиентов</strong>
                    <p className="muted small">Все записи выбранного клиента перейдут в карточку «{contact.name}». История и заметки сохранятся, а вторая карточка исчезнет.</p>
                    <label htmlFor="merge-search">Найти второй профиль</label>
                    <input id="merge-search" type="search" value={query} maxLength={100} placeholder="Имя или телефон"
                        onChange={e => { setQuery(e.target.value); setSourceId(null); }} />
                    <div className="client-card-candidates">
                        {candidates.length ? candidates.map(c => <label key={c.id} className="client-card-candidate">
                            <input type="radio" name="merge-target" checked={sourceId === c.id} onChange={() => setSourceId(c.id)} />
                            <span><strong>{c.name}</strong><small>+{c.phone_digits}</small></span>
                        </label>) : <p className="muted small">Другие клиенты не найдены.</p>}
                    </div>
                    <div className="row wrap">
                        <button type="button" className="primary" onClick={() => void confirmAction()} disabled={actionBusy || !sourceId}>
                            {actionBusy ? "Объединяем…" : "Подтвердить объединение"}
                        </button>
                        <button type="button" className="ghost" disabled={actionBusy} onClick={() => setAction(null)}>Отмена</button>
                    </div>
                </section>}
                {action === "archive" && <section className="client-card-confirm">
                    <strong>Удалить клиента из списка?</strong>
                    <p className="muted small">Карточка исчезнет из списка клиентов. Существующие записи, история и финансовые данные останутся без изменений. При новой записи на тот же номер карточка восстановится.</p>
                    <div className="row wrap">
                        <button type="button" className="primary" disabled={actionBusy} onClick={() => void confirmAction()}>
                            {actionBusy ? "Удаляем…" : "Удалить из списка"}
                        </button>
                        <button type="button" className="ghost" disabled={actionBusy} onClick={() => setAction(null)}>Отмена</button>
                    </div>
                </section>}
                {actionError && <p role="alert" className="errors">{actionError}</p>}
            </>}
        </div>
    </dialog>;
}
