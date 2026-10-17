import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import Icon from "../Icon";
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
function ServiceEditor({ service }: { service?: Service }) {
    const f = useForm({
        name: service?.name ?? "",
        description: service?.description ?? "",
        duration_minutes: service?.duration_minutes ?? 60,
        price_som: service?.price_som ?? 0,
        is_active: service?.is_active ?? true,
    });
    return (
        <form
            className="service-editor"
            onSubmit={(e) => {
                e.preventDefault();
                if (service) f.post(`/services/${service.id}`);
                else f.post("/services", { onSuccess: () => f.reset() });
            }}
        >
            <Errors errors={f.errors} />
            <div className="form-grid">
                <Field label="Название">
                    <input
                        required
                        maxLength={150}
                        value={f.data.name}
                        onChange={(e) => f.setData("name", e.target.value)}
                    />
                </Field>
                <Field label="Описание">
                    <input
                        maxLength={2000}
                        value={f.data.description}
                        onChange={(e) =>
                            f.setData("description", e.target.value)
                        }
                    />
                </Field>
                <Field label="Длительность, минут">
                    <input
                        required
                        type="number"
                        min={5}
                        max={720}
                        value={f.data.duration_minutes}
                        onChange={(e) =>
                            f.setData(
                                "duration_minutes",
                                Number(e.target.value),
                            )
                        }
                    />
                </Field>
                <Field label="Цена, сом">
                    <input
                        required
                        type="number"
                        min={0}
                        max={10000000}
                        value={f.data.price_som}
                        onChange={(e) =>
                            f.setData("price_som", Number(e.target.value))
                        }
                    />
                </Field>
            </div>
            <div className="row between">
                <label className="check">
                    <input
                        type="checkbox"
                        checked={f.data.is_active}
                        onChange={(e) =>
                            f.setData("is_active", e.target.checked)
                        }
                    />
                    Доступна для записи
                </label>
                <button className="primary" disabled={f.processing}>
                    {service ? "Сохранить" : "Добавить услугу"}
                </button>
            </div>
        </form>
    );
}
function Schedule({ hours }: { hours: Hour[] }) {
    const f = useForm({
        hours: hours.map((h) => ({
            weekday: Number(h.weekday),
            starts_at: h.starts_at.slice(0, 5),
            ends_at: h.ends_at.slice(0, 5),
        })),
    });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                f.post("/schedule", {
                    preserveScroll: true,
                    onSuccess: () => f.setDefaults(),
                });
            }}
        >
            <p className="muted">
                Одно рабочее окно на день. Существующие записи при изменении
                расписания сохраняются.
            </p>
            <Errors errors={f.errors} />
            {days.map((day, i) => {
                const h = f.data.hours.find((h) => h.weekday === i + 1);
                return (
                    <div className="schedule-row" key={day}>
                        <label className="check">
                            <input
                                type="checkbox"
                                checked={!!h}
                                onChange={(e) =>
                                    f.setData(
                                        "hours",
                                        e.target.checked
                                            ? [
                                                  ...f.data.hours,
                                                  {
                                                      weekday: i + 1,
                                                      starts_at: "09:00",
                                                      ends_at: "18:00",
                                                  },
                                              ]
                                            : f.data.hours.filter(
                                                  (h) => h.weekday !== i + 1,
                                              ),
                                    )
                                }
                            />
                            {day}
                        </label>
                        {h ? (
                            <div className="row">
                                <input
                                    aria-label={`${day}: начало`}
                                    required
                                    type="time"
                                    value={h.starts_at}
                                    onChange={(e) =>
                                        f.setData(
                                            "hours",
                                            f.data.hours.map((x) =>
                                                x.weekday === i + 1
                                                    ? {
                                                          ...x,
                                                          starts_at:
                                                              e.target.value,
                                                      }
                                                    : x,
                                            ),
                                        )
                                    }
                                />
                                <span>—</span>
                                <input
                                    aria-label={`${day}: окончание`}
                                    required
                                    type="time"
                                    value={h.ends_at}
                                    onChange={(e) =>
                                        f.setData(
                                            "hours",
                                            f.data.hours.map((x) =>
                                                x.weekday === i + 1
                                                    ? {
                                                          ...x,
                                                          ends_at:
                                                              e.target.value,
                                                      }
                                                    : x,
                                            ),
                                        )
                                    }
                                />
                            </div>
                        ) : (
                            <span className="muted">Выходной</span>
                        )}
                    </div>
                );
            })}
            <button className="primary" disabled={f.processing}>
                {f.processing ? "Сохраняем…" : "Сохранить расписание"}
            </button>
            {f.recentlySuccessful && (
                <p role="status" className="accent">
                    Расписание сохранено
                </p>
            )}
        </form>
    );
}
function Profile({ master }: { master: Master }) {
    const f = useForm({
        display_name: master.display_name,
        specialty: master.specialty ?? "",
        bio: master.bio ?? "",
        phone: master.phone ?? "",
        address: master.address ?? "",
        timezone: master.timezone,
        booking_enabled: master.booking_enabled,
        auto_confirm: master.auto_confirm,
        whatsapp_notifications: master.whatsapp_notifications ?? false,
    });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                f.post("/profile");
            }}
        >
            <Errors errors={f.errors} />
            <div className="form-grid">
                <Field label="Имя на странице">
                    <input
                        required
                        maxLength={100}
                        value={f.data.display_name}
                        onChange={(e) =>
                            f.setData("display_name", e.target.value)
                        }
                    />
                </Field>
                <Field label="Специализация">
                    <input
                        maxLength={150}
                        value={f.data.specialty}
                        onChange={(e) => f.setData("specialty", e.target.value)}
                    />
                </Field>
                <Field label="Адрес">
                    <input
                        maxLength={255}
                        value={f.data.address}
                        onChange={(e) => f.setData("address", e.target.value)}
                    />
                </Field>
                <Field label="Телефон">
                    <input
                        type="tel"
                        maxLength={30}
                        value={f.data.phone}
                        onChange={(e) => f.setData("phone", e.target.value)}
                    />
                </Field>
            </div>
            <Field label="О себе">
                <textarea
                    maxLength={2000}
                    value={f.data.bio}
                    onChange={(e) => f.setData("bio", e.target.value)}
                />
            </Field>
            <Field label="Часовой пояс">
                <select
                    value={f.data.timezone}
                    onChange={(e) => f.setData("timezone", e.target.value)}
                >
                    {[
                        ...new Set([
                            master.timezone,
                            "Asia/Bishkek",
                            "Asia/Almaty",
                            "Asia/Tashkent",
                            "Europe/Moscow",
                            "UTC",
                        ]),
                    ].map((z) => (
                        <option key={z}>{z}</option>
                    ))}
                </select>
            </Field>
            <label className="check">
                <input
                    type="checkbox"
                    checked={f.data.booking_enabled}
                    onChange={(e) =>
                        f.setData("booking_enabled", e.target.checked)
                    }
                />
                Принимать онлайн-записи
            </label>
            <label className="check">
                <input
                    type="checkbox"
                    checked={f.data.auto_confirm}
                    onChange={(e) =>
                        f.setData("auto_confirm", e.target.checked)
                    }
                />
                Подтверждать записи автоматически
            </label>
            <label className="check">
                <input
                    type="checkbox"
                    checked={f.data.whatsapp_notifications}
                    onChange={(e) =>
                        f.setData("whatsapp_notifications", e.target.checked)
                    }
                />
                Получать подтверждённые записи в WhatsApp на указанный выше
                телефон
            </label>
            <p className="muted small">
                Автоматические уведомления начнут работать после подключения
                отправки. Для связи с клиентом уже доступна кнопка WhatsApp в
                записи.
            </p>
            <button className="primary" disabled={f.processing}>
                Сохранить профиль
            </button>
        </form>
    );
}
export default function Dashboard({
    master,
    services,
    hours,
    appointments,
    publicUrl,
    whatsappAutomatic,
}: {
    master: Master;
    services: Service[];
    hours: Hour[];
    appointments: Appointment[];
    publicUrl: string;
    whatsappAutomatic: boolean;
}) {
    const [tab, setTab] = useState("appointments");
    const [filter, setFilter] = useState("active");
    const [message, setMessage] = useState("");
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
                    <div className="avatar">
                        {master.display_name.charAt(0)}
                    </div>
                </header>
                <Notice />
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
                                    profile: "Публичная страница",
                                }[tab]
                            }
                        </h2>
                        {tab === "appointments" && (
                            <select
                                aria-label="Фильтр записей"
                                value={filter}
                                onChange={(e) => setFilter(e.target.value)}
                            >
                                <option value="active">Активные</option>
                                <option value="all">
                                    Все за последние 30 дней и будущие
                                </option>
                            </select>
                        )}
                    </div>
                    {tab === "appointments" && (
                        <>
                            <p className="muted">
                                Последние 200 записей. Время указано в часовом
                                поясе мастера.
                            </p>
                            <div className="appointment-list">
                                {appointments
                                    .filter(
                                        (a) =>
                                            filter === "all" ||
                                            ["pending", "confirmed"].includes(
                                                a.status,
                                            ),
                                    )
                                    .map((a) => (
                                        <article
                                            className="appointment"
                                            key={a.id}
                                        >
                                            <div>
                                                <span className="accent">
                                                    {date(a.starts_at)}
                                                </span>
                                                <h3>{a.client_name}</h3>
                                                <p>
                                                    {a.services_snapshot?.length
                                                        ? a.services_snapshot
                                                              .map(
                                                                  (s) => s.name,
                                                              )
                                                              .join(" + ")
                                                        : a.service_name_snapshot}{" "}
                                                    ·{" "}
                                                    {a.price_som.toLocaleString()}{" "}
                                                    сом
                                                </p>
                                                <a
                                                    href={`tel:${a.client_phone.replace(/[^+0-9]/g, "")}`}
                                                >
                                                    {a.client_phone}
                                                </a>
                                                {a.client_comment && (
                                                    <p className="muted">
                                                        {a.client_comment}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="appt-actions">
                                                <span
                                                    className={`badge ${a.status}`}
                                                >
                                                    {statuses[a.status]}
                                                </span>
                                                {a.status === "confirmed" &&
                                                    a.whatsapp_link && (
                                                        <a
                                                            className="ghost"
                                                            href={
                                                                a.whatsapp_link
                                                            }
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            Сообщить клиенту в
                                                            WhatsApp ↗
                                                        </a>
                                                    )}
                                                {a.notifications?.map((n) => (
                                                    <small key={n.id}>
                                                        WhatsApp{" "}
                                                        {n.recipient ===
                                                        "client"
                                                            ? "клиенту"
                                                            : "мастеру"}
                                                        :{" "}
                                                        {(
                                                            {
                                                                accepted:
                                                                    "принято сервисом",
                                                                not_configured:
                                                                    "отправка не подключена",
                                                                failed: "ошибка отправки",
                                                                unknown:
                                                                    "результат неизвестен — проверьте перед повтором",
                                                                sending:
                                                                    "передаётся сервису",
                                                            } as Record<
                                                                string,
                                                                string
                                                            >
                                                        )[n.status] || n.status}
                                                    </small>
                                                ))}
                                                {whatsappAutomatic &&
                                                    a.status === "confirmed" &&
                                                    a.notifications?.some((n) =>
                                                        [
                                                            "failed",
                                                            "not_configured",
                                                        ].includes(n.status),
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
                                                            Повторить
                                                            автоматическую
                                                            отправку
                                                        </button>
                                                    )}
                                                {[
                                                    "pending",
                                                    "confirmed",
                                                ].includes(a.status) && (
                                                    <div className="row wrap">
                                                        {a.status ===
                                                            "pending" && (
                                                            <button
                                                                className="primary"
                                                                disabled={
                                                                    pending ===
                                                                    a.id
                                                                }
                                                                onClick={() =>
                                                                    changeStatus(
                                                                        a,
                                                                        "confirmed",
                                                                    )
                                                                }
                                                            >
                                                                Подтвердить
                                                            </button>
                                                        )}
                                                        {a.status ===
                                                            "confirmed" &&
                                                            new Date(
                                                                a.ends_at,
                                                            ) <= new Date() && (
                                                                <button
                                                                    className="ghost"
                                                                    disabled={
                                                                        pending ===
                                                                        a.id
                                                                    }
                                                                    onClick={() =>
                                                                        changeStatus(
                                                                            a,
                                                                            "completed",
                                                                        )
                                                                    }
                                                                >
                                                                    Завершить
                                                                </button>
                                                            )}
                                                        <button
                                                            className="ghost"
                                                            disabled={
                                                                pending === a.id
                                                            }
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        "Отменить запись?",
                                                                    )
                                                                )
                                                                    changeStatus(
                                                                        a,
                                                                        "cancelled",
                                                                    );
                                                            }}
                                                        >
                                                            Отменить
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        </article>
                                    ))}
                            </div>
                            {!appointments.some(
                                (a) =>
                                    filter === "all" ||
                                    ["pending", "confirmed"].includes(a.status),
                            ) && (
                                <div className="empty">
                                    <h3>Здесь появятся ваши записи</h3>
                                    <p>
                                        Добавьте услуги, настройте расписание и
                                        поделитесь ссылкой на страницу.
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
                    {tab === "services" && (
                        <>
                            {services.map((s) => (
                                <ServiceEditor key={s.id} service={s} />
                            ))}
                            <h3>Новая услуга</h3>
                            <ServiceEditor />
                        </>
                    )}
                    {tab === "schedule" && <Schedule hours={hours} />}
                    {tab === "profile" && (
                        <>
                            <p className="link-box">
                                <a
                                    href={publicUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    {publicUrl} ↗
                                </a>
                            </p>
                            <Profile master={master} />
                        </>
                    )}
                </section>
            </main>
        </div>
    );
}
