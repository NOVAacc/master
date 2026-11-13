import { localizedDateTimeFormat } from "./localized-date";
import { useI18n } from "./i18n";
import axios from "axios";
import CloseButton from "./CloseButton";
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
    const { t: tr, locale } = useI18n();
    const dialog = useRef<HTMLDialogElement>(null);
    const [detail, setDetail] = useState<Detail | null>(null);
    const [name, setName] = useState(client.name);
    const [phone, setPhone] = useState(client.phone_digits);
    const [notes, setNotes] = useState(client.notes ?? "");
    const [editing, setEditing] = useState(false);
    const [history, setHistory] = useState(false);
    const [saving, setSaving] = useState(false);
    const [notesSaved, setNotesSaved] = useState(false);
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(true);
    const [menu, setMenu] = useState(false);
    const [action, setAction] = useState<"merge" | "archive" | null>(null);
    const [candidates, setCandidates] = useState<Client[]>([]);
    const [query, setQuery] = useState("");
    const [sourceId, setSourceId] = useState<number | null>(null);
    const [actionBusy, setActionBusy] = useState(false);
    const [actionError, setActionError] = useState("");
    const saveInFlight = useRef(false);
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
            .catch(e => { if (!axios.isCancel(e)) setError(tr("Не удалось загрузить карточку клиента.")); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [client.id]);

    useEffect(() => {
        if (action !== "merge") return;
        const controller = new AbortController();
        axios.get<Client[]>("/clients/" + client.id + "/merge-candidates", {
            params: { q: query }, signal: controller.signal,
        }).then(({ data }) => setCandidates(data))
            .catch(e => { if (!axios.isCancel(e)) setActionError(tr("Не удалось загрузить список клиентов.")); });
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
                ? tr("Не удалось объединить: проверьте карточки и длину заметок.")
                : tr("Действие не выполнено. Повторите попытку."));
        } finally {
            setActionBusy(false);
        }
    }

    async function save(patch: Partial<Client>) {
        if (saveInFlight.current) return;
        saveInFlight.current = true;
        setSaving(true);
        setError("");
        try {
            const { data } = await axios.post<{ saved: boolean; client: Client }>(`/clients/${client.id}`, patch, { headers: { Accept: "application/json" } });
            const updated = data.client;
            setDetail(old => old ? { ...old, client: updated } : old);
            onSaved(updated);
            if (patch.notes !== undefined) {
                savedNotes.current = updated.notes ?? "";
                setNotes(updated.notes ?? "");
                setNotesSaved(true);
            }
            if (patch.name !== undefined || patch.phone_digits !== undefined) setEditing(false);
        } catch (e) {
            if (axios.isAxiosError(e)) {
                const status = e.response?.status;
                if (status === 422) {
                    const errors = e.response?.data?.errors as Record<string, string[] | string> | undefined;
                    const first = errors ? Object.values(errors)[0] : undefined;
                    setError(first ? (Array.isArray(first) ? first[0] : first) : "Не удалось сохранить. Повторите попытку.");
                } else if (status === 419 || status === 401) {
                    setError("Сеанс истёк. Скопируйте заметку и обновите страницу.");
                } else if (status === 429) {
                    setError("Слишком много попыток сохранения. Подождите минуту и повторите.");
                } else setError("Не удалось сохранить. Повторите попытку.");
            } else setError("Не удалось сохранить. Повторите попытку.");
        } finally {
            saveInFlight.current = false;
            setSaving(false);
        }
    }

    const visits = detail?.visits ?? [];
    const completed = visits.filter(v => v.status === "completed");
    const latest = completed[0];
    const upcoming = visits.filter(v => ["pending", "confirmed"].includes(v.status) && new Date(v.starts_at) > new Date())
        .sort((a, b) => Date.parse(a.starts_at) - Date.parse(b.starts_at))[0];
    const daysSince = latest ? Math.floor((Date.now() - Date.parse(latest.starts_at)) / 86400000) : null;
    const status = !detail?.visitCount ? tr("Новый клиент") : daysSince !== null && daysSince >= 60 ? tr("Давно не посещал") : tr("Постоянный клиент");
    const format = (value: string) => localizedDateTimeFormat(locale, {
        timeZone: detail?.timezone || "Asia/Bishkek", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit",
    }).format(new Date(value));
    const contact = detail?.client ?? client;

    return <dialog ref={dialog} className="service-dialog client-card-dialog" aria-labelledby="client-card-heading"
        onCancel={e => { e.preventDefault(); if (!saving && !actionBusy) onClose(); }}>
        <div className="service-dialog-header">
            <span className="client-card-eyebrow">{tr("КЛИЕНТ")}</span>
            <CloseButton label={tr("Закрыть карточку")} onClick={onClose} disabled={actionBusy || saving} />
        </div>
        <div className="client-card-body">
            {loading ? <p className="muted" role="status">{tr("Загрузка…")}</p> : error && !detail ? <p role="alert" className="errors">{tr(error)}</p> : <>
                <div className="client-card-identity">
                    {editing ? <form onSubmit={e => { e.preventDefault(); void save({ name: name.trim(), phone_digits: phone.replace(/\D/g, "") }); }}>
                        <label htmlFor="client-card-name">{tr("Имя")}</label>
                        <input id="client-card-name" required maxLength={100} value={name} onChange={e => setName(e.target.value)} />
                        <label htmlFor="client-card-phone">{tr("Телефон без +")}</label>
                        <input id="client-card-phone" required inputMode="tel" maxLength={25} value={phone} onChange={e => setPhone(e.target.value)} />
                        <div className="row wrap"><button type="submit" className="primary" disabled={saving}>{tr("Сохранить")}</button>
                            <button type="button" className="ghost" onClick={() => { setEditing(false); setName(contact.name); setPhone(contact.phone_digits); }}>{tr("Отмена")}</button></div>
                    </form> : <>
                        <div className="row between">
                            <h2 id="client-card-heading">{contact.name}</h2>
                            <button type="button" className="ghost" onClick={() => setEditing(true)} aria-label={tr("Редактировать клиента")}>{tr("Изменить")}</button>
                        </div>
                        <a href={`tel:+${contact.phone_digits}`}>+{contact.phone_digits}</a>
                        <p className="client-card-status">{status}</p>
                    </>}
                </div>
                <div className="client-card-stats">
                    <div><span>{tr("Посещений")}</span><strong>{detail?.visitCount ?? 0}</strong></div>
                    <div><span>{tr("Последний визит")}</span><strong>{latest ? format(latest.starts_at).split(",")[0] : "—"}</strong></div>
                </div>
                {upcoming && <section className="client-card-section">
                    <h3>{tr("Ближайшая запись")}</h3>
                    <div className="client-card-visit"><strong>{upcoming.service_name_snapshot}</strong>
                        <span>{format(upcoming.starts_at)} · {Number(upcoming.price_som).toLocaleString("ru-RU")} {tr("сом")}</span></div>
                </section>}
                <section className="client-card-section">
                    <label htmlFor="client-card-notes">{tr("Заметка мастера")}</label>
                    <textarea id="client-card-notes" rows={2} maxLength={2000} placeholder={tr("Предпочтения и важные детали")}
                        value={notes} disabled={saving} onChange={e => { setNotes(e.target.value); setNotesSaved(false); setError(""); }}
                        onBlur={() => { if (notes !== savedNotes.current) void save({ notes }); }} />
                    {notes !== savedNotes.current && <button type="button" className="ghost" disabled={saving}
                        onClick={() => void save({ notes })}>{saving ? tr("Сохранение…") : tr("Сохранить заметку")}</button>}
                    {notesSaved && notes === savedNotes.current && <p className="muted small" role="status">{tr("Сохранено")}</p>}
                </section>
                <section className="client-card-section">
                    <button type="button" className="client-card-history-toggle" aria-expanded={history} onClick={() => setHistory(v => !v)}>
                        {tr("История записей")}{" "}<span>{history ? tr("Скрыть") : tr("Показать")}</span>
                    </button>
                    {history && <div className={`client-card-history${visits.length > 10 ? " is-long" : ""}`}>
                        {visits.length === 0 ? <p className="muted">{tr("Записей пока нет.")}</p> :
                            visits.map(v => <div key={v.id} className="client-card-visit">
                                <strong>{v.service_name_snapshot}</strong>
                                <span>{format(v.starts_at)} · {Number(v.price_som).toLocaleString("ru-RU")} {tr("сом ·")}{" "}{tr(labels[v.status] ?? v.status)}</span>
                            </div>)}
                    </div>}
                </section>
                {error && <p className="errors" role="alert">{tr(error)}</p>}
                <div className="client-card-actions">
                    <button className="primary" type="button" disabled={!services.some(s => s.is_active)} onClick={() => onBook(contact)}>{tr("+ Записать")}</button>
                    <a className="ghost" aria-label={tr("Позвонить")} title={tr("Позвонить")} href={`tel:+${contact.phone_digits}`}>{tr("Телефон")}</a>
                    <a className="ghost" aria-label="WhatsApp" title="WhatsApp" href={`https://wa.me/${contact.phone_digits}`} target="_blank" rel="noreferrer">WhatsApp</a>
                    <button className="ghost" type="button" aria-expanded={menu} aria-label={tr("Дополнительные действия")} onClick={() => setMenu(v => !v)}>···</button>
                </div>
                {menu && !action && <div className="client-card-menu">
                    <button type="button" className="ghost" onClick={() => { setAction("merge"); setMenu(false); setActionError(""); }}>{tr("Объединить с другим клиентом")}</button>
                    <button type="button" className="ghost" onClick={() => { setAction("archive"); setMenu(false); setActionError(""); }}>{tr("Удалить из списка")}</button>
                </div>}
                {action === "merge" && <section className="client-card-confirm">
                    <strong>{tr("Объединение клиентов")}</strong>
                    <p className="muted small">{tr("Все записи выбранного клиента перейдут в карточку «")}{contact.name}{tr("». История и заметки сохранятся, а вторая карточка исчезнет.")}</p>
                    <label htmlFor="merge-search">{tr("Найти второй профиль")}</label>
                    <input id="merge-search" type="search" value={query} maxLength={100} placeholder={tr("Имя или телефон")}
                        onChange={e => { setQuery(e.target.value); setSourceId(null); }} />
                    <div className={`client-card-candidates${candidates.length > 8 ? " is-long" : ""}`}>
                        {candidates.length ? candidates.map(c => <label key={c.id} className="client-card-candidate">
                            <input type="radio" name="merge-target" checked={sourceId === c.id} onChange={() => setSourceId(c.id)} />
                            <span><strong>{c.name}</strong><small>+{c.phone_digits}</small></span>
                        </label>) : <p className="muted small">{tr("Другие клиенты не найдены.")}</p>}
                    </div>
                    <div className="row wrap">
                        <button type="button" className="primary" onClick={() => void confirmAction()} disabled={actionBusy || !sourceId}>
                            {actionBusy ? tr("Объединяем…") : tr("Подтвердить объединение")}
                        </button>
                        <button type="button" className="ghost" disabled={actionBusy} onClick={() => setAction(null)}>{tr("Отмена")}</button>
                    </div>
                </section>}
                {action === "archive" && <section className="client-card-confirm">
                    <strong>{tr("Удалить клиента из списка?")}</strong>
                    <p className="muted small">{tr("Карточка исчезнет из списка клиентов. Существующие записи, история и финансовые данные останутся без изменений. При новой записи на тот же номер карточка восстановится.")}</p>
                    <div className="row wrap">
                        <button type="button" className="primary" disabled={actionBusy} onClick={() => void confirmAction()}>
                            {actionBusy ? tr("Удаляем…") : tr("Удалить из списка")}
                        </button>
                        <button type="button" className="ghost" disabled={actionBusy} onClick={() => setAction(null)}>{tr("Отмена")}</button>
                    </div>
                </section>}
                {actionError && <p role="alert" className="errors">{actionError}</p>}
            </>}
        </div>
    </dialog>;
}
