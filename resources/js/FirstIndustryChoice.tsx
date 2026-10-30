import { useForm, usePage } from "@inertiajs/react";
import { Notice } from "./Shared";
import type { Industries, Master } from "./types";

export default function FirstIndustryChoice({ master }: { master: Master }) {
    const { industries } = usePage<{ industries?: Industries }>().props;
    const catalog = industries ?? {};
    const industryValid = !!master.industry && !!catalog[master.industry];
    const activityValid = industryValid && !!master.activity && !!catalog[master.industry!].activities?.[master.activity];
    const f = useForm({
        industry: industryValid ? master.industry! : "",
        activity: activityValid ? master.activity! : "",
    });
    const activities = catalog[f.data.industry]?.activities ?? {};
    const needsChoice = !industryValid || !activityValid;
    if (!needsChoice) return null;

    return <div className="industry-onboarding-backdrop">
        <section className="industry-onboarding" role="dialog" aria-modal="true" aria-labelledby="industry-title">
            <p className="eyebrow">BOSH · НАЧАЛО РАБОТЫ</p>
            <h2 id="industry-title">Чем вы занимаетесь?</h2>
            <p className="muted small">Всего два выбора — и можно работать. Направление можно изменить позже в профиле.</p>
            <Notice />
            <form onSubmit={e => {
                e.preventDefault();
                if (!catalog[f.data.industry]?.activities?.[f.data.activity]) return;
                f.post("/onboarding/industry", { preserveScroll: true });
            }}>
                <label className="field"><span>Сфера деятельности</span>
                    <select required value={f.data.industry} onChange={e => {
                        f.clearErrors();
                        f.setData({ industry: e.target.value, activity: "" });
                    }}>
                        <option value="">Выберите сферу</option>
                        {Object.entries(catalog).map(([id, item]) => <option key={id} value={id}>{item.name}</option>)}
                    </select>
                </label>
                <label className="field"><span>Направление</span>
                    <select required disabled={!f.data.industry || !catalog[f.data.industry]} value={f.data.activity} onChange={e => f.setData("activity", e.target.value)}>
                        <option value="">Выберите направление</option>
                        {Object.entries(activities).map(([id, title]) => <option key={id} value={id}>{title}</option>)}
                    </select>
                </label>
                {Object.keys(f.errors).length > 0 && <p className="errors" role="alert">{Object.values(f.errors).join(" ")}</p>}
                <button className="primary wide" type="submit" disabled={f.processing || !catalog[f.data.industry]?.activities?.[f.data.activity]}>
                    {f.processing ? "Сохраняем…" : "Продолжить"}
                </button>
            </form>
        </section>
    </div>;
}
