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
const dayLabel = (day: string, options: Intl.DateTimeFormatOptions) =>
    new Date(`${day}T12:00:00Z`).toLocaleDateString("ru-RU", {
        timeZone: "UTC",
        ...options,
    });
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
}: {
    appointments: Appointment[];
    blocks: TimeBlock[];
    hours: Hour[];
    timezone: string;
    date: string;
    loading: boolean;
    onNavigate: (date: string) => void;
    renderDetails: (appointment: Appointment) => ReactNode;
}) {
    const root = useRef<HTMLDivElement>(null);
    const scroll = useRef<HTMLDivElement>(null);
    const detail = useRef<HTMLDivElement>(null);
    const [compact, setCompact] = useState(false);
    const [mode, setMode] = useState<"auto" | "day" | "week">("auto");
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const view = mode === "auto" ? (compact ? "day" : "week") : mode;
    const first = weekStart(date);
    const days =
        view === "day"
            ? [date]
            : Array.from({ length: 7 }, (_, i) => addDays(first, i));
    const today = localDay(new Date(), timezone);
    const selected = appointments.find((a) => a.id === selectedId);
    useEffect(() => {
        const observer = new ResizeObserver((entries) =>
            setCompact(entries[0].contentRect.width < 620),
        );
        if (root.current) observer.observe(root.current);
        return () => observer.disconnect();
    }, []);
    useEffect(() => {
        if (selected) detail.current?.focus();
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
    const opening = hours.map((h) => clockMinutes(h.starts_at));
    const closing = hours.map((h) => clockMinutes(h.ends_at));
    const start = Math.max(
        0,
        Math.floor(
            (Math.min(480, ...opening, ...all.map((a) => a.start)) - 30) / 60,
        ) * 60,
    );
    const end = Math.min(
        1440,
        Math.ceil(
            (Math.max(1200, ...closing, ...all.map((a) => a.end)) + 30) / 60,
        ) * 60,
    );
    const height = (end - start) * 1.4;
    const ticks = Array.from(
        { length: Math.ceil((end - start) / 60) },
        (_, i) => start + i * 60,
    );
    const scrollMinute = Math.min(
        ...opening,
        ...all.map((item) => item.start),
        540,
    );
    useEffect(() => {
        if (scroll.current)
            scroll.current.scrollTop = Math.max(
                0,
                (scrollMinute - start - 30) * 1.4,
            );
    }, [view, date, start, scrollMinute]);
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
                                ? "Предыдущая неделя"
                                : "Предыдущий день"
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
                        Сегодня
                    </button>
                    <button
                        type="button"
                        className="calendar-nav"
                        disabled={loading}
                        aria-label={
                            view === "week"
                                ? "Следующая неделя"
                                : "Следующий день"
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
                    aria-label="Период календаря"
                >
                    <button
                        type="button"
                        aria-pressed={view === "day"}
                        onClick={() => setMode("day")}
                    >
                        День
                    </button>
                    <button
                        type="button"
                        aria-pressed={view === "week"}
                        onClick={() => setMode("week")}
                    >
                        Неделя
                    </button>
                </div>
            </div>
            <p className="muted small">
                {timezone} · сетка по 30 минут. Белые участки рабочего дня
                свободны; серые — вне расписания. Нажмите запись, чтобы открыть
                детали.
            </p>
            {loading && (
                <p role="status" className="muted small">
                    Загружаем записи…
                </p>
            )}
            <div
                className="time-calendar-scroll"
                ref={scroll}
                aria-busy={loading}
            >
                <div className={`time-calendar ${view}`}>
                    <div
                        className="time-calendar-head"
                        style={{
                            gridTemplateColumns: `54px repeat(${days.length}, minmax(0, 1fr))`,
                        }}
                    >
                        <span className="time-zone-mark">Время</span>
                        {days.map((day) => (
                            <button
                                key={day}
                                type="button"
                                className={`time-day-heading${day === today ? " today" : ""}`}
                                aria-label={`Открыть ${dayLabel(day, { day: "numeric", month: "long" })}`}
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
                                    className="time-day-column"
                                    style={{ height }}
                                    aria-label={dayLabel(day, {
                                        day: "numeric",
                                        month: "long",
                                    })}
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
                                                    title={`${minuteLabel(item.start)}–${minuteLabel(item.end)} · ${item.block.reason || "Время закрыто"}`}
                                                >
                                                    {minuteLabel(item.start)}–
                                                    {minuteLabel(item.end)}
                                                    <br />
                                                    {item.block.reason ||
                                                        "Время закрыто"}
                                                </div>
                                            );
                                        const a = item.appointment!;
                                        const text = `${minuteLabel(item.start)}–${minuteLabel(item.end)} · ${a.client_name} · ${a.service_name_snapshot} · ${labels[a.status] || a.status}`;
                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                className={`calendar-appointment ${a.status}${selectedId === a.id ? " selected" : ""}${size < 48 ? " short" : ""}`}
                                                style={style}
                                                aria-label={text}
                                                aria-pressed={
                                                    selectedId === a.id
                                                }
                                                title={text}
                                                onClick={() =>
                                                    setSelectedId(a.id)
                                                }
                                            >
                                                <span className="calendar-appt-time">
                                                    {minuteLabel(item.start)}–
                                                    {minuteLabel(item.end)}
                                                </span>
                                                <strong>{a.client_name}</strong>
                                                {size >= 64 && (
                                                    <span>
                                                        {
                                                            a.service_name_snapshot
                                                        }
                                                    </span>
                                                )}
                                                {size >= 96 && (
                                                    <small>
                                                        {a.price_som.toLocaleString(
                                                            "ru-RU",
                                                        )}{" "}
                                                        сом · {labels[a.status]}
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
            <div className="calendar-legend">
                <span className="badge pending">Ожидает</span>
                <span className="badge confirmed">Подтверждена</span>
                <span className="badge completed">Завершена</span>
            </div>
            {!all.length && (
                <p className="muted small">На выбранный период записей нет.</p>
            )}
            {selected && (
                <div
                    className="calendar-detail"
                    ref={detail}
                    tabIndex={-1}
                    aria-label="Детали записи"
                >
                    <div className="row between">
                        <h3>Детали записи</h3>
                        <button
                            type="button"
                            className="ghost"
                            onClick={() => setSelectedId(null)}
                        >
                            Закрыть
                        </button>
                    </div>
                    {renderDetails(selected)}
                </div>
            )}
        </div>
    );
}
