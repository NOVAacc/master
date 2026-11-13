import LanguageSwitch from "./i18n";
import { useI18n } from "./i18n";
import { useForm, usePage, useRemember } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { Notice } from "./Shared";
import ShareProfile from "./ShareProfile";
import type { Industries, Master } from "./types";

type Counts = { hoursCount: number; servicesCount: number; restartStep?: number | null };
const weekdays = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];
const starters: Record<string, string[]> = {
    nails: ["Маникюр", "Маникюр с покрытием"], barber: ["Мужская стрижка", "Оформление бороды"],
    massage: ["Массаж 60 минут"], "brows-lashes": ["Оформление бровей", "Окрашивание бровей"],
    cosmetology: ["Консультация", "Уход за лицом"], tutoring: ["Индивидуальное занятие"],
};
export default function FirstIndustryChoice({ master, publicUrl, completed }: { master: Master; publicUrl: string; completed: boolean }) {
    const { t: tr, locale } = useI18n();
    const { industries, onboarding } = usePage<{ industries?: Industries; onboarding?: Counts }>().props;
    const catalog = industries ?? {};
    const counts = onboarding ?? { hoursCount: 0, servicesCount: 0 };
    const validCategory = !!master.industry && !!catalog[master.industry]?.activities?.[master.activity ?? ""];
    const needCategory = !validCategory;
    const needSchedule = counts.hoursCount === 0;
    const needServices = counts.servicesCount === 0;
    const firstStep = counts.restartStep != null ? counts.restartStep : needCategory ? 0 : needSchedule ? 1 : needServices ? 2 : 3;
    const [days, setDays] = useRemember<number[]>([1, 2, 3, 4, 5, 6], `bosh-onboarding-${master.id}-days`);
    const [start, setStart] = useRemember("09:00", `bosh-onboarding-${master.id}-start`);
    const [end, setEnd] = useRemember("18:00", `bosh-onboarding-${master.id}-end`);
    const category = useForm(`bosh-onboarding-${master.id}-category`, { industry: master.industry || "", activity: master.activity || "" });
    const schedule = useForm<{ hours: { weekday: number; starts_at: string; ends_at: string; break_starts_at: null; break_ends_at: null }[] }>({ hours: [] });
    const service = useForm(`bosh-onboarding-${master.id}-service`, { services: [{ name: "", duration_minutes: 60, price_som: 0 }] });
    const location = useForm({ address: master.address || "", address_url: master.address_url || "" });
    const [locationSaved, setLocationSaved] = useState(false);
    const [drafted, setDrafted] = useState(false);
    const [done, setDone] = useState(false);
    const [step, setStep] = useState(firstStep);
    useEffect(() => { setStep(firstStep); }, [firstStep]);
    if (done || (step === 3 && !completed)) return null;
    const active = catalog[category.data.industry]?.activities ?? {};
    const suggested = starters[master.activity ?? ""] ?? [tr("Первая услуга")];
    const submitSchedule = () => {
        if (!days.length || start >= end) return;
        schedule.setData("hours", days.map(weekday => ({
            weekday, starts_at: start, ends_at: end, break_starts_at: null, break_ends_at: null,
        })));
        // Submit the current values directly so the form does not send stale state.
        schedule.transform(() => ({ hours: days.map(weekday => ({
            weekday, starts_at: start, ends_at: end, break_starts_at: null, break_ends_at: null,
        })) }));
        schedule.post("/schedule", { preserveScroll: true, preserveState: true, onSuccess: () => setStep(2) });
    };
    const submitServices = () => {
        if (!service.data.services.every(s => s.name.trim() && s.duration_minutes >= 5 && s.price_som >= 0)) return;
        service.post("/onboarding/services", { preserveScroll: true, preserveState: true, onSuccess: () => setStep(3) });
    };
    return <div className="industry-onboarding-backdrop">
        <section className="industry-onboarding" role="dialog" aria-modal="true" aria-labelledby="industry-title">
            <LanguageSwitch /><p className="eyebrow">{tr("BOSH · БЫСТРЫЙ СТАРТ")}</p>
            {step < 3 && <p className="muted small">{tr("Шаг")}{" "}{step + 1} {tr("из 3")}</p>}
            {step < 3 && <div className="onboarding-progress">{[0, 1, 2].map(i => <span key={i} className={i <= step ? "active" : ""} />)}</div>}
            <Notice />
            {step === 3 && <section className="onboarding-finish">
                <h2 id="industry-title">{tr("Ваша страница готова!")}</h2>
                <p className="muted small">{tr("Всё готово. Отправьте клиентам ссылку — они смогут выбрать услугу и время самостоятельно.")}</p>
                <ShareProfile url={publicUrl} />
                <details className="onboarding-optional-details">
                    <summary>{tr("Дополнительно: адрес приёма и 2ГИС")}</summary>
                    <p className="muted small">{tr("Добавьте место приёма сейчас или позже в разделе «Моя страница».")}</p>
                    <form onSubmit={e => { e.preventDefault(); location.post("/onboarding/location", {
                    preserveScroll: true, preserveState: true, onSuccess: () => setLocationSaved(true),
                }); }}>
                    <label className="field"><span>{tr("Адрес приёма (необязательно)")}</span>
                        <input maxLength={255} value={location.data.address} onChange={e => { location.setData("address", e.target.value); setLocationSaved(false); }} placeholder={tr("Например, Бишкек, ул. Киевская, 100")} />
                    </label>
                    <label className="field"><span>{tr("Ссылка на 2GIS (необязательно)")}</span>
                        <input type="url" maxLength={2048} value={location.data.address_url} onChange={e => { location.setData("address_url", e.target.value); setLocationSaved(false); }} placeholder="https://2gis.kg/..." />
                    </label>
                    {Object.values(location.errors).map((error, i) => <p className="errors" key={i}>{tr(error)}</p>)}
                    {locationSaved && <p className="muted small" role="status">{tr("Адрес сохранён.")}</p>}
                    <button type="submit" className="button wide" disabled={location.processing}>{location.processing ? tr("Сохранение…") : tr("Сохранить адрес")}</button>
                    </form>
                </details>
                <div className="onboarding-finish-footer"><button className="primary wide" type="button" onClick={() => setDone(true)}>{tr("Перейти в кабинет")}</button></div>
            </section>}
            {step === 0 && <>
                <h2 id="industry-title">{tr("Чем вы занимаетесь?")}</h2>
                <p className="muted small">{tr("Выберите сферу и направление. Их можно изменить позже.")}</p>
                <form onSubmit={e => { e.preventDefault(); category.post("/onboarding/industry", { preserveScroll: true, preserveState: true, onSuccess: () => setStep(1) }); }}>
                    <label className="field"><span>{tr("Сфера деятельности")}</span>
                        <select required value={category.data.industry} onChange={e => category.setData({ industry: e.target.value, activity: "" })}>
                            <option value="">{tr("Выберите сферу")}</option>
                            {Object.entries(catalog).map(([id, item]) => <option key={id} value={id}>{tr(item.name)}</option>)}
                        </select>
                    </label>
                    <label className="field"><span>{tr("Направление")}</span>
                        <select required disabled={!category.data.industry} value={category.data.activity} onChange={e => category.setData("activity", e.target.value)}>
                            <option value="">{tr("Выберите направление")}</option>
                            {Object.entries(active).map(([id, title]) => <option key={id} value={id}>{tr(title)}</option>)}
                        </select>
                    </label>
                    {Object.values(category.errors).map((error, i) => <p className="errors" key={i}>{tr(error)}</p>)}
                    <button type="submit" className="primary wide" disabled={category.processing || !active[category.data.activity]}>{tr("Продолжить")}</button>
                </form>
            </>}
            {step === 1 && <>
                <h2 id="industry-title">{tr("Когда вы работаете?")}</h2>
                <p className="muted small">{tr("Выберите дни и обычные рабочие часы. Индивидуальное расписание и обеденный перерыв можно настроить позже в настройках расписания.")}</p>
                <div className="onboarding-weekdays">{weekdays.map((name, i) => <button type="button" key={name} aria-pressed={days.includes(i+1)}
                    className={days.includes(i+1) ? "selected" : ""} onClick={() => setDays(old => old.includes(i+1) ? old.filter(d => d !== i+1) : [...old, i+1].sort())}>{tr(name)}</button>)}</div>
                <div className="onboarding-time">
                    <label className="field"><span>{tr("С")}</span><input type="time" value={start} onChange={e => setStart(e.target.value)} /></label>
                    <label className="field"><span>{tr("До")}</span><input type="time" value={end} onChange={e => setEnd(e.target.value)} /></label>
                </div>
                {Object.values(schedule.errors).map((error, i) => <p className="errors" key={i}>{tr(error)}</p>)}
                <button type="button" className="primary wide" disabled={schedule.processing || !days.length || start >= end} onClick={submitSchedule}>{tr("Сохранить расписание")}</button>
            </>}
            {step === 2 && <>
                <h2 id="industry-title">{tr("Ваши первые услуги")}</h2>
                <p className="muted small">{tr("Добавьте хотя бы одну услугу. Цены и продолжительность можно изменить позже.")}</p>
                {!drafted && <div className="onboarding-suggestions">
                    {suggested.map(name => <button type="button" key={name} className="button small" onClick={() => {
                        service.setData("services", [{ name: tr(name), duration_minutes: 60, price_som: 0 }]); setDrafted(true);
                    }}>{tr(name)}</button>)}
                </div>}
                {service.data.services.map((item, i) => <div className="onboarding-service" key={i}>
                    <label className="field"><span>{tr("Название")}</span><input required maxLength={150} placeholder={tr("Название услуги")} value={item.name} onChange={e => service.setData("services", service.data.services.map((s,j) => i===j ? {...s,name:e.target.value} : s))} /></label>
                    <div className="onboarding-time">
                        <label className="field"><span>{tr("Цена, сом")}</span><input type="number" min="0" max="10000000" value={item.price_som} onChange={e => service.setData("services", service.data.services.map((s,j) => i===j ? {...s,price_som:Number(e.target.value)} : s))}/></label>
                        <label className="field"><span>{tr("Минут")}</span><select value={item.duration_minutes} onChange={e => service.setData("services", service.data.services.map((s,j) => i===j ? {...s,duration_minutes:Number(e.target.value)} : s))}>{[15,30,45,60,90,120].map(n => <option value={n} key={n}>{n}</option>)}</select></label>
                    </div>
                </div>)}
                {service.data.services.length < 3 && <button type="button" className="button small" onClick={() => service.setData("services",[...service.data.services,{name:"",duration_minutes:60,price_som:0}])}>{tr("+ Ещё услуга")}</button>}
                {Object.values(service.errors).map((error,i) => <p className="errors" key={i}>{tr(error)}</p>)}
                <button type="button" className="primary wide" disabled={service.processing || service.data.services.some(x=>!x.name.trim())} onClick={submitServices}>{tr("Завершить настройку")}</button>
            </>}
        </section>
    </div>;
}
