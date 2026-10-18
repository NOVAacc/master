import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import Icon from "../Icon";
import ProfileSettings from "../ProfileSettings";
import AccountSecurity from "../AccountSecurity";
import type { SecurityStatus } from "../AccountSecurity";
import { MasterAvatar } from "../ProfileIdentity";
import ServicesPanel from "../ServicesPanel";
import EditAppointmentForm from "../EditAppointmentForm";
import AppointmentCalendar from "../AppointmentCalendar";
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
                                        break_starts_at: null, break_ends_at: null,
                                    }]
                                    : f.data.hours.filter(x => x.weekday !== weekday));
                            }} />
                            {day}
                        </label>
                        {h ? (
                            <div className="schedule-day-fields">
                                <div className="row">
                                    <label className="schedule-time-label">
                                        <span className="muted small">С</span>
                                        <input aria-label={day + ": начало"} required type="time"
                                            value={h.starts_at} onChange={e => updateDay(weekday, {starts_at: e.target.value})} />
                                    </label>
                                    <label className="schedule-time-label">
                                        <span className="muted small">До</span>
                                        <input aria-label={day + ": окончание"} required type="time"
                                            value={h.ends_at} onChange={e => updateDay(weekday, {ends_at: e.target.value})} />
                                    </label>
                                </div>
                                <label className="check schedule-lunch-switch">
                                    <input type="checkbox" checked={lunch} onChange={e => updateDay(weekday,
                                        e.target.checked
                                            ? {break_starts_at: "13:00", break_ends_at: "14:00"}
                                            : {break_starts_at: null, break_ends_at: null}
                                    )} />
                                    Обеденный перерыв
                                </label>
                                {lunch && (
                                    <div className="row schedule-lunch-times">
                                        <label className="schedule-time-label">
                                            <span className="muted small">Начало обеда</span>
                                            <input type="time" required aria-label={day + ": начало обеда"}
                                                value={h.break_starts_at ?? ""}
                                                onChange={e => updateDay(weekday, {break_starts_at: e.target.value})} />
                                        </label>
                                        <label className="schedule-time-label">
                                            <span className="muted small">Конец обеда</span>
                                            <input type="time" required aria-label={day + ": конец обеда"}
                                                value={h.break_ends_at ?? ""}
                                                onChange={e => updateDay(weekday, {break_ends_at: e.target.value})} />
                                        </label>
                                    </div>
                                )}
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
    const [filter, setFilter] = useState("active");
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
            <aside className="sidebar">
                <Brand />
                <p className="eyebrow">ВАШЕ ДЕЛО</p>
                <nav>
                    {Object.entries({
                        appointments: "Записи",
                        services: "Услуги",
                        schedule: "Расписание",
                        profile: "Моя страница",
                    }).map(([key, label]) => (
                        <button
                            key={key}
                            className={tab === key ? "active" : ""}
                            onClick={() => setTab(key)}
                        >
                            <Icon
                                name={
                                    {
                                        appointments: "calendar",
                                        services: "grid",
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
                <div className="sidebar-settings">
                    <button
                        type="button"
                        className={tab === "settings" ? "active" : ""}
                        aria-current={tab === "settings" ? "page" : undefined}
                        onClick={() => setTab("settings")}
                    >
                        <Icon name="settings" />
                        <span>Настройки профиля</span>
                        <span className="nav-chevron" aria-hidden="true">›</span>
                    </button>
                </div>
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
                            ЛИЧНЫЙ КАБИНЕТ · {master.timezone}
                        </p>
                        <h1>{master.display_name}</h1>
                    </div>
                    <MasterAvatar master={master} />
                </header>
                <Notice />
                {(!security.emailVerified || !security.passwordSet) && (
                    <AccountSecurity status={security} />
                )}
                {!whatsappAutomatic && (
                    <p className="integration-note">
                        WhatsApp: автоматическая отправка ещё не подключена.
                        Кнопка в записи откроет готовое сообщение — отправьте
                        его в WhatsApp вручную.
                    </p>
                )}
                {message && (
                    <div className="errors" role="alert">
                        {message}
                    </div>
                )}
                <div className="metrics">
                    <article className="metric">
                        <small>Предстоящие записи</small>
                        <strong>{upcoming.length}</strong>
                    </article>
                    <article className="metric">
                        <small>Ждут подтверждения</small>
                        <strong>
                            {
                                appointments.filter(
                                    (a) => a.status === "pending",
                                ).length
                            }
                        </strong>
                    </article>
                    <article className="metric">
                        <small>Активные услуги</small>
                        <strong>
                            {services.filter((s) => s.is_active).length}
                        </strong>
                    </article>
                    <article className="metric">
                        <small>Рабочих дней в неделе</small>
                        <strong>{hours.length}</strong>
                    </article>
                </div>
                <section className="panel">
                    <div className="row between">
                        <h2>
                            {
                                {
                                    appointments: "Записи клиентов",
                                    services: "Ваши услуги",
                                    schedule: "Рабочая неделя",
                                    profile: "Моя страница",
                                    settings: "Настройки профиля",
                                }[tab]
                            }
                        </h2>
                        {tab === "appointments" && (
                            <div className="row wrap appointment-view-controls">
                                <div
                                    className="display-switch"
                                    role="group"
                                    aria-label="Вид записей"
                                >
                                    <button
                                        type="button"
                                        aria-pressed={
                                            appointmentView === "calendar"
                                        }
                                        onClick={() =>
                                            setAppointmentView("calendar")
                                        }
                                    >
                                        Календарь
                                    </button>
                                    <button
                                        type="button"
                                        aria-pressed={
                                            appointmentView === "list"
                                        }
                                        onClick={() =>
                                            setAppointmentView("list")
                                        }
                                    >
                                        Список
                                    </button>
                                </div>
                                <select
                                    aria-label="Фильтр записей"
                                    value={filter}
                                    onChange={(e) => setFilter(e.target.value)}
                                >
                                    <option value="active">Активные</option>
                                    <option value="all">Все статусы</option>
                                </select>
                            </div>
                        )}
                    </div>
                    {tab === "appointments" && (
                        <>
                            {appointmentView === "calendar" ? (
                                <AppointmentCalendar
                                    appointments={calendarAppointments.filter(
                                        (a) =>
                                            filter === "all" ||
                                            ["pending", "confirmed"].includes(
                                                a.status,
                                            ),
                                    )}
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
                                        Последние 200 записей. Время указано в
                                        часовом поясе мастера.
                                    </p>
                                    <div className="appointment-list">
                                        {appointments
                                            .filter(
                                                (a) =>
                                                    filter === "all" ||
                                                    [
                                                        "pending",
                                                        "confirmed",
                                                    ].includes(a.status),
                                            )
                                            .map(renderAppointment)}
                                    </div>
                                    {!appointments.some(
                                        (a) =>
                                            filter === "all" ||
                                            ["pending", "confirmed"].includes(
                                                a.status,
                                            ),
                                    ) && (
                                        <div className="empty">
                                            <h3>Здесь появятся ваши записи</h3>
                                            <p>
                                                Добавьте услуги, настройте
                                                расписание и поделитесь ссылкой
                                                на страницу.
                                            </p>
                                            <a
                                                href={publicUrl}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                {publicUrl}
                                            </a>
                                        </div>
                                    )}
                                </>
                            )}
                        </>
                    )}
                    {tab === "services" && (
                        <ServicesPanel services={services} />
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
                            <button type="button" className="ghost" onClick={() => setTab("settings")}>
                                Настроить профиль
                            </button>
                        </>
                    )}
                    {tab === "settings" && (
                        <>
                            <ProfileSettings master={master} />
                            {security.emailVerified && security.passwordSet && (
                                <AccountSecurity status={security} />
                            )}
                        </>
                    )}
                </section>
            </main>
        </div>
    );
}
