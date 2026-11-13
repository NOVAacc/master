import { localizedDateTimeFormat } from "./localized-date";
import { useI18n } from "./i18n";
import CloseButton from "./CloseButton";
import { useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";
import type { Appointment, Hour } from "./types";
import {
    addDays,
    arrangeIntervals,
    clockMinutes,
    intervalOnDay,
    localDay,
    weekStart,
} from "./calendar";

type TimeBlock = {
    id: number;
    starts_at: string;
    ends_at: string;
    reason: string | null;
};
const labels: Record<string, string> = {
    pending: "Ожидает",
    confirmed: "Подтверждена",
    cancelled: "Отменена",
    completed: "Завершена",
    no_show: "Не пришёл",
};
const minuteLabel = (value: number) =>
    `${String(Math.floor(value / 60)).padStart(2, "0")}:${String(value % 60).padStart(2, "0")}`;

export default function AppointmentCalendar({
    appointments,
    blocks,
    hours,
    timezone,
    date,
    loading,
    onNavigate,
    renderDetails,
    onCreateBooking,
}: {
    appointments: Appointment[];
    blocks: TimeBlock[];
    hours: Hour[];
    timezone: string;
    date: string;
    loading: boolean;
    onNavigate: (date: string) => void;
    renderDetails: (appointment: Appointment) => ReactNode;
    onCreateBooking?: (date: string, time: string) => void;
}) {
    const { t: tr, locale } = useI18n();
const dayLabel = (day: string, options: Intl.DateTimeFormatOptions) =>
    localizedDateTimeFormat(locale, {
        timeZone: "UTC",
        ...options,
    }).format(new Date(`${day}T12:00:00Z`));

    const root = useRef<HTMLDivElement>(null);
    const detail = useRef<HTMLDialogElement>(null);
    const [detailPosition, setDetailPosition] = useState({left: 16, top: 16});
    const [compact, setCompact] = useState(false);
    const [now, setNow] = useState(() => Date.now());
    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 30_000);
        return () => window.clearInterval(timer);
    }, []);
    const [mode, setMode] = useState<"auto" | "day" | "week">("auto");
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const view = mode === "auto" ? (compact ? "day" : "week") : mode;
    const first = weekStart(date);
    const days =
        view === "day"
            ? [date]
            : Array.from({ length: 7 }, (_, i) => addDays(first, i));
    const today = localDay(new Date(now), timezone);
    const selected = appointments.find((a) => a.id === selectedId);
    useEffect(() => {
        const observer = new ResizeObserver((entries) =>
            setCompact(entries[0].contentRect.width < 620),
        );
        if (root.current) observer.observe(root.current);
        return () => observer.disconnect();
    }, []);
    useEffect(() => {
        if (selected && detail.current && !detail.current.open) detail.current.showModal();
    }, [selectedId]);
    const entries = days.map((day) => {
        const bookings = appointments.flatMap((a) => {
            const interval = intervalOnDay(
                a.starts_at,
                a.ends_at,
                day,
                timezone,
            );
            return interval
                ? [
                      {
                          ...interval,
                          id: `a-${a.id}`,
                          appointment: a,
                          block: null as TimeBlock | null,
                      },
                  ]
                : [];
        });
        const busy = blocks.flatMap((b) => {
            const interval = intervalOnDay(
                b.starts_at,
                b.ends_at,
                day,
                timezone,
            );
            return interval
                ? [
                      {
                          ...interval,
                          id: `b-${b.id}`,
                          appointment: null as Appointment | null,
                          block: b,
                      },
                  ]
                : [];
        });
        return {
            day,
            items: arrangeIntervals<{
                id: string;
                start: number;
                end: number;
                appointment: Appointment | null;
                block: TimeBlock | null;
            }>([...bookings, ...busy]),
        };
    });
    const all = entries.flatMap((entry) => entry.items);
    const weekdays = new Set(days.map(day =>
        new Date(`${day}T12:00:00Z`).getUTCDay() || 7,
    ));
    const visibleHours = hours.filter(h => weekdays.has(h.weekday));
    // Existing appointments remain visible even after the schedule is changed.
    // Closed-time blocks do not expand the calendar beyond usable hours.
    const bookings = all.filter(item => item.appointment);
    const starts = [
        ...visibleHours.map(h => clockMinutes(h.starts_at)),
        ...bookings.map(item => item.start),
    ];
    const ends = [
        ...visibleHours.map(h => clockMinutes(h.ends_at)),
        ...bookings.map(item => item.end),
    ];
    const start = starts.length ? Math.max(0, Math.min(...starts)) : 0;
    const end = ends.length ? Math.min(1440, Math.max(...ends)) : start;
    const height = (end - start) * 1.4;
    const ticks = Array.from(
        { length: Math.ceil((end - start) / 60) },
        (_, i) => start + i * 60,
    );
    return (
        <div className="appointment-calendar" ref={root}>
            <div className="calendar-toolbar">
                <div className="row wrap">
                    <button
                        type="button"
                        className="calendar-nav"
                        disabled={loading}
                        aria-label={
                            view === "week"
                                ? tr("Предыдущая неделя")
                                : tr("Предыдущий день")
                        }
                        onClick={() =>
                            onNavigate(addDays(date, view === "week" ? -7 : -1))
                        }
                    >
                        ‹
                    </button>
                    <button
                        type="button"
                        className="ghost"
                        disabled={loading}
                        onClick={() => onNavigate(today)}
                    >
                        {tr("Сегодня")}{" "}</button>
                    <button
                        type="button"
                        className="calendar-nav"
                        disabled={loading}
                        aria-label={
                            view === "week"
                                ? tr("Следующая неделя")
                                : tr("Следующий день")
                        }
                        onClick={() =>
                            onNavigate(addDays(date, view === "week" ? 7 : 1))
                        }
                    >
                        ›
                    </button>
                </div>
                <h3 aria-live="polite">
                    {view === "day"
                        ? dayLabel(date, {
                              day: "numeric",
                              month: "long",
                              year: "numeric",
                          })
                        : `${dayLabel(first, { day: "numeric", month: "short" })} — ${dayLabel(addDays(first, 6), { day: "numeric", month: "short", year: "numeric" })}`}
                </h3>
                <div
                    className="display-switch text-switch"
                    role="group"
                    aria-label={tr("Период календаря")}
                >
                    <button
                        type="button"
                        aria-pressed={view === "day"}
                        onClick={() => setMode("day")}
                    >
                        {tr("День")}{" "}</button>
                    <button
                        type="button"
                        aria-pressed={view === "week"}
                        onClick={() => setMode("week")}
                    >
                        {tr("Неделя")}{" "}</button>
                </div>
            </div>
            {loading && (
                <p role="status" className="muted small">
                    {tr("Загружаем записи…")}{" "}</p>
            )}
            {height > 0 ? <div
                className="time-calendar-scroll"
                aria-busy={loading}
            >
                <div className={`time-calendar ${view}`}>
                    <div
                        className="time-calendar-head"
                        style={{
                            gridTemplateColumns: `54px repeat(${days.length}, minmax(0, 1fr))`,
                        }}
                    >
                        <span className="time-zone-mark">{tr("Время")}</span>
                        {days.map((day) => (
                            <button
                                key={day}
                                type="button"
                                className={`time-day-heading${day === today ? " today" : ""}`}
                                aria-current={day === today ? "date" : undefined}
                                aria-label={tr("Открыть {0}", [dayLabel(day, { day: "numeric", month: "long" })])}
                                disabled={loading}
                                onClick={() => {
                                    setMode("day");
                                    onNavigate(day);
                                }}
                            >
                                <span>
                                    {dayLabel(day, { weekday: "short" })}
                                </span>
                                <strong>
                                    {dayLabel(day, {
                                        day: "numeric",
                                        month: "short",
                                    })}
                                </strong>
                            </button>
                        ))}
                    </div>
                    <div
                        className="time-calendar-body"
                        style={{
                            gridTemplateColumns: `54px repeat(${days.length}, minmax(0, 1fr))`,
                        }}
                    >
                        <div className="time-axis" style={{ height }}>
                            {ticks.map((t) => (
                                <span
                                    key={t}
                                    style={{ top: (t - start) * 1.4 }}
                                >
                                    {minuteLabel(t)}
                                </span>
                            ))}
                        </div>
                        {entries.map(({ day, items }) => {
                            const weekday =
                                new Date(`${day}T12:00:00Z`).getUTCDay() || 7;
                            const work = hours.filter(
                                (h) => h.weekday === weekday,
                            );
                            return (
                                <div
                                    key={day}
                                    className={`time-day-column${view === "week" && day === today ? " today" : ""}`}
                                    style={{ height }}
                                    aria-label={dayLabel(day, {
                                        day: "numeric",
                                        month: "long",
                                    })}
                                    onClick={event => {
                                        if (!onCreateBooking || loading ||
                                            (event.target as HTMLElement).closest(".calendar-appointment, .calendar-time-block")) return;
                                        const y = event.clientY - event.currentTarget.getBoundingClientRect().top;
                                        const minute = Math.floor((start + y / 1.4) / 30) * 30;
                                        const availableWork = work.some(h => {
                                            const starts = clockMinutes(h.starts_at), ends = clockMinutes(h.ends_at);
                                            const breakStarts = h.break_starts_at ? clockMinutes(h.break_starts_at) : null;
                                            const breakEnds = h.break_ends_at ? clockMinutes(h.break_ends_at) : null;
                                            return minute >= starts && minute < ends &&
                                                !(breakStarts !== null && breakEnds !== null && minute >= breakStarts && minute < breakEnds);
                                        });
                                        const occupied = items.some(item => item.start <= minute && item.end > minute &&
                                            (item.block || (item.appointment && ["pending", "confirmed"].includes(item.appointment.status))));
                                        if (availableWork && !occupied) onCreateBooking(day, minuteLabel(minute));
                                    }}
                                >
                                    {work.map((h, i) => (
                                        <div
                                            key={i}
                                            className="calendar-working-time"
                                            style={{
                                                top:
                                                    (clockMinutes(h.starts_at) -
                                                        start) *
                                                    1.4,
                                                height:
                                                    (clockMinutes(h.ends_at) -
                                                        clockMinutes(
                                                            h.starts_at,
                                                        )) *
                                                    1.4,
                                            }}
                                        />
                                    ))}
                                    {items.map((item) => {
                                        const top = (item.start - start) * 1.4;
                                        const size =
                                            (item.end - item.start) * 1.4;
                                        const style = {
                                            top,
                                            height: size,
                                            left: `calc(${(item.lane / item.lanes) * 100}% + 2px)`,
                                            width: `calc(${100 / item.lanes}% - 4px)`,
                                        };
                                        if (item.block)
                                            return (
                                                <div
                                                    key={item.id}
                                                    className="calendar-time-block"
                                                    style={style}
                                                    title={`${minuteLabel(item.start)}–${minuteLabel(item.end)} · ${item.block.reason || tr("Время закрыто")}`}
                                                >
                                                    {minuteLabel(item.start)}–
                                                    {minuteLabel(item.end)}
                                                    <br />
                                                    {item.block.reason ||
                                                        tr("Время закрыто")}
                                                </div>
                                            );
                                        const a = item.appointment!;
                                        const past = new Date(a.ends_at).getTime() <= now;
                                        const text = `${minuteLabel(item.start)}–${minuteLabel(item.end)} · ${a.client_name} · ${a.client_phone} · ${a.service_name_snapshot} · ${tr(labels[a.status] || a.status)}${past ? tr(" · Прошедшая запись") : ""}`;
                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                className={`calendar-appointment ${a.status}${past ? " past" : ""}${selectedId === a.id ? " selected" : ""}${size < 48 ? " short" : ""}`}
                                                style={style}
                                                aria-label={text}
                                                aria-pressed={
                                                    selectedId === a.id
                                                }
                                                aria-haspopup="dialog"
                                                onClick={event => {
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    const width = Math.min(460, window.innerWidth - 32);
                                                    const left = rect.right + width + 16 <= window.innerWidth ? rect.right + 12 : rect.left - width - 12 >= 16 ? rect.left - width - 12 : Math.max(16, Math.min(rect.left, window.innerWidth - width - 16));
                                                    const height = Math.min(560, window.innerHeight - 32);
                                                    setDetailPosition({left, top: Math.max(16, Math.min(rect.top, window.innerHeight - height - 16))});
                                                    setSelectedId(a.id);
                                                }}
                                            >
                                                <span className="calendar-appt-time">
                                                    {minuteLabel(item.start)}–
                                                    {minuteLabel(item.end)}
                                                </span>
                                                {a.payment_status === "paid" && <span className="calendar-paid-marker" title={tr("Оплачено")} aria-label={tr("Оплачено")}>✓</span>}
                                                <strong>{a.client_name}</strong>
                                                <span className="calendar-appt-phone">{a.client_phone}</span>
                                                {size >= 88 && (
                                                    <span>
                                                        {
                                                            a.service_name_snapshot
                                                        }
                                                    </span>
                                                )}
                                                {size >= 112 && (
                                                    <small>
                                                        {a.price_som.toLocaleString(
                                                            "ru-RU",
                                                        )}{" "}
                                                        {tr("сом ·")}{" "}{tr(labels[a.status] ?? a.status)}
                                                    </small>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
            : <p className="muted small">{tr("На выбранный период рабочие часы не настроены.")}</p>}
            <div className="calendar-legend">
                <span className="badge pending">{tr("Ожидает")}</span>
                <span className="badge confirmed">{tr("Подтверждена")}</span>
                <span className="badge completed" title={tr("Мастер отметил, что приём состоялся")}>{tr("Завершена")}</span>
                <span className="badge calendar-past" title={tr("Время записи закончилось. Это не отметка о посещении")}>{tr("Время прошло")}</span>
            </div>
            {!all.length && (
                <p className="muted small">{tr("На выбранный период записей нет.")}</p>
            )}
            {selected && (
                <dialog
                    className="calendar-detail calendar-detail-popup"
                    ref={detail}
                    style={detailPosition}
                    aria-labelledby="calendar-detail-title"
                    onCancel={event => { event.preventDefault(); setSelectedId(null); }}
                    onClose={() => setSelectedId(null)}
                    onClick={event => {
                        if (event.target !== event.currentTarget) return;
                        const r = event.currentTarget.getBoundingClientRect();
                        if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) setSelectedId(null);
                    }}
                >
                    <div className="row between">
                        <h3 id="calendar-detail-title">{tr("Детали записи")}</h3>
                        <CloseButton label={tr("Закрыть детали записи")} onClick={() => setSelectedId(null)} />
                    </div>
                    {renderDetails(selected)}
                </dialog>
            )}
        </div>
    );
}
