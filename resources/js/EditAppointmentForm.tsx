import { timezoneLabel } from "./timezone";
import PhoneInput from "./PhoneInput";
import { useForm } from "@inertiajs/react";
import { Errors, Field } from "./Shared";
import type { Appointment, Service } from "./types";
import { localDay } from "./calendar";

function localInput(value: string, timezone: string) {
    const clock = new Intl.DateTimeFormat("en-GB", {
        timeZone: timezone,
        hour: "2-digit",
        minute: "2-digit",
        hourCycle: "h23",
    }).format(new Date(value));
    return `${localDay(value, timezone)}T${clock}`;
}
export default function EditAppointmentForm({
    appointment: a,
    services,
    timezone,
    onClose,
}: {
    appointment: Appointment;
    services: Service[];
    timezone: string;
    onClose: () => void;
}) {
    const old = a.services_snapshot?.length
        ? a.services_snapshot
        : a.master_service_id
          ? [
                {
                    id: a.master_service_id,
                    name: a.service_name_snapshot,
                    duration_minutes: Math.round(
                        (Date.parse(a.ends_at) - Date.parse(a.starts_at)) /
                            60000,
                    ),
                    price_som: a.price_som,
                },
            ]
          : [];
    const oldIds = old.map((s) => s.id);
    const options = [
        ...old,
        ...services.filter((s) => s.is_active && !oldIds.includes(s.id)),
    ];
    const f = useForm({
        revision: a.revision,
        starts_at_local: localInput(a.starts_at, timezone),
        duration_minutes: Math.round(
            (Date.parse(a.ends_at) - Date.parse(a.starts_at)) / 60000,
        ),
        price_som: a.price_som,
        service_ids: oldIds,
        client_name: a.client_name,
        client_phone: a.client_phone,
        client_comment: a.client_comment ?? "",
        reason: "",
    });
    const end = (() => {
        const date = new Date(`${f.data.starts_at_local}Z`);
        if (
            !Number.isFinite(date.getTime()) ||
            !Number.isFinite(Number(f.data.duration_minutes))
        )
            return "";
        date.setUTCMinutes(
            date.getUTCMinutes() + Number(f.data.duration_minutes),
        );
        return date.toLocaleString("ru-RU", {
            timeZone: "UTC",
            day: "numeric",
            month: "short",
            hour: "2-digit",
            minute: "2-digit",
        });
    })();
    function toggle(id: number, checked: boolean) {
        const ids = checked
            ? [...f.data.service_ids, id]
            : f.data.service_ids.filter((value) => value !== id);
        const selected = options.filter((s) => ids.includes(s.id));
        f.setData((d) => ({
            ...d,
            service_ids: ids,
            duration_minutes: selected.reduce(
                (sum, s) => sum + s.duration_minutes,
                0,
            ),
            price_som: selected.reduce((sum, s) => sum + s.price_som, 0),
        }));
    }
    return (
        <form
            className="appointment-edit-form"
            onSubmit={(e) => {
                e.preventDefault();
                f.post(`/appointments/${a.id}/edit`, {
                    preserveScroll: true,
                    onSuccess: onClose,
                });
            }}
        >
            <h3>Перенос и редактирование</h3>
            <p className="muted small">
                Время мастера: {timezoneLabel(timezone)}. Изменения сохраняются в истории.
            </p>
            <Errors errors={f.errors} />
            <div className="form-grid">
                <Field label="Дата и время начала">
                    <input
                        type="datetime-local"
                        required
                        value={f.data.starts_at_local}
                        onChange={(e) =>
                            f.setData("starts_at_local", e.target.value)
                        }
                    />
                </Field>
                <Field label="Длительность, минут">
                    <input
                        type="number"
                        required
                        min={5}
                        max={1440}
                        step={1}
                        value={f.data.duration_minutes}
                        onChange={(e) =>
                            f.setData(
                                "duration_minutes",
                                Number(e.target.value),
                            )
                        }
                    />
                    <span className="muted small">
                        Окончание: {end || "укажите начало и длительность"}
                    </span>
                </Field>
            </div>
            <fieldset className="booking-services">
                <legend className="field-title">Услуги</legend>
                {options.map((s) => (
                    <label key={s.id} className="check">
                        <input
                            type="checkbox"
                            checked={f.data.service_ids.includes(s.id)}
                            onChange={(e) => toggle(s.id, e.target.checked)}
                        />
                        {s.name} · {s.duration_minutes} мин ·{" "}
                        {s.price_som.toLocaleString("ru-RU")} сом
                    </label>
                ))}
            </fieldset>
            <p className="muted small">
                При смене услуг длительность и сумма пересчитываются. Итоговые
                значения можно скорректировать вручную.
            </p>
            <div className="form-grid">
                <Field label="Итоговая стоимость, сом">
                    <input
                        type="number"
                        required
                        min={0}
                        max={200000000}
                        step={1}
                        value={f.data.price_som}
                        onChange={(e) =>
                            f.setData("price_som", Number(e.target.value))
                        }
                    />
                </Field>
                <Field label="Имя клиента">
                    <input
                        required
                        maxLength={100}
                        value={f.data.client_name}
                        onChange={(e) =>
                            f.setData("client_name", e.target.value)
                        }
                    />
                </Field>
                <Field label="Телефон клиента">
                    <PhoneInput required value={f.data.client_phone} onChange={value => f.setData("client_phone", value)} label="Телефон клиента" />
                </Field>
            </div>
            <Field label="Комментарий к записи">
                <textarea
                    maxLength={1000}
                    value={f.data.client_comment}
                    onChange={(e) =>
                        f.setData("client_comment", e.target.value)
                    }
                />
            </Field>
            <Field label="Причина изменения (необязательно)">
                <input
                    maxLength={500}
                    placeholder="Например: клиент попросил перенести визит"
                    value={f.data.reason}
                    onChange={(e) => f.setData("reason", e.target.value)}
                />
            </Field>
            <p className="muted small">
                После сохранения можно отправить клиенту актуальные данные через
                WhatsApp. Если меняется номер клиента, прежнее согласие на
                автоматические сообщения сбрасывается.
            </p>
            <div className="row wrap">
                <button
                    className="primary"
                    disabled={f.processing || !f.data.service_ids.length}
                >
                    {f.processing ? "Сохраняем…" : "Сохранить изменения"}
                </button>
                <button
                    type="button"
                    className="ghost"
                    disabled={f.processing}
                    onClick={onClose}
                >
                    Без изменений
                </button>
            </div>
        </form>
    );
}
