import { Head, router, useForm } from "@inertiajs/react";
import { Brand, Errors, Field, Notice } from "../Shared";
import type { Master, Service } from "../types";
type Props = {
    master: Master;
    services: Service[];
    selectedService: number | null;
    date: string;
    minDate: string;
    maxDate: string;
    slots: { value: string; label: string }[];
};
export default function Booking(p: Props) {
    const f = useForm({
        service_id: p.selectedService ?? "",
        starts_at: "",
        client_name: "",
        client_phone: "",
        client_comment: "",
        consent: false,
    });
    const service = p.services.find((s) => s.id === p.selectedService);
    const change = (date: string, id: number) => {
        f.setData((d) => ({ ...d, starts_at: "", service_id: id }));
        router.get(
            `/m/${p.master.slug}`,
            { date, service: id },
            { preserveScroll: true, preserveState: true },
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
                                f.post(`/m/${p.master.slug}/book`, {
                                    onSuccess: () =>
                                        f.reset(
                                            "starts_at",
                                            "client_name",
                                            "client_phone",
                                            "client_comment",
                                            "consent",
                                        ),
                                });
                            }}
                        >
                            <Errors errors={f.errors} />
                            <Field label="Услуга">
                                <select
                                    value={p.selectedService ?? ""}
                                    onChange={(e) =>
                                        change(p.date, Number(e.target.value))
                                    }
                                >
                                    {p.services.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name} · {s.duration_minutes} мин
                                            · {s.price_som.toLocaleString()} сом
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            {service?.description && (
                                <p className="muted">{service.description}</p>
                            )}
                            <Field label="Дата">
                                <input
                                    type="date"
                                    required
                                    min={p.minDate}
                                    max={p.maxDate}
                                    value={p.date}
                                    onChange={(e) => {
                                        if (e.target.value)
                                            change(
                                                e.target.value,
                                                p.selectedService!,
                                            );
                                    }}
                                />
                            </Field>
                            <p className="field-title">Свободное время</p>
                            <div className="slots">
                                {p.slots.map((s) => (
                                    <button
                                        type="button"
                                        className={
                                            f.data.starts_at === s.value
                                                ? "slot selected"
                                                : "slot"
                                        }
                                        key={s.value}
                                        onClick={() =>
                                            f.setData("starts_at", s.value)
                                        }
                                    >
                                        {s.label}
                                    </button>
                                ))}
                            </div>
                            {!p.slots.length && (
                                <p className="empty">
                                    На этот день свободного времени нет.
                                    Выберите другую дату.
                                </p>
                            )}
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
                            <button
                                className="primary wide"
                                disabled={f.processing || !f.data.starts_at}
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
