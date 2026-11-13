import LanguageSwitch from "../i18n";
import { useI18n } from "../i18n";
import MasterModeSwitch from "../MasterModeSwitch";
import PhoneInput from "../PhoneInput";
import Icon from "../Icon";
import { Head, router, useForm } from "@inertiajs/react";
import { useRef, useState } from "react";
import ProfileIdentity from "../ProfileIdentity";
import BookingCalendar from "../BookingCalendar";
import { Brand, Errors, Field, Notice } from "../Shared";
import type { Master, Service } from "../types";
type Props = {
    isOwner?: boolean;
    master: Master;
    services: Service[];
    groupSessions: { id: number; name: string; starts_at: string; price_som: number; capacity: number; reserved_seats: number }[];
    selectedServices: number[];
    workingDays: number[];
    date: string;
    minDate: string;
    maxDate: string;
    slots: { value: string; label: string; available: boolean }[];
    whatsappAutomatic: boolean;
    storyTime?: string | null;
    hotOffer: { token: string; special_price_som: number | null; slots: { id: number; starts_at: string }[] } | null;
    bookingReceipt: {
        status: string;
        text: string;
        details?: { id: number; client_name: string; services: string; starts_at: string; ends_at: string; price_som: number };
        masterLink: string | null;
        automaticStatus: string | null;
        manageUrl: string;
    } | null;
};
export default function Booking(p: Props) {
    const { t: tr, locale } = useI18n();
    const f = useForm({
        service_ids: p.selectedServices,
        starts_at: p.storyTime ? (p.slots.find(s => s.available && s.label === p.storyTime)?.value ?? "") : "",
        client_name: "",
        client_phone: "",
        client_comment: "",
        consent: false,
        whatsapp_consent: false,
        offer: p.hotOffer?.token ?? null,
    });
    const [chosenDate, setChosenDate] = useState(p.date);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState("");
    const submitting = useRef(false);
    const selected = p.services.filter((s) =>
        f.data.service_ids.includes(s.id),
    );
    const duration = selected.reduce((sum, s) => sum + s.duration_minutes, 0);
    const basePrice = selected.reduce((sum, s) => sum + s.price_som, 0);
    const price = p.hotOffer?.special_price_som ?? basePrice;
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
            { date, services: ids, selection: 1, offer: p.hotOffer?.token },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onStart: () => setLoading(true),
                onError: () =>
                    setLoadError(
                        tr("Не удалось обновить свободное время. Повторите выбор даты."),
                    ),
                onFinish: () => setLoading(false),
                onCancel: () => setLoading(false),
            },
        );
    };
    return (
        <main className="booking-wrap">
            <Head title={p.master.display_name} />
            <div className="public-page-tools"><Brand /><LanguageSwitch /></div>
            {p.isOwner && <MasterModeSwitch mode="page" publicUrl={`/m/${p.master.slug}`} />}
            <div className="booking-grid">
                <aside className="panel profile-card">
                    <ProfileIdentity master={p.master} />
                    <div className="profile-about">
                        <p>{p.master.bio}</p>
                        {p.master.address && (
                            <div className="profile-address">
                                <Icon name="location" />
                                <div>
                                    <span className="profile-address-label">{tr("Адрес приёма")}</span>
                                    {p.master.address_url ? (
                                        <a href={p.master.address_url} target="_blank" rel="noopener noreferrer">
                                            {p.master.address}
                                            <span className="profile-address-map">{tr("Открыть в 2ГИС ↗")}</span>
                                        </a>
                                    ) : <span>{p.master.address}</span>}
                                </div>
                            </div>
                        )}
                    </div>
                </aside>
                <section className="panel">
                    <p className="eyebrow">{tr("ОНЛАЙН-ЗАПИСЬ")}</p>
                    <h1>{tr("Выберите удобное время")}</h1>
                    <Notice />
                    {p.hotOffer && (
                        <div className="hot-offer-banner">
                            <strong>{tr("Горящее окошко")}</strong>
                            <span>{tr("Выберите одно из предложенных свободных времён.")}</span>
                        </div>
                    )}
                    {p.bookingReceipt && (
                        <section
                            className="booking-receipt"
                            aria-label={tr("Итог записи")}
                        >
                            <h2>
                                {p.bookingReceipt.status === "confirmed"
                                    ? tr("Вы записаны")
                                    : tr("Заявка отправлена мастеру")}
                            </h2>
                            {p.bookingReceipt.manageUrl && (
                                <div>
                                    <a href={p.bookingReceipt.manageUrl} className="ghost" referrerPolicy="no-referrer">
                                        {tr("Отменить или перенести запись →")}{" "}</a>
                                    <p className="muted small">{tr("Сохраните ссылку: она действует 30 дней, но изменения доступны только заранее.")}</p>
                                </div>
                            )}
                            {p.bookingReceipt.details ? <div className="receipt-text">
                                <p>BOSH · №{p.bookingReceipt.details.id}</p>
                                <p>{tr("Мастер:")} {p.master.display_name}</p>
                                <p>{tr("Клиент:")} {p.bookingReceipt.details.client_name}</p>
                                <p>{tr("Услуги:")} {p.bookingReceipt.details.services}</p>
                                <p>{tr("Дата и время:")} {new Intl.DateTimeFormat(locale === "ky" ? "ky-KG" : "ru-RU", {timeZone: p.master.timezone, dateStyle: "medium", timeStyle: "short"}).format(new Date(p.bookingReceipt.details.starts_at))}–{new Intl.DateTimeFormat(locale === "ky" ? "ky-KG" : "ru-RU", {timeZone: p.master.timezone, hour: "2-digit", minute: "2-digit"}).format(new Date(p.bookingReceipt.details.ends_at))} ({p.master.timezone})</p>
                                <p>{tr("Стоимость:")} {Number(p.bookingReceipt.details.price_som).toLocaleString(locale === "ky" ? "ky-KG" : "ru-RU")} {tr("сом")}</p>
                                {p.master.address && <p>{tr("Адрес:")} {p.master.address}</p>}
                                {p.master.phone && <p>{tr("Телефон мастера:")} {p.master.phone}</p>}
                            </div> : <p className="receipt-text">{p.bookingReceipt.text}</p>}
                            {p.bookingReceipt.status !== "confirmed" && (
                                <p className="muted small">
                                    {tr("Запись пока ожидает подтверждения мастера.")}{" "}</p>
                            )}
                            {p.bookingReceipt.automaticStatus === "accepted" ? (
                                <p className="muted small">
                                    {tr("Уведомление принято сервисом WhatsApp. Доставка ещё не подтверждена.")}{" "}</p>
                            ) : (
                                p.bookingReceipt.automaticStatus && (
                                    <p className="muted small">
                                        {tr("Автоматическое сообщение пока не отправлено или его результат неизвестен. При необходимости свяжитесь с мастером.")}{" "}</p>
                                )
                            )}
                            <button type="button" className="button small" onClick={() => router.get(`/m/${p.master.slug}`, {}, { replace: true })}>
                                {tr("Записаться ещё раз")}{" "}</button>
                            {p.bookingReceipt.masterLink ? (
                                <>
                                    <a
                                        className="primary"
                                        href={p.bookingReceipt.masterLink}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        {tr("Отправить мастеру в WhatsApp ↗")}{" "}</a>
                                    <p className="muted small">
                                        {tr("Откроется WhatsApp с готовым сообщением. Для отправки нажмите кнопку в WhatsApp.")}{" "}</p>
                                </>
                            ) : (
                                <p className="muted small">
                                    {tr("Мастер пока не указал номер WhatsApp. Заявка уже сохранена в его кабинете.")}{" "}</p>
                            )}
                        </section>
                    )}
                    {!p.bookingReceipt && p.groupSessions?.length > 0 && !p.master.is_demo && (
                        <section>
                            <h2>{tr("Групповые занятия")}</h2>
                            <p className="muted small">{tr("Выберите занятие и запишитесь на свободное место.")}</p>
                            {p.groupSessions.map(group => (
                                <div key={group.id} className="appointment">
                                    <div>
                                        <strong>{group.name}</strong>
                                        <p>{new Intl.DateTimeFormat((locale === "ky" ? "ky-KG" : "ru-RU"), {
                                            dateStyle: "medium", timeStyle: "short", timeZone: p.master.timezone,
                                        }).format(new Date(group.starts_at))}</p>
                                        <p className="muted small">{tr("Свободно")}{" "}{group.capacity - group.reserved_seats} {tr("из")}{" "}{group.capacity} {tr("мест ·")}{" "}{group.price_som.toLocaleString((locale === "ky" ? "ky-KG" : "ru-RU"))} {tr("сом")}</p>
                                        {group.reserved_seats < group.capacity && (
                                            <form onSubmit={event => {
                                                event.preventDefault();
                                                const fd = new FormData(event.currentTarget);
                                                router.post(`/group-sessions/${group.id}/register`, {
                                                    client_name: String(fd.get("client_name")),
                                                    client_phone: String(fd.get("client_phone")),
                                                    consent: fd.get("consent") === "on",
                                                }, { preserveScroll: true });
                                            }}>
                                                <Field label={tr("Ваше имя")}><input required maxLength={100} name="client_name"/></Field>
                                                <Field label={tr("Телефон")}><input required name="client_phone" type="tel" minLength={7} maxLength={30}/></Field>
                                                <label className="check"><input type="checkbox" name="consent" required/> {tr("Согласен на обработку данных для записи")}</label>
                                                <button className="primary" type="submit">{tr("Записаться на занятие")}</button>
                                            </form>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </section>
                    )}
                    {p.bookingReceipt ? null : p.master.is_demo ? (
                        <div>
                            <p className="notice">{tr("Демонстрационный профиль. Мастер вымышленный, фотографии — иллюстрации. Цены примерные. Запись недоступна.")}</p>
                            {p.services.map(service => (
                                <div className="appointment" key={service.id}>
                                    <div><h3>{service.name}</h3><p className="muted small">{service.description}</p></div>
                                    <div style={{ textAlign: "right", flexShrink: 0 }}>
                                        <strong>{service.price_som.toLocaleString((locale === "ky" ? "ky-KG" : "ru-RU"))} {tr("сом")}</strong>
                                        <p className="booking-service-duration">{service.duration_minutes} {tr("мин")}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : !p.master.booking_enabled ? (
                        <p className="empty">
                            {tr("Мастер временно отключил онлайн-запись.")}{" "}</p>
                    ) : p.services.length === 0 ? (
                        <p className="empty">{tr("Услуги пока не добавлены.")}</p>
                    ) : (
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                if (
                                    submitting.current ||
                                    f.processing ||
                                    loading ||
                                    !!loadError ||
                                    !ready ||
                                    !selected.length ||
                                    !f.data.starts_at
                                )
                                    return;
                                submitting.current = true;
                                f.post(`/m/${p.master.slug}/book`, {
                                    preserveScroll: true,
                                    onFinish: () => { submitting.current = false; },
                                    onError: (errors) => {
                                        if (errors.starts_at) {
                                            f.setData("starts_at", "");
                                            router.reload({
                                                only: ["slots"],
                                            });
                                        }
                                    },
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
                                <legend className="field-title">{tr("Услуги")}</legend>
                                <p className="muted small">
                                    {tr("Можно выбрать несколько услуг за один визит.")}{" "}</p>
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
                                            readOnly={!!p.hotOffer}
                                            onChange={(e) =>
                                                !p.hotOffer &&
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

                                        </span>
                                        <span className="booking-service-price">
                                            {s.price_som.toLocaleString(
                                                (locale === "ky" ? "ky-KG" : "ru-RU"),
                                            )}{" "}
                                            {tr("сом")}{" "}<span className="booking-service-duration">
                                                {s.duration_minutes} {tr("мин")}{" "}</span>
                                        </span>
                                    </label>
                                ))}
                            </fieldset>
                            <div className="booking-total" aria-live="polite">
                                <span>
                                    {selected.length
                                        ? tr("Выбрано услуг: {0} · {1} мин", [selected.length, duration])
                                        : tr("Выберите хотя бы одну услугу")}
                                </span>
                                <strong>
                                    {price.toLocaleString((locale === "ky" ? "ky-KG" : "ru-RU"))} {tr("сом")}{" "}{p.hotOffer?.special_price_som != null && p.hotOffer.special_price_som !== basePrice && (
                                        <del className="muted small"> {basePrice.toLocaleString((locale === "ky" ? "ky-KG" : "ru-RU"))} {tr("сом")}</del>
                                    )}
                                </strong>
                            </div>
                            <p className="field-title">{tr("Дата")}</p>
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
                                <div className="booking-retry" role="alert">
                                    <p className="errors">{tr(loadError)}</p>
                                    <button type="button" className="ghost" disabled={loading || f.processing}
                                        onClick={() => change(chosenDate, f.data.service_ids)}>{tr("Повторить загрузку")}</button>
                                </div>
                            )}
                            <p className="field-title">{tr("Свободное время")}</p>
                            <div className="slots">
                                {(ready && selected.length ? p.slots : []).map(
                                    (s) => (
                                        <button
                                            type="button"
                                            className={s.available ? (f.data.starts_at === s.value ? "slot selected" : "slot") : "slot slot-unavailable"}
                                            key={s.value}
                                            aria-pressed={
                                                f.data.starts_at === s.value
                                            }
                                            disabled={f.processing || !s.available}
                                            aria-label={s.available ? s.label : tr("{0}, занято", [s.label])}
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
                                    <p className="muted small" role="status">
                                        {tr("Обновляем свободное время… Пожалуйста, дождитесь результата.")}{" "}</p>
                                ) : loadError ? null : !selected.length ? (
                                    <p className="muted small">
                                        {tr("Сначала выберите услуги.")}{" "}</p>
                                ) : ready && !p.slots.some(s => s.available) ? (
                                    <p className="empty">
                                        {tr("На этот день нет свободного времени на все выбранные услуги. Выберите другую дату.")}{" "}</p>
                                ) : null}
                            </div>
                            <div className="form-grid">
                                <Field label={tr("Ваше имя")}>
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
                                <Field label={tr("Телефон")}>
                                    <PhoneInput required value={f.data.client_phone} onChange={value => f.setData("client_phone", value)} label={tr("Телефон клиента")} />
                                </Field>
                            </div>
                            <Field label={tr("Комментарий (необязательно)")}>
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
                                {tr("Я согласен передать имя, телефон и комментарий этому мастеру для оформления записи и связи со мной.")}{" "}</label>
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
                                    {tr("Получать подтверждение этой записи в WhatsApp на указанный номер")}{" "}</label>
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
                                {f.processing ? tr("Отправляем…") : tr("Записаться")}
                            </button>
                            <p className="muted small">
                                {p.master.auto_confirm
                                    ? tr("Запись подтверждается автоматически.")
                                    : tr("Заявка требует подтверждения мастера.")}
                            </p>
                        </form>
                    )}
                </section>
            </div>
        </main>
    );
}
