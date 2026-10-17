import { Head, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import BookingCalendar from "../BookingCalendar";
import { Brand, Errors, Field, Notice } from "../Shared";
import type { Master, Service } from "../types";
type Props = {
    master: Master;
    services: Service[];
    selectedServices: number[];
    workingDays: number[];
    date: string;
    minDate: string;
    maxDate: string;
    slots: { value: string; label: string }[];
    whatsappAutomatic: boolean;
    bookingReceipt: {
        status: string;
        text: string;
        masterLink: string | null;
        automaticStatus: string | null;
    } | null;
};
export default function Booking(p: Props) {
    const f = useForm({
        service_ids: p.selectedServices,
        starts_at: "",
        client_name: "",
        client_phone: "",
        client_comment: "",
        consent: false,
        whatsapp_consent: false,
    });
    const [chosenDate, setChosenDate] = useState(p.date);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState("");
    const selected = p.services.filter((s) =>
        f.data.service_ids.includes(s.id),
    );
    const duration = selected.reduce((sum, s) => sum + s.duration_minutes, 0);
    const price = selected.reduce((sum, s) => sum + s.price_som, 0);
    const ready =
        !loading &&
        chosenDate === p.date &&
        [...f.data.service_ids].sort().join(",") ===
            [...p.selectedServices].sort().join(",");
    const change = (date: string, ids: number[]) => {
        setChosenDate(date);
        setLoadError("");
        f.setData((d) => ({ ...d, starts_at: "", service_ids: ids }));
        router.get(
            `/m/${p.master.slug}`,
            { date, services: ids, selection: 1 },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onStart: () => setLoading(true),
                onError: () =>
                    setLoadError(
                        "Не удалось обновить свободное время. Повторите выбор даты.",
                    ),
                onFinish: () => setLoading(false),
            },
        );
    };
    return (
        <main className="booking-wrap">
            <Head title={p.master.display_name} />
            <Brand />
            <div className="booking-grid">
                <aside className="panel profile-card">
                    <div className="avatar large">
                        {p.master.display_name.charAt(0)}
                    </div>
                    <h1>{p.master.display_name}</h1>
                    <p className="accent">
                        {p.master.specialty || "Частный специалист"}
                    </p>
                    <p>{p.master.bio}</p>
                    <p className="muted">{p.master.address}</p>
                    <hr />
                    <small>
                        Время указано для часового пояса
                        <br />
                        {p.master.timezone}
                    </small>
                </aside>
                <section className="panel">
                    <p className="eyebrow">ОНЛАЙН-ЗАПИСЬ</p>
                    <h1>Выберите удобное время</h1>
                    <Notice />
                    {p.bookingReceipt && (
                        <section
                            className="booking-receipt"
                            aria-label="Итог записи"
                        >
                            <h2>
                                {p.bookingReceipt.status === "confirmed"
                                    ? "Вы записаны"
                                    : "Заявка отправлена мастеру"}
                            </h2>
                            <p className="receipt-text">
                                {p.bookingReceipt.text}
                            </p>
                            {p.bookingReceipt.status !== "confirmed" && (
                                <p className="muted small">
                                    Запись пока ожидает подтверждения мастера.
                                </p>
                            )}
                            {p.bookingReceipt.automaticStatus === "accepted" ? (
                                <p className="muted small">
                                    Уведомление принято сервисом WhatsApp.
                                    Доставка ещё не подтверждена.
                                </p>
                            ) : (
                                p.bookingReceipt.automaticStatus && (
                                    <p className="muted small">
                                        Автоматическое сообщение пока не
                                        отправлено или его результат неизвестен.
                                        При необходимости свяжитесь с мастером.
                                    </p>
                                )
                            )}
                            {p.bookingReceipt.masterLink ? (
                                <>
                                    <a
                                        className="primary"
                                        href={p.bookingReceipt.masterLink}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Отправить мастеру в WhatsApp ↗
                                    </a>
                                    <p className="muted small">
                                        Откроется WhatsApp с готовым сообщением.
                                        Для отправки нажмите кнопку в WhatsApp.
                                    </p>
                                </>
                            ) : (
                                <p className="muted small">
                                    Мастер пока не указал номер WhatsApp. Заявка
                                    уже сохранена в его кабинете.
                                </p>
                            )}
                        </section>
                    )}
                    {!p.master.booking_enabled ? (
                        <p className="empty">
                            Мастер временно отключил онлайн-запись.
                        </p>
                    ) : p.services.length === 0 ? (
                        <p className="empty">Услуги пока не добавлены.</p>
                    ) : (
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                if (
                                    !ready ||
                                    !selected.length ||
                                    !f.data.starts_at
                                )
                                    return;
                                f.post(`/m/${p.master.slug}/book`, {
                                    onSuccess: (page) => {
                                        setChosenDate(
                                            page.props.date as string,
                                        );
                                        f.setData(
                                            "service_ids",
                                            page.props
                                                .selectedServices as number[],
                                        );
                                        f.reset(
                                            "starts_at",
                                            "client_name",
                                            "client_phone",
                                            "client_comment",
                                            "consent",
                                            "whatsapp_consent",
                                        );
                                    },
                                });
                            }}
                        >
                            <Errors errors={f.errors} />
                            <fieldset className="booking-services">
                                <legend className="field-title">Услуги</legend>
                                <p className="muted small">
                                    Можно выбрать несколько услуг за один визит.
                                </p>
                                {p.services.map((s) => (
                                    <label
                                        key={s.id}
                                        className={`booking-service${f.data.service_ids.includes(s.id) ? " selected" : ""}`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={f.data.service_ids.includes(
                                                s.id,
                                            )}
                                            disabled={f.processing}
                                            onChange={(e) =>
                                                change(
                                                    chosenDate,
                                                    e.target.checked
                                                        ? [
                                                              ...f.data
                                                                  .service_ids,
                                                              s.id,
                                                          ]
                                                        : f.data.service_ids.filter(
                                                              (id) =>
                                                                  id !== s.id,
                                                          ),
                                                )
                                            }
                                        />
                                        <span className="booking-service-info">
                                            <strong>{s.name}</strong>
                                            {s.description && (
                                                <span className="muted small">
                                                    {s.description}
                                                </span>
                                            )}
                                            <span className="muted small">
                                                {s.duration_minutes} мин
                                            </span>
                                        </span>
                                        <span className="booking-service-price">
                                            {s.price_som.toLocaleString(
                                                "ru-RU",
                                            )}{" "}
                                            сом
                                        </span>
                                    </label>
                                ))}
                            </fieldset>
                            <div className="booking-total" aria-live="polite">
                                <span>
                                    {selected.length
                                        ? `Выбрано услуг: ${selected.length} · ${duration} мин`
                                        : "Выберите хотя бы одну услугу"}
                                </span>
                                <strong>
                                    {price.toLocaleString("ru-RU")} сом
                                </strong>
                            </div>
                            <p className="field-title">Дата</p>
                            <BookingCalendar
                                value={chosenDate}
                                min={p.minDate}
                                max={p.maxDate}
                                workingDays={p.workingDays}
                                onChange={(date) =>
                                    change(date, f.data.service_ids)
                                }
                            />
                            {loadError && (
                                <p className="errors" role="alert">
                                    {loadError}
                                </p>
                            )}
                            <p className="field-title">Свободное время</p>
                            <div className="slots">
                                {(ready && selected.length ? p.slots : []).map(
                                    (s) => (
                                        <button
                                            type="button"
                                            className={
                                                f.data.starts_at === s.value
                                                    ? "slot selected"
                                                    : "slot"
                                            }
                                            key={s.value}
                                            aria-pressed={
                                                f.data.starts_at === s.value
                                            }
                                            disabled={f.processing}
                                            onClick={() =>
                                                f.setData("starts_at", s.value)
                                            }
                                        >
                                            {s.label}
                                        </button>
                                    ),
                                )}
                            </div>
                            <div aria-live="polite">
                                {loading ? (
                                    <p className="muted small">
                                        Обновляем свободное время…
                                    </p>
                                ) : !selected.length ? (
                                    <p className="muted small">
                                        Сначала выберите услуги.
                                    </p>
                                ) : ready && !p.slots.length ? (
                                    <p className="empty">
                                        На этот день нет свободного времени на
                                        все выбранные услуги. Выберите другую
                                        дату.
                                    </p>
                                ) : null}
                            </div>
                            <div className="form-grid">
                                <Field label="Ваше имя">
                                    <input
                                        required
                                        maxLength={100}
                                        value={f.data.client_name}
                                        onChange={(e) =>
                                            f.setData(
                                                "client_name",
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="name"
                                    />
                                </Field>
                                <Field label="Телефон">
                                    <input
                                        required
                                        type="tel"
                                        maxLength={30}
                                        placeholder="+996"
                                        value={f.data.client_phone}
                                        onChange={(e) =>
                                            f.setData(
                                                "client_phone",
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="tel"
                                    />
                                </Field>
                            </div>
                            <Field label="Комментарий (необязательно)">
                                <textarea
                                    maxLength={1000}
                                    value={f.data.client_comment}
                                    onChange={(e) =>
                                        f.setData(
                                            "client_comment",
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <label className="check">
                                <input
                                    type="checkbox"
                                    required
                                    checked={f.data.consent}
                                    onChange={(e) =>
                                        f.setData("consent", e.target.checked)
                                    }
                                />
                                Я согласен передать имя, телефон и комментарий
                                этому мастеру для оформления записи и связи со
                                мной.
                            </label>
                            {p.whatsappAutomatic && (
                                <label className="check">
                                    <input
                                        type="checkbox"
                                        checked={f.data.whatsapp_consent}
                                        onChange={(e) =>
                                            f.setData(
                                                "whatsapp_consent",
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Получать подтверждение этой записи в
                                    WhatsApp на указанный номер
                                </label>
                            )}
                            <button
                                className="primary wide"
                                disabled={
                                    f.processing ||
                                    !ready ||
                                    !selected.length ||
                                    !f.data.starts_at
                                }
                            >
                                {f.processing ? "Отправляем…" : "Записаться"}
                            </button>
                            <p className="muted small">
                                {p.master.auto_confirm
                                    ? "Запись подтверждается автоматически."
                                    : "Заявка требует подтверждения мастера."}
                            </p>
                        </form>
                    )}
                </section>
            </div>
        </main>
    );
}
