import { useEffect, useRef } from "react";
import { useForm } from "@inertiajs/react";
import CloseButton from "./CloseButton";
import { Errors, Field } from "./Shared";
import { useI18n } from "./i18n";
import { localizedDateTimeFormat } from "./localized-date";
import { addDays, localDay } from "./calendar";
import type { TimeBlock } from "./AppointmentCalendar";

export type ClosedRange = {date: string; start: string; end: string};
export default function TimeBlockEditor({range, block, timezone, onClose}: {
    range?: ClosedRange; block?: TimeBlock; timezone: string; onClose: () => void;
}) {
    const { t: tr, locale } = useI18n();
    const dialog = useRef<HTMLDialogElement>(null);
    const submitting = useRef(false);
    const day = range?.date ?? localDay(new Date(), timezone);
    const f = useForm({starts_at_local: `${day}T${range?.start ?? "09:00"}`,
        ends_at_local: range?.end === "24:00" ? `${addDays(day, 1)}T00:00` : `${day}T${range?.end ?? "09:30"}`, reason: ""});
    useEffect(() => { dialog.current?.showModal(); }, []);
    const format = (value: string) => localizedDateTimeFormat(locale, {timeZone: timezone, day:"numeric", month:"long", hour:"2-digit", minute:"2-digit"}).format(new Date(value));
    return <dialog ref={dialog} className="service-dialog time-block-dialog" aria-labelledby="time-block-title"
        onCancel={e => {e.preventDefault(); if (!f.processing) onClose();}}>
        <div className="service-dialog-header"><h2 id="time-block-title">{block ? tr("Время закрыто") : tr("Закрыть время")}</h2>
            <CloseButton onClick={onClose} disabled={f.processing} /></div>
        <form className="time-block-form" onSubmit={e => {
            e.preventDefault(); if (submitting.current || f.processing) return;
            submitting.current = true;
            f.post(block ? `/time-blocks/${block.id}/remove` : "/time-blocks", {
                preserveScroll:true, onSuccess:onClose, onFinish:() => {submitting.current = false;},
            });
        }}>
            <Errors errors={f.errors} />
            {block ? <>
                <p className="time-block-period">{format(block.starts_at)} — {format(block.ends_at)}</p>
                {block.reason && <p>{block.reason}</p>}
                <p className="muted small">{tr("Нажмите «Открыть время», чтобы клиенты снова могли записываться в этот интервал с учётом рабочего расписания.")}</p>
            </> : <>
                <p className="muted small">{tr("Этот интервал исчезнет из доступного времени у клиентов. Причину видите только вы.")}</p>
                <Field label={tr("Начало")}><input type="datetime-local" required value={f.data.starts_at_local}
                    min={`${localDay(new Date(), timezone)}T00:00`} onChange={e => {
                        const value = e.target.value;
                        const shifted = new Date(new Date(value + "Z").getTime() + 30 * 60000);
                        f.setData(data => ({...data, starts_at_local:value,
                            ends_at_local:value && data.ends_at_local <= value && Number.isFinite(shifted.getTime()) ? shifted.toISOString().slice(0,16) : data.ends_at_local}));
                    }} /></Field>
                <Field label={tr("Конец")}><input type="datetime-local" required value={f.data.ends_at_local}
                    min={f.data.starts_at_local} onChange={e => f.setData("ends_at_local", e.target.value)} /></Field>
                <Field label={tr("Причина (необязательно)")}><input maxLength={255} value={f.data.reason}
                    placeholder={tr("Например, личные дела или отпуск")} onChange={e => f.setData("reason", e.target.value)} /></Field>
                <p className="muted small">{tr("Время указано в часовом поясе мастера.")} {timezone}</p>
            </>}
            <div className="row wrap time-block-actions">
                <button type="button" className="ghost" disabled={f.processing} onClick={onClose}>{tr("Отмена")}</button>
                <button type="submit" className="primary" disabled={f.processing}>{f.processing ? tr("Сохраняем…") : block ? tr("Открыть время") : tr("Закрыть для записи")}</button>
            </div>
        </form>
    </dialog>;
}
