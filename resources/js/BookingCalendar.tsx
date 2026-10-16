import { useEffect, useState } from "react";

const parse = (value: string) => new Date(`${value}T12:00:00`);
const iso = (value: Date) =>
    `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, "0")}-${String(value.getDate()).padStart(2, "0")}`;
const longDate = (value: Date) =>
    value.toLocaleDateString("ru-RU", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });

export default function BookingCalendar({
    value,
    min,
    max,
    workingDays,
    onChange,
}: {
    value: string;
    min: string;
    max: string;
    workingDays: number[];
    onChange: (date: string) => void;
}) {
    const [month, setMonth] = useState(value.slice(0, 7));
    useEffect(() => setMonth(value.slice(0, 7)), [value]);
    const first = parse(`${month}-01`);
    const offset = (first.getDay() + 6) % 7;
    const count = new Date(
        first.getFullYear(),
        first.getMonth() + 1,
        0,
    ).getDate();
    const title = first.toLocaleDateString("ru-RU", {
        month: "long",
        year: "numeric",
    });
    const move = (delta: number) =>
        setMonth(
            iso(
                new Date(first.getFullYear(), first.getMonth() + delta, 1, 12),
            ).slice(0, 7),
        );
    return (
        <section className="booking-calendar" aria-label="Выбор даты">
            <div className="calendar-heading">
                <button
                    type="button"
                    className="calendar-nav"
                    aria-label="Предыдущий месяц"
                    disabled={month <= min.slice(0, 7)}
                    onClick={() => move(-1)}
                >
                    ‹
                </button>
                <h3 aria-live="polite">{title}</h3>
                <button
                    type="button"
                    className="calendar-nav"
                    aria-label="Следующий месяц"
                    disabled={month >= max.slice(0, 7)}
                    onClick={() => move(1)}
                >
                    ›
                </button>
            </div>
            <div className="calendar-grid">
                {["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"].map((day) => (
                    <span key={day} className="calendar-weekday">
                        {day}
                    </span>
                ))}
                {Array.from({ length: offset }, (_, i) => (
                    <span key={`empty-${i}`} />
                ))}
                {Array.from({ length: count }, (_, i) => {
                    const date = new Date(
                        first.getFullYear(),
                        first.getMonth(),
                        i + 1,
                        12,
                    );
                    const key = iso(date);
                    const working = workingDays.includes(date.getDay() || 7);
                    const disabled = key < min || key > max || !working;
                    return (
                        <button
                            key={key}
                            type="button"
                            disabled={disabled}
                            aria-label={`${longDate(date)}${!working ? ", нерабочий день" : ""}`}
                            aria-pressed={key === value}
                            aria-current={key === min ? "date" : undefined}
                            className={`calendar-day${key === value ? " selected" : ""}${key === min ? " today" : ""}`}
                            onClick={() => onChange(key)}
                        >
                            {i + 1}
                            <span className="calendar-dot" />
                        </button>
                    );
                })}
            </div>
            <div className="calendar-caption">
                <span className="calendar-dot" /> Рабочий день · свободное время
                ниже
            </div>
        </section>
    );
}
