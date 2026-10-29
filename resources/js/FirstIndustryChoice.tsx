import { useForm, usePage } from "@inertiajs/react";
import type { Industries, Master } from "./types";

export default function FirstIndustryChoice({ master }: { master: Master }) {
    const { industries } = usePage<{ industries: Industries }>().props;
    const f = useForm({ industry: master.industry || "", activity: master.activity || "" });
    if (master.industry && master.activity) return null;
    const activities = industries[f.data.industry]?.activities ?? {};
    return <div className="industry-onboarding-backdrop">
        <section className="industry-onboarding" role="dialog" aria-modal="true" aria-labelledby="industry-title">
            <p className="eyebrow">BOSH · НАЧАЛО РАБОТЫ</p>
            <h2 id="industry-title">Чем вы занимаетесь?</h2>
            <p className="muted small">Выберите сферу и направление — настроим кабинет под ваше дело. Остальное заполните позже.</p>
            <form onSubmit={e => { e.preventDefault(); f.post("/onboarding/industry", { preserveScroll: true }); }}>
                <label className="field"><span>Сфера деятельности</span>
                    <select required value={f.data.industry} onChange={e => f.setData({ industry: e.target.value, activity: "" })}>
                        <option value="">Выберите сферу</option>
                        {Object.entries(industries).map(([id, item]) => <option key={id} value={id}>{item.name}</option>)}
                    </select>
                </label>
                <label className="field"><span>Направление</span>
                    <select required disabled={!f.data.industry} value={f.data.activity} onChange={e => f.setData("activity", e.target.value)}>
                        <option value="">Выберите направление</option>
                        {Object.entries(activities).map(([id, title]) => <option key={id} value={id}>{title}</option>)}
                    </select>
                </label>
                {Object.keys(f.errors).length > 0 && <p className="errors" role="alert">{Object.values(f.errors).join(" ")}</p>}
                <button className="primary wide" disabled={f.processing || !f.data.industry || !f.data.activity}>{f.processing ? "Сохраняем…" : "Продолжить"}</button>
            </form>
        </section>
    </div>;
}
