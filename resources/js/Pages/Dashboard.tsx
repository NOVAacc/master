import { timezoneLabel } from "../timezone";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";
import axios from "axios";
import Icon from "../Icon";
import ProfileSettings from "../ProfileSettings";
import AccountSecurity from "../AccountSecurity";
import type { SecurityStatus } from "../AccountSecurity";
import { MasterAvatar } from "../ProfileIdentity";
import ServicesPanel from "../ServicesPanel";
import EditAppointmentForm from "../EditAppointmentForm";
import AppointmentCalendar from "../AppointmentCalendar";
import SlotStory from "../SlotStory";
import QuickBooking from "../QuickBooking";
import ClientCard from "../ClientCard";
import { Brand, Errors, Field, Notice } from "../Shared";
import type { Appointment, Hour, Master, Service } from "../types";
const days = [
    "Понедельник",
    "Вторник",
    "Среда",
    "Четверг",
    "Пятница",
    "Суббота",
    "Воскресенье",
];
const statuses: Record<string, string> = {
    pending: "Ожидает",
    confirmed: "Подтверждена",
    cancelled: "Отменена",
    completed: "Завершена",
    no_show: "Не пришёл",
};
function Schedule({ hours }: { hours: Hour[] }) {
    const f = useForm({
        hours: hours.map((h) => ({
            weekday: Number(h.weekday),
            starts_at: h.starts_at.slice(0, 5),
            ends_at: h.ends_at.slice(0, 5),
            break_starts_at: h.break_starts_at?.slice(0, 5) ?? null,
            break_ends_at: h.break_ends_at?.slice(0, 5) ?? null,
        })),
    });
    type WorkingDay = (typeof f.data.hours)[number];
    function updateDay(weekday: number, patch: Partial<WorkingDay>) {
        f.setData("hours", f.data.hours.map(h => h.weekday === weekday ? { ...h, ...patch } : h));
    }
    return (
        <form onSubmit={(e) => {
            e.preventDefault();
            f.post("/schedule", {
                preserveScroll: true,
                onSuccess: () => f.setDefaults(),
            });
        }}>
            <p className="muted">
                Укажите рабочее время и при необходимости обед. В часы перерыва
                клиенты не смогут записаться. Существующие записи сохраняются.
            </p>
            <Errors errors={f.errors} />
            {days.map((day, i) => {
                const weekday = i + 1;
                const h = f.data.hours.find(x => x.weekday === weekday);
                const lunch = !!h?.break_starts_at && !!h?.break_ends_at;
                return (
                    <div className="schedule-row schedule-day" key={day}>
                        <label className="check">
                            <input type="checkbox" checked={!!h} onChange={e => {
                                f.setData("hours", e.target.checked
                                    ? [...f.data.hours, {
                                        weekday, starts_at: "09:00", ends_at: "18:00",
                                        break_starts_at: "12:00", break_ends_at: "13:00",
                                    }]
                                    : f.data.hours.filter(x => x.weekday !== weekday));
                            }} />
                            {day}
                        </label>
                        {h ? (
                            <div className="schedule-day-fields">
                                <div className="schedule-hours-line">
                                    <span className="schedule-range-label">Приём</span>
                                    <div className="schedule-time-range">
                                        <input aria-label={day + ": начало работы"} required type="time"
                                            value={h.starts_at} onChange={e => updateDay(weekday, {starts_at: e.target.value})} />
                                        <span aria-hidden="true">–</span>
                                        <input aria-label={day + ": окончание работы"} required type="time"
                                            value={h.ends_at} onChange={e => updateDay(weekday, {ends_at: e.target.value})} />
                                    </div>
                                </div>
                                <div className="schedule-break-line">
                                    <label className="check schedule-lunch-switch">
                                        <input type="checkbox" checked={lunch} onChange={e => updateDay(weekday,
                                            e.target.checked
                                                ? {break_starts_at: "12:00", break_ends_at: "13:00"}
                                                : {break_starts_at: null, break_ends_at: null}
                                        )} />
                                        Перерыв
                                    </label>
                                    {lunch && (
                                        <div className="schedule-time-range">
                                            <input type="time" required aria-label={day + ": начало перерыва"}
                                                value={h.break_starts_at ?? ""}
                                                onChange={e => updateDay(weekday, {break_starts_at: e.target.value})} />
                                            <span aria-hidden="true">–</span>
                                            <input type="time" required aria-label={day + ": конец перерыва"}
                                                value={h.break_ends_at ?? ""}
                                                onChange={e => updateDay(weekday, {break_ends_at: e.target.value})} />
                                        </div>
                                    )}
                                </div>
                            </div>
                        ) : <span className="muted">Выходной</span>}
                    </div>
                );
            })}
            <button className="primary" disabled={f.processing}>
                {f.processing ? "Сохраняем…" : "Сохранить расписание"}
            </button>
            {f.recentlySuccessful && <p role="status" className="accent">Расписание сохранено</p>}
        </form>
    );
}
export default function Dashboard({
    master,
    security,
    services,
    clients,
    groupSessions,
    hours,
    appointments,
    publicUrl,
    whatsappAutomatic,
    calendarDate,
    calendarAppointments,
    calendarBlocks,
}: {
    master: Master;
    security: SecurityStatus;
    services: Service[];
    clients: { id: number; name: string; phone_digits: string; notes: string | null }[];
    groupSessions: { id: number; service_name_snapshot: string; starts_at: string; capacity: number; reserved_seats: number; status: string; price_som: number; participants: {id: number; client_name: string; client_phone: string; status: string}[] }[];
    hours: Hour[];
    appointments: Appointment[];
    publicUrl: string;
    whatsappAutomatic: boolean;
    calendarDate: string;
    calendarAppointments: Appointment[];
    calendarBlocks: {
        id: number;
        starts_at: string;
        ends_at: string;
        reason: string | null;
    }[];
}) {
    const [tab, setTab] = useState("appointments");
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [notificationsOpen, setNotificationsOpen] = useState(false);
    const [storyOpen, setStoryOpen] = useState(false);
    const [storyAppointment, setStoryAppointment] = useState<Appointment | null>(null);
    const openStory = (appointment: Appointment | null = null) => { setStoryAppointment(appointment); setNotificationsOpen(false); setStoryOpen(true); };
    const [appointmentFilter, setAppointmentFilter] = useState<"all" | "upcoming" | "pending">("all");
    useEffect(() => {
        if (!mobileMenuOpen) return;
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === "Escape") setMobileMenuOpen(false);
        };
        window.addEventListener("keydown", closeOnEscape);
        return () => window.removeEventListener("keydown", closeOnEscape);
    }, [mobileMenuOpen]);
    const selectTab = (nextTab: string) => {
        setTab(nextTab);
        setMobileMenuOpen(false);
        setNotificationsOpen(false);
        if (nextTab === "appointments") {
            setAppointmentFilter("all");
            setAppointmentView("calendar");
        }
    };
    const [quickBooking, setQuickBooking] = useState(false);
    const [selectedClient, setSelectedClient] = useState<(typeof clients)[number] | null>(null);
    const [bookingClient, setBookingClient] = useState<(typeof clients)[number] | null>(null);
    const [clientOverrides, setClientOverrides] = useState<Record<number, (typeof clients)[number]>>({});
    const [clientQuery, setClientQuery] = useState("");
    const [clientSearchResults, setClientSearchResults] = useState<(typeof clients)>([]);
    const [clientSearching, setClientSearching] = useState(false);
    const [clientSearchError, setClientSearchError] = useState("");
    const [clientSearchRevision, setClientSearchRevision] = useState(0);
    useEffect(() => {
        const query = clientQuery.trim();
        if (tab !== "clients" || !query) {
            setClientSearching(false);
            setClientSearchError("");
            setClientSearchResults([]);
            return;
        }
        const controller = new AbortController();
        setClientSearching(true);
        setClientSearchError("");
        const timer = window.setTimeout(() => {
            axios.get<typeof clients>("/clients/search", { params: { q: query }, signal: controller.signal })
                .then(({ data }) => setClientSearchResults(data))
                .catch(e => {
                    if (!axios.isCancel(e)) setClientSearchError("Не удалось найти клиентов. Попробуйте ещё раз.");
                })
                .finally(() => { if (!controller.signal.aborted) setClientSearching(false); });
        }, 250);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [clientQuery, clientSearchRevision, tab]);
    const visibleClients = (clientQuery.trim() ? clientSearchResults : clients)
        .map(client => clientOverrides[client.id] ?? client);
    const [appointmentView, setAppointmentView] = useState<"calendar" | "list">(
        "calendar",
    );
    const [calendarLoading, setCalendarLoading] = useState(false);
    const navigateCalendar = (date: string) =>
        router.get(
            "/dashboard",
            { calendar_date: date },
            {
                only: [
                    "calendarDate",
                    "calendarAppointments",
                    "calendarBlocks",
                ],
                preserveState: true,
                preserveScroll: true,
                onStart: () => setCalendarLoading(true),
                onFinish: () => setCalendarLoading(false),
                onError: () =>
                    setMessage(
                        "Не удалось загрузить календарь. Повторите переход.",
                    ),
            },
        );
    const [message, setMessage] = useState("");
    const [editingId, setEditingId] = useState<number | null>(null);
    const [pending, setPending] = useState<number | null>(null);
    const date = (s: string) =>
        new Intl.DateTimeFormat("ru", {
            timeZone: master.timezone,
            dateStyle: "medium",
            timeStyle: "short",
        }).format(new Date(s));
    const upcoming = appointments.filter(
        (a) =>
            ["pending", "confirmed"].includes(a.status) &&
            new Date(a.starts_at) > new Date(),
    );
    const recentCancelled = appointments.filter(a => a.status === "cancelled" && new Date(a.starts_at) > new Date()).slice(0, 5);
    const awaitingConfirmation = appointments.filter(a =>
        a.status === "pending" && new Date(a.starts_at) > new Date()
    );
    const showNotificationAppointments = (filter: "upcoming" | "pending") => {
        setAppointmentFilter(filter);
        setAppointmentView("list");
        setTab("appointments");
        setMobileMenuOpen(false);
        setNotificationsOpen(false);
    };
    const visibleAppointments = appointmentFilter === "upcoming" ? upcoming
        : appointmentFilter === "pending" ? awaitingConfirmation : appointments;
    useEffect(() => {
        if (!notificationsOpen) return;
        const dismiss = (event: KeyboardEvent) => {
            if (event.key === "Escape") setNotificationsOpen(false);
        };
        window.addEventListener("keydown", dismiss);
        return () => window.removeEventListener("keydown", dismiss);
    }, [notificationsOpen]);
    const changeStatus = (a: Appointment, status: string) => {
        setPending(a.id);
        setMessage("");
        router.post(
            `/appointments/${a.id}`,
            { status },
            {
                preserveScroll: true,
                onError: () =>
                    setMessage(
                        "Не удалось изменить статус. Обновите страницу и повторите.",
                    ),
                onFinish: () => setPending(null),
            },
        );
    };
    const renderAppointment = (a: Appointment) => (
        <div className="appointment-entry" key={a.id}>
            <article className="appointment">
                <div>
                    <span className="accent">{date(a.starts_at)}</span>
                    <h3>{a.client_name}</h3>
                    <p>
                        {a.services_snapshot?.length
                            ? a.services_snapshot.map((s) => s.name).join(" + ")
                            : a.service_name_snapshot}{" "}
                        · {a.price_som.toLocaleString()} сом ·{" "}
                        {Math.round(
                            (Date.parse(a.ends_at) - Date.parse(a.starts_at)) /
                                60000,
                        )}{" "}
                        мин
                    </p>
                    <a href={`tel:${a.client_phone.replace(/[^+0-9]/g, "")}`}>
                        {a.client_phone}
                    </a>
                    {a.client_comment && (
                        <p className="muted">{a.client_comment}</p>
                    )}
                </div>
                <div className="appt-actions">
                    <span className={`badge ${a.status}`}>
                        {statuses[a.status]}
                    </span>
                    {a.status === "cancelled" && new Date(a.starts_at) > new Date() && (
                        <button type="button" className="ghost" onClick={() => openStory(a)}>Создать сторис об окне</button>
                    )}
                    {a.status === "confirmed" && a.whatsapp_link && (
                        <a
                            className="ghost"
                            href={a.whatsapp_link}
                            target="_blank"
                            rel="noreferrer"
                        >
                            {a.notification_revision > 1
                                ? "Сообщить об изменениях в WhatsApp ↗"
                                : "Сообщить клиенту в WhatsApp ↗"}
                        </a>
                    )}
                    {a.notifications?.map((n) => (
                        <small key={n.id}>
                            WhatsApp{" "}
                            {n.recipient === "client" ? "клиенту" : "мастеру"}:{" "}
                            {(
                                {
                                    recipient_changed:
                                        "номер изменён — отправка остановлена",
                                    accepted: "принято сервисом",
                                    not_configured: "отправка не подключена",
                                    failed: "ошибка отправки",
                                    unknown:
                                        "результат неизвестен — проверьте перед повтором",
                                    sending: "передаётся сервису",
                                } as Record<string, string>
                            )[n.status] || n.status}
                        </small>
                    ))}
                    {whatsappAutomatic &&
                        a.status === "confirmed" &&
                        a.notifications?.some((n) =>
                            ["failed", "not_configured"].includes(n.status),
                        ) && (
                            <button
                                type="button"
                                className="ghost"
                                onClick={() =>
                                    router.post(
                                        `/appointments/${a.id}/whatsapp`,
                                        {},
                                        {
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            >
                                Повторить автоматическую отправку
                            </button>
                        )}
                    {["pending", "confirmed"].includes(a.status) && (
                        <div className="row wrap">
                            <button
                                type="button"
                                className="ghost"
                                disabled={pending === a.id}
                                onClick={() =>
                                    setEditingId(
                                        editingId === a.id ? null : a.id,
                                    )
                                }
                            >
                                Перенести / изменить
                            </button>
                            {a.status === "pending" && (
                                <button
                                    className="primary"
                                    disabled={pending === a.id}
                                    onClick={() => changeStatus(a, "confirmed")}
                                >
                                    Подтвердить
                                </button>
                            )}
                            {a.status === "confirmed" &&
                                new Date(a.ends_at) <= new Date() && (
                                    <button
                                        className="ghost"
                                        disabled={pending === a.id}
                                        onClick={() =>
                                            changeStatus(a, "completed")
                                        }
                                    >
                                        Завершить
                                    </button>
                                )}
                            <button
                                className="ghost"
                                disabled={pending === a.id}
                                onClick={() => {
                                    if (confirm("Отменить запись?"))
                                        changeStatus(a, "cancelled");
                                }}
                            >
                                Отменить
                            </button>
                        </div>
                    )}
                </div>
            </article>
            {editingId === a.id && (
                <EditAppointmentForm
                    key={`${a.id}-${a.revision}`}
                    appointment={a}
                    services={services}
                    timezone={master.timezone}
                    onClose={() => setEditingId(null)}
                />
            )}
            {!!a.changes?.length && (
                <details className="appointment-history">
                    <summary>История изменений ({a.changes.length})</summary>
                    {a.changes.map((change) => (
                        <div key={change.id} className="appointment-change">
                            <strong>{date(change.created_at)}</strong>
                            {change.reason && <p>{change.reason}</p>}
                            <p>
                                {date(change.before.starts_at)} ·{" "}
                                {change.before.duration_minutes} мин ·{" "}
                                {change.before.price_som.toLocaleString(
                                    "ru-RU",
                                )}{" "}
                                сом
                                <br />→ {date(change.after.starts_at)} ·{" "}
                                {change.after.duration_minutes} мин ·{" "}
                                {change.after.price_som.toLocaleString("ru-RU")}{" "}
                                сом
                            </p>
                            {change.before.service_name !==
                                change.after.service_name && (
                                <p>
                                    Услуги: {change.before.service_name} →{" "}
                                    {change.after.service_name}
                                </p>
                            )}
                            {change.before.client_name !==
                                change.after.client_name && (
                                <p>
                                    Имя: {change.before.client_name} →{" "}
                                    {change.after.client_name}
                                </p>
                            )}
                            {change.before.client_phone !==
                                change.after.client_phone && (
                                <p>
                                    Телефон: {change.before.client_phone} →{" "}
                                    {change.after.client_phone}
                                </p>
                            )}
                            {change.before.client_comment !==
                                change.after.client_comment && (
                                <p>
                                    Комментарий:{" "}
                                    {change.before.client_comment || "—"} →{" "}
                                    {change.after.client_comment || "—"}
                                </p>
                            )}
                        </div>
                    ))}
                </details>
            )}
        </div>
    );
    return (
        <div className="shell">
            <Head title="Кабинет" />
            {storyOpen && <SlotStory master={master} services={services} initialDate={calendarDate}
                initialAppointment={storyAppointment ?? undefined} onClose={() => setStoryOpen(false)} />}
            <aside className={`sidebar${mobileMenuOpen ? " mobile-menu-open" : ""}`}>
                <div className="sidebar-mobile-header">
                    <Brand />
                    <span className="mobile-header-title">Кабинет</span>
                    <button
                        type="button"
                        className="sidebar-menu-toggle"
                        aria-label={mobileMenuOpen ? "Закрыть меню" : "Открыть меню"}
                        aria-controls="master-sidebar-navigation"
                        aria-expanded={mobileMenuOpen}
                        onClick={() => setMobileMenuOpen(open => !open)}
                    >
                        <span className="sidebar-menu-lines" aria-hidden="true"><i /><i /><i /></span>
                    </button>
                </div>
                <p className="eyebrow">ВАШЕ ДЕЛО</p>
                <nav id="master-sidebar-navigation" aria-label="Разделы кабинета">
                    {Object.entries({
                        appointments: "Записи",
                        services: "Услуги",
                        clients: "Клиенты",
                        groups: "Групповые занятия",
                        schedule: "Расписание",
                        profile: "Моя страница",
                    }).map(([key, label]) => (
                        <button
                            key={key}
                            className={tab === key ? "active" : ""}
                            onClick={() => selectTab(key)}
                        >
                            <Icon
                                name={
                                    {
                                        appointments: "calendar",
                                        services: "grid",
                                        clients: "user",
                                        groups: "calendar",
                                        schedule: "clock",
                                        profile: "user",
                                    }[key] || "grid"
                                }
                            />
                            <span>{label}</span>
                            <span className="nav-chevron" aria-hidden="true">
                                ›
                            </span>
                        </button>
                    ))}
                </nav>
                <nav className="sidebar-settings" aria-label="Настройки">
                    <button
                        type="button"
                        className={tab === "settings" ? "active" : ""}
                        aria-current={tab === "settings" ? "page" : undefined}
                        onClick={() => selectTab("settings")}
                    >
                        <Icon name="settings" />
                        <span>Настройки профиля</span>
                        <span className="nav-chevron" aria-hidden="true">›</span>
                    </button>
                </nav>
                <div className="sidebar-bottom">
                    <a href={publicUrl} target="_blank" rel="noreferrer">
                        Открыть страницу ↗
                    </a>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-button"
                    >
                        Выйти
                    </Link>
                </div>
            </aside>
            <main className="main">
                <header className="row between dashboard-header">
                    <div>
                        <p className="eyebrow">
                            ЛИЧНЫЙ КАБИНЕТ · {timezoneLabel(master.timezone)}
                        </p>
                        <h1>{master.display_name}</h1>
                    </div>
                    <div className="dashboard-header-actions">
                        <div className="dashboard-notifications">
                            <button
                                type="button"
                                className="dashboard-notification-trigger"
                                onClick={() => setNotificationsOpen(open => !open)}
                                aria-label={`Уведомления: предстоящих записей ${upcoming.length}, ждут подтверждения ${awaitingConfirmation.length}`}
                                aria-expanded={notificationsOpen}
                                aria-controls="master-notification-panel"
                            >
                                <span className="notification-counter" title="Предстоящие записи">{upcoming.length}</span>
                                <Icon name="bell" />
                                <span className="notification-counter notification-counter-pending" title="Ждут подтверждения">{awaitingConfirmation.length}</span>
                            </button>
                            {notificationsOpen && (
                                <div id="master-notification-panel" className="dashboard-notification-panel">
                                    <div className="dashboard-notification-heading">Уведомления</div>
                                    <button type="button" onClick={() => showNotificationAppointments("upcoming")}>
                                        <span>Предстоящие записи</span><strong>{upcoming.length}</strong>
                                    </button>
                                    <button type="button" onClick={() => showNotificationAppointments("pending")}>
                                        <span>Ждут подтверждения</span><strong>{awaitingConfirmation.length}</strong>
                                    </button>
                                    {recentCancelled.length > 0 && <div className="story-notification-links">
                                        <strong>Освободившееся время</strong>
                                        {recentCancelled.map(a => <button type="button" key={a.id} onClick={() => openStory(a)}>
                                            <span>{date(a.starts_at)} · Создать сторис</span><Icon name="calendar" />
                                        </button>)}
                                    </div>}
                                    <p>Выберите категорию, чтобы увидеть соответствующие записи.</p>
                                </div>
                            )}
                        </div>
                        <MasterAvatar master={master} />
                    </div>
                </header>
                <Notice />
                {message && (
                    <div className="errors" role="alert">
                        {message}
                    </div>
                )}
                <section className="panel">
                    <div className="row between">
                        <h2>
                            {
                                {
                                    appointments: "Записи клиентов",
                                    services: "Ваши услуги",
                                    clients: "Клиенты",
                                    groups: "Групповые занятия",
                                    schedule: "Рабочая неделя",
                                    profile: "Моя страница",
                                    settings: "Настройки профиля",
                                }[tab]
                            }
                        </h2>
                        {tab === "appointments" && (
                            <div className="row wrap appointment-view-controls">
                                <button type="button" className="ghost" onClick={() => openStory()}>Создать сторис</button>
                                <button type="button" className="primary" onClick={() => setQuickBooking(true)} disabled={!services.some(s => s.is_active)}>+ Записать клиента</button>
                                <div
                                    className="display-switch text-switch"
                                    role="group"
                                    aria-label="Вид записей"
                                >
                                    <button
                                        type="button"
                                        aria-pressed={
                                            appointmentView === "calendar"
                                        }
                                        onClick={() => {
                                            setAppointmentView("calendar");
                                            setAppointmentFilter("all");
                                        }}
                                    >
                                        Календарь
                                    </button>
                                    <button
                                        type="button"
                                        aria-pressed={
                                            appointmentView === "list"
                                        }
                                        onClick={() => {
                                            setAppointmentView("list");
                                            setAppointmentFilter("all");
                                        }}
                                    >
                                        Список
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                    {tab === "appointments" && (
                        <>
                            {appointmentView === "calendar" ? (
                                <AppointmentCalendar
                                    onCreateStory={() => openStory()}
                                    appointments={calendarAppointments}
                                    blocks={calendarBlocks}
                                    hours={hours}
                                    timezone={master.timezone}
                                    date={calendarDate}
                                    loading={calendarLoading}
                                    onNavigate={navigateCalendar}
                                    renderDetails={renderAppointment}
                                />
                            ) : (
                                <>
                                    <p className="muted">
                                        {appointmentFilter === "upcoming" ? "Предстоящие записи." : appointmentFilter === "pending" ? "Записи, ожидающие подтверждения." : "Последние 200 записей."} Время указано в
                                        часовом поясе мастера.
                                    </p>
                                    {appointmentFilter !== "all" && (
                                        <button type="button" className="ghost appointment-filter-reset" onClick={() => setAppointmentFilter("all")}>Показать все записи</button>
                                    )}
                                    <div className="appointment-list">
                                        {visibleAppointments.map(renderAppointment)}
                                    </div>
                                    {!visibleAppointments.length && (
                                        <div className="empty">
                                            {appointmentFilter === "upcoming" ? (
                                                <>
                                                    <h3>Предстоящих записей пока нет</h3>
                                                    <p>Здесь появятся будущие записи клиентов.</p>
                                                </>
                                            ) : appointmentFilter === "pending" ? (
                                                <>
                                                    <h3>Нет записей на подтверждение</h3>
                                                    <p>Все предстоящие записи обработаны.</p>
                                                </>
                                            ) : (
                                                <>
                                                    <h3>Здесь появятся ваши записи</h3>
                                                    <p>Добавьте услуги, настройте расписание и поделитесь ссылкой на страницу.</p>
                                                    <a href={publicUrl} target="_blank" rel="noreferrer">{publicUrl}</a>
                                                </>
                                            )}
                                        </div>
                                    )}
                                </>
                            )}
                        </>
                    )}
                    {tab === "clients" && (
                        <div className="clients-section">
                            <div className="clients-toolbar">
                                <label className="clients-search" htmlFor="clients-search-input">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none"
                                        stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                        <circle cx="10.8" cy="10.8" r="6.8" />
                                        <path d="m16 16 4.2 4.2" />
                                    </svg>
                                    <input id="clients-search-input" type="search" autoComplete="off" maxLength={100}
                                        aria-label="Поиск клиентов по имени или телефону"
                                        placeholder="Поиск по имени или телефону"
                                        value={clientQuery} onChange={e => setClientQuery(e.target.value)} />
                                </label>
                                <span className="clients-count" role="status">
                                    {clientSearching ? "Поиск…" : clientQuery.trim()
                                        ? `Найдено: ${visibleClients.length}${visibleClients.length === 100 ? "+" : ""}`
                                        : `Показано клиентов: ${visibleClients.length}${visibleClients.length === 200 ? "+" : ""}`}
                                </span>
                            </div>
                            {clientSearchError ? (
                                <div className="clients-empty" role="alert">
                                    {clientSearchError}
                                    <button className="ghost" type="button" onClick={() => setClientSearchRevision(n => n + 1)}>Повторить</button>
                                </div>
                            ) : clientSearching ? (
                                <div className="clients-empty" role="status">Поиск клиентов…</div>
                            ) : visibleClients.length === 0 ? (
                                <div className="clients-empty">
                                    {clientQuery.trim()
                                        ? "Клиенты не найдены. Проверьте имя или номер телефона."
                                        : "Пока нет клиентов. После первой записи здесь появится карточка."}
                                </div>
                            ) : (
                                <div className="clients-grid" aria-busy={clientSearching}>
                                    {visibleClients.map(client => (
                                        <button type="button" key={client.id} className="clients-item"
                                            onClick={() => setSelectedClient(client)}>
                                            <span className="clients-item-info">
                                                <strong>{client.name}</strong>
                                                <span>+{client.phone_digits}</span>
                                            </span>
                                            <svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none"
                                                stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                                <path d="m9 5 7 7-7 7" />
                                            </svg>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                    {tab === "groups" && (
                        <div>
                            <p className="muted">Групповые занятия бронируют время мастера один раз. Количество участников ограничено вместимостью.</p>
                            <form onSubmit={event => {
                                event.preventDefault();
                                const data = new FormData(event.currentTarget);
                                router.post("/group-sessions", {
                                    service_id: Number(data.get("service_id")),
                                    starts_at_local: String(data.get("starts_at_local")),
                                    capacity: Number(data.get("capacity")),
                                }, { preserveScroll: true });
                            }}>
                                <Field label="Услуга">
                                    <select name="service_id" required>
                                        {services.filter(service => service.is_active).map(service =>
                                            <option key={service.id} value={service.id}>{service.name}</option>)}
                                    </select>
                                </Field>
                                <Field label="Дата и время">
                                    <input name="starts_at_local" type="datetime-local" required step={1800}/>
                                </Field>
                                <Field label="Количество мест">
                                    <input name="capacity" type="number" min={2} max={100} defaultValue={8} required/>
                                </Field>
                                <button className="primary" type="submit" disabled={!services.some(s => s.is_active)}>Создать занятие</button>
                            </form>
                            <h3>Ближайшие занятия</h3>
                            {groupSessions.length === 0 && <p className="muted">Занятий пока нет.</p>}
                            {groupSessions.map(session => (
                                <article className="appointment" key={session.id}>
                                    <div>
                                        <strong>{session.service_name_snapshot}</strong>
                                        <p>{date(session.starts_at)} · {session.price_som.toLocaleString("ru-RU")} сом</p>
                                        <p className="muted">Занято {session.reserved_seats} из {session.capacity} мест</p>
                                        {session.participants.length > 0 && (
                                            <details>
                                                <summary>Участники ({session.participants.length})</summary>
                                                {session.participants.map(person => (
                                                    <div className="row between wrap" key={person.id}>
                                                        <div>
                                                            <strong>{person.client_name}</strong>
                                                            <p className="muted small">{person.client_phone} · {person.status === "cancelled" ? "Отменён" : "Записан"}</p>
                                                        </div>
                                                        {person.status === "confirmed" && (
                                                            <button className="ghost" type="button"
                                                                onClick={() => {
                                                                    if (window.confirm("Снять участника с занятия?")) {
                                                                        router.post(`/group-sessions/${session.id}/participants/${person.id}/cancel`, {}, {preserveScroll: true});
                                                                    }
                                                                }}>Снять с занятия</button>
                                                        )}
                                                    </div>
                                                ))}
                                            </details>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                    {tab === "services" && (
                        <ServicesPanel services={services} publicUrl={publicUrl} />
                    )}
                    {tab === "schedule" && <Schedule hours={hours} />}
                    {tab === "profile" && (
                        <>
                            <p className="muted">
                                Это ссылка на вашу страницу, которую видят клиенты.
                            </p>
                            <p className="link-box">
                                <a
                                    href={publicUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    {publicUrl} ↗
                                </a>
                            </p>
                            <ProfileSettings master={master} />
                        </>
                    )}
                    {tab === "settings" && <AccountSecurity status={security} />}
                </section>
            </main>
            {selectedClient && <ClientCard key={selectedClient.id} client={selectedClient} services={services}
                onClose={() => setSelectedClient(null)}
                onSaved={updated => setClientOverrides(old => ({ ...old, [updated.id]: updated }))}
                onChanged={() => { setSelectedClient(null); setClientSearchRevision(n => n + 1); router.reload({ only: ["clients", "appointments", "calendarAppointments"] }); }}
                onBook={target => { setBookingClient(target); setSelectedClient(null); setQuickBooking(true); }} />}
            {quickBooking && <QuickBooking services={services} timezone={master.timezone} date={calendarDate}
                initialClient={bookingClient} onClose={() => { setQuickBooking(false); setBookingClient(null); }} />}
        </div>
    );
}
