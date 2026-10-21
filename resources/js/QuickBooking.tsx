import { timezoneLabel } from "./timezone";
import { useEffect, useRef, useState } from "react";
import { useForm } from "@inertiajs/react";
import axios from "axios";
import CloseButton from "./CloseButton";
import { Errors, Field } from "./Shared";
import PhoneInput from "./PhoneInput";
import { addDays, localDay } from "./calendar";
import type { Service } from "./types";

type Client = { id: number; name: string; phone_digits: string };
type Slot = { value: string; label: string };

export default function QuickBooking({ services, timezone, date, initialClient, onClose }: {
    services: Service[]; timezone: string; date: string; initialClient?: Client | null; onClose: () => void;
}) {
    const dialog = useRef<HTMLDialogElement>(null);
    const active = services.filter(s => s.is_active);
    const today = localDay(new Date(), timezone);
    const [day, setDay] = useState(date >= today && date <= addDays(today, 90) ? date : today);
    const [query, setQuery] = useState("");
    const [newClient, setNewClient] = useState(false);
    const [selectedClient, setSelectedClient] = useState<Client | null>(initialClient ?? null);
    const [clients, setClients] = useState<Client[]>([]);
    const [clientsLoading, setClientsLoading] = useState(true);
    const [clientError, setClientError] = useState("");
    const [slots, setSlots] = useState<Slot[]>([]);
    const [slotsLoading, setSlotsLoading] = useState(true);
    const [slotError, setSlotError] = useState("");
    const [refresh, setRefresh] = useState(0);
    const f = useForm({
        client_id: initialClient?.id ?? null as number | null,
        client_name: "",
        client_phone: "",
        service_id: active[0]?.id ?? 0,
        starts_at: "",
        client_comment: "",
    });
    const service = active.find(s => s.id === Number(f.data.service_id));
    useEffect(() => {
        dialog.current?.showModal();
    }, []);
    useEffect(() => {
        const abort = new AbortController();
        const timer = window.setTimeout(() => {
            setClientsLoading(true);
            setClientError("");
            axios.get<Client[]>("/appointments/clients", { params: { q: query }, signal: abort.signal })
                .then(r => setClients(r.data))
                .catch(e => { if (!axios.isCancel(e)) setClientError("Не удалось загрузить клиентов. Можно добавить нового."); })
                .finally(() => { if (!abort.signal.aborted) setClientsLoading(false); });
        }, 200);
        return () => { window.clearTimeout(timer); abort.abort(); };
    }, [query]);
    useEffect(() => {
        const abort = new AbortController();
        setSlots([]);
        setSlotsLoading(true);
        setSlotError("");
        f.setData("starts_at", "");
        if (!service) { setSlotsLoading(false); return; }
        axios.get<Slot[]>("/appointments/slots", {
            params: { date: day, service_id: service.id }, signal: abort.signal,
        }).then(r => setSlots(r.data))
            .catch(e => { if (!axios.isCancel(e)) setSlotError("Не удалось загрузить время. Проверьте дату и повторите."); })
            .finally(() => { if (!abort.signal.aborted) setSlotsLoading(false); });
        return () => abort.abort();
    }, [day, service?.id, refresh]);
    return (
        <dialog ref={dialog} className="service-dialog quick-booking-dialog"
            aria-labelledby="quick-booking-title" onCancel={e => { e.preventDefault(); if (!f.processing) onClose(); }}>
            <div className="service-dialog-header">
                <h2 id="quick-booking-title">Записать клиента</h2>
                <CloseButton onClick={onClose} disabled={f.processing} />
            </div>
            <form className="quick-booking-form" onSubmit={e => {
                e.preventDefault();
                f.post("/appointments", {
                    preserveScroll: true,
                    onSuccess: onClose,
                    onError: errors => {
                        if (errors.starts_at) {
                            setRefresh(v => v + 1);
                        }
                    },
                });
            }}>
                <Errors errors={f.errors} />
                <div className="row between wrap">
                    <h3>Клиент</h3>
                    <button type="button" className="ghost" onClick={() => {
                        setNewClient(v => !v);
                        setSelectedClient(null);
                        f.setData("client_id", null);
                    }}>{newClient ? "Из моих клиентов" : "+ Новый клиент"}</button>
                </div>
                {newClient ? <div className="form-grid">
                    <Field label="Имя клиента">
                        <input autoFocus required maxLength={100} value={f.data.client_name}
                            onChange={e => f.setData("client_name", e.target.value)} autoComplete="off" />
                    </Field>
                    <Field label="Телефон">
                        <PhoneInput required label="Телефон клиента" value={f.data.client_phone}
                            onChange={value => f.setData("client_phone", value)} />
                    </Field>
                </div> : selectedClient ? (
                    <div className="quick-client-selected row between">
                        <div><strong>{selectedClient.name}</strong><p className="muted small">{selectedClient.phone_digits}</p></div>
                        <button type="button" className="ghost" onClick={() => {
                            setSelectedClient(null); f.setData("client_id", null);
                        }}>Изменить</button>
                    </div>
                ) : (
                    <div>
                        <Field label="Найти по имени или телефону">
                            <input autoFocus type="search" value={query} maxLength={100} placeholder="Начните вводить имя или телефон"
                                onChange={e => { setQuery(e.target.value); setClients([]); setClientsLoading(true); }} />
                        </Field>
                        <div className={`quick-client-results${clients.length > 8 ? " is-long" : ""}`} aria-label="Мои клиенты" aria-busy={clientsLoading}>
                            {clientsLoading ? <p className="muted small" role="status">Ищем клиентов…</p> :
                                clientError ? <p className="errors" role="alert">{clientError}</p> :
                                clients.length ? clients.map(client => (
                                    <button type="button" key={client.id} onClick={() => {
                                        setSelectedClient(client); f.setData(d => ({ ...d, client_id: client.id, client_name: "", client_phone: "" }));
                                    }}><strong>{client.name}</strong><span>{client.phone_digits}</span></button>
                                )) : <p className="muted small">Клиент не найден. Нажмите «Новый клиент».</p>}
                        </div>
                    </div>
                )}
                <div className="form-grid">
                    <Field label="Услуга">
                        <select required value={f.data.service_id} onChange={e => {
                            f.setData(d => ({ ...d, service_id: Number(e.target.value), starts_at: "" }));
                            setSlots([]);
                        }}>
                            {active.map(s => <option key={s.id} value={s.id}>{s.name} · {s.duration_minutes} мин · {s.price_som.toLocaleString("ru-RU")} сом</option>)}
                        </select>
                    </Field>
                    <Field label="Дата">
                        <input type="date" required min={today} max={addDays(today, 90)} value={day}
                            onChange={e => { setDay(e.target.value); setSlots([]); f.setData("starts_at", ""); }} />
                    </Field>
                </div>
                <h3>Свободное время</h3>
                <p className="muted small">Время мастера: {timezoneLabel(timezone)}</p>
                <div aria-busy={slotsLoading}>
                    {slotsLoading ? <p className="muted small" role="status">Проверяем время…</p> :
                        slotError ? <div><p role="alert" className="errors">{slotError}</p>
                            <button className="ghost" type="button" onClick={() => setRefresh(v => v + 1)}>Повторить</button></div> :
                        !slots.length ? <p className="muted small">На эту дату свободного времени нет. Выберите другой день.</p> :
                        <div className="quick-time-slots" role="group" aria-label="Свободное время">
                            {slots.map(slot => <button type="button" key={slot.value}
                                className={f.data.starts_at === slot.value ? "slot selected" : "slot"}
                                aria-pressed={f.data.starts_at === slot.value}
                                onClick={() => f.setData("starts_at", slot.value)}>{slot.label}</button>)}
                        </div>}
                </div>
                <details className="quick-booking-comment"><summary>Добавить комментарий</summary>
                    <Field label="Комментарий"><textarea maxLength={1000} value={f.data.client_comment}
                        onChange={e => f.setData("client_comment", e.target.value)} /></Field>
                </details>
                <div className="quick-booking-footer">
                    <p className="muted small">{service ? `${service.duration_minutes} мин · ${service.price_som.toLocaleString("ru-RU")} сом` : "Сначала добавьте активную услугу"}</p>
                    <button className="primary" disabled={f.processing || slotsLoading || !service || !f.data.starts_at ||
                        (!newClient && !selectedClient) || (newClient && (!f.data.client_name.trim() || !f.data.client_phone.trim()))}>
                        {f.processing ? "Сохраняем…" : "Записать"}
                    </button>
                </div>
            </form>
        </dialog>
    );
}
