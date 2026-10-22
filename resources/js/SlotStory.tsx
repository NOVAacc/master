import axios from "axios";
import { useEffect, useRef, useState } from "react";
import CloseButton from "./CloseButton";
import type { Master, Service } from "./types";

type StyleName = "minimal" | "graphite" | "soft" | "accent" | "premium";
type Values = { bg: string; text: string; subtle: string; button: string; buttonText: string };
const styles: Record<StyleName, { label: string; colors: Values }> = {
    minimal: { label: "Минимализм", colors: { bg: "#F5F3EF", text: "#191919", subtle: "#6B6965", button: "#191919", buttonText: "#FFFFFF" } },
    graphite: { label: "Графит", colors: { bg: "#202127", text: "#FFFFFF", subtle: "#B8BBC4", button: "#FFFFFF", buttonText: "#202127" } },
    soft: { label: "Нежный", colors: { bg: "#F6E2E8", text: "#632F46", subtle: "#956479", button: "#632F46", buttonText: "#FFFFFF" } },
    accent: { label: "Акцент", colors: { bg: "#D92E42", text: "#FFFFFF", subtle: "#FFE0E6", button: "#FFFFFF", buttonText: "#B52B3C" } },
    premium: { label: "Премиум", colors: { bg: "#25362F", text: "#F6EBD7", subtle: "#C8BB9E", button: "#D9BE89", buttonText: "#25362F" } },
};

function lines(ctx: CanvasRenderingContext2D, text: string, maxWidth: number, font: string): string[] {
    ctx.font = font;
    const result: string[] = [];
    let row = "";
    for (const word of text.split(/\s+/).filter(Boolean)) {
        const proposed = row ? row + " " + word : word;
        if (ctx.measureText(proposed).width > maxWidth && row) {
            result.push(row);
            row = word;
        } else row = proposed;
    }
    if (row) result.push(row);
    return result.slice(0, 4);
}

function paint(canvas: HTMLCanvasElement, info: { master: string; service: string; date: string; time: string; price: number; slug: string; style: StyleName }) {
    canvas.width = 1080;
    canvas.height = 1920;
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Не удалось создать изображение.");
    const c = styles[info.style].colors;
    ctx.fillStyle = c.bg;
    ctx.fillRect(0, 0, 1080, 1920);
    ctx.fillStyle = c.text;
    ctx.textBaseline = "top";
    ctx.font = "bold 58px system-ui, sans-serif";
    ctx.fillText("BOSH.", 88, 114);
    ctx.font = "42px system-ui, sans-serif";
    const shortName = lines(ctx, info.master, 700, ctx.font)[0] || "";
    ctx.fillText(shortName, 88, 218);
    ctx.fillStyle = c.button;
    ctx.fillRect(88, 558, 100, 9);
    ctx.fillStyle = c.text;
    ctx.font = "bold 112px system-ui, sans-serif";
    lines(ctx, "Освободилось окно", 900, ctx.font).forEach((line, i) => ctx.fillText(line, 88, 622 + i * 132));
    ctx.font = "55px system-ui, sans-serif";
    ctx.fillStyle = c.subtle;
    lines(ctx, info.service, 900, ctx.font).forEach((line, i) => ctx.fillText(line, 88, 970 + i * 72));
    ctx.fillStyle = c.text;
    ctx.font = "bold 202px system-ui, sans-serif";
    ctx.fillText(info.time, 76, 1180);
    ctx.font = "48px system-ui, sans-serif";
    ctx.fillText(new Date(info.date + "T12:00:00Z").toLocaleDateString("ru-RU", { timeZone: "UTC", day: "numeric", month: "long" }), 88, 1420);
    ctx.font = "bold 54px system-ui, sans-serif";
    ctx.fillText(info.price.toLocaleString("ru-RU") + " сом", 88, 1500);
    ctx.fillStyle = c.button;
    ctx.beginPath();
    ctx.roundRect(88, 1660, 904, 115, 58);
    ctx.fill();
    ctx.textAlign = "center";
    ctx.fillStyle = c.buttonText;
    ctx.font = "bold 48px system-ui, sans-serif";
    ctx.fillText("Записаться", 540, 1688);
    ctx.textAlign = "left";
    ctx.fillStyle = c.subtle;
    ctx.font = "36px system-ui, sans-serif";
    ctx.fillText(("bosh.kg/m/" + info.slug).slice(0, 45), 88, 1820);
}

function localParts(value: string, timezone: string) {
    const formatter = new Intl.DateTimeFormat("en-CA", {
        timeZone: timezone, year: "numeric", month: "2-digit", day: "2-digit",
        hour: "2-digit", minute: "2-digit", hourCycle: "h23",
    });
    const parts = Object.fromEntries(formatter.formatToParts(new Date(value)).map(part => [part.type, part.value]));
    return { date: parts.year + "-" + parts.month + "-" + parts.day, time: parts.hour + ":" + parts.minute };
}

export default function SlotStory({
    master, services, initialDate, initialAppointment, onClose,
}: {
    master: Master;
    services: Service[];
    initialDate: string;
    initialAppointment?: { starts_at: string; master_service_id?: number | null; service_name_snapshot: string };
    onClose: () => void;
}) {
    const active = services.filter(s => s.is_active);
    const previous = initialAppointment ? localParts(initialAppointment.starts_at, master.timezone) : null;
    const [serviceId, setServiceId] = useState(initialAppointment?.master_service_id && active.some(s => s.id === initialAppointment.master_service_id)
        ? initialAppointment.master_service_id : (active[0]?.id ?? 0));
    const [date, setDate] = useState(previous?.date ?? initialDate);
    const [time, setTime] = useState(previous?.time ?? "15:00");
    const [style, setStyle] = useState<StyleName>(styles[(master.story_style || "minimal") as StyleName] ? master.story_style as StyleName : "minimal");
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");
    const [link, setLink] = useState("");
    const [file, setFile] = useState<File | null>(null);
    const canvas = useRef<HTMLCanvasElement>(null);
    const selected = active.find(s => s.id === serviceId);

    useEffect(() => {
        setLink("");
        setFile(null);
        setError("");
        if (!canvas.current || !selected) return;
        paint(canvas.current, { master: master.display_name, service: selected.name, date, time, price: selected.price_som, slug: master.slug, style });
    }, [master.display_name, master.slug, selected, date, time, style]);

    useEffect(() => {
        const escape = (event: KeyboardEvent) => { if (event.key === "Escape") onClose(); };
        window.addEventListener("keydown", escape);
        return () => window.removeEventListener("keydown", escape);
    }, [onClose]);

    const chooseStyle = async (next: StyleName) => {
        setStyle(next);
        try { await axios.put("/stories/preference", { style: next }); }
        catch { setError("Стиль выбран, но сохранить его на будущее не удалось."); }
    };

    const prepare = async () => {
        if (!selected || !canvas.current) return;
        setBusy(true);
        setError("");
        setFile(null);
        setLink("");
        try {
            const response = await axios.get<{ available: boolean; reason: string | null; url: string | null }>("/stories/availability", {
                params: { service_id: selected.id, date, time },
            });
            if (!response.data.available || !response.data.url) {
                setError(response.data.reason || "Время недоступно.");
                return;
            }
            const blob = await new Promise<Blob>((resolve, reject) =>
                canvas.current!.toBlob(value => value ? resolve(value) : reject(new Error("Ошибка экспорта PNG.")), "image/png"));
            setLink(response.data.url);
            setFile(new File([blob], "bosh-story-" + date + "-" + time.replace(":", "") + ".png", { type: "image/png" }));
        } catch {
            setError("Не удалось проверить время или создать сторис. Попробуйте ещё раз.");
        } finally {
            setBusy(false);
        }
    };
    const download = () => {
        if (!file) return;
        const url = URL.createObjectURL(file);
        const anchor = document.createElement("a");
        anchor.href = url;
        anchor.download = file.name;
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    };
    const share = async () => {
        if (!file) return;
        try {
            if (navigator.share && navigator.canShare?.({ files: [file] })) {
                await navigator.share({ files: [file], title: "Свободное окно" });
            } else download();
        } catch (e) {
            if ((e as Error).name !== "AbortError") setError("Не удалось поделиться. Скачайте PNG.");
        }
    };
    return (
        <div className="story-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) onClose(); }}>
            <section className="story-dialog panel" role="dialog" aria-modal="true" aria-label="Создание сторис об окне">
                <div className="row between"><div><h2>Сторис об окне</h2><p className="muted small">Готовая картинка для Instagram и WhatsApp</p></div><CloseButton label="Закрыть" onClick={onClose} /></div>
                <div className="story-layout">
                    <div className="story-fields">
                        <label className="field"><span>Услуга</span>
                            <select value={serviceId} onChange={event => setServiceId(Number(event.target.value))}>
                                {active.map(service => <option key={service.id} value={service.id}>{service.name} · {service.price_som.toLocaleString("ru-RU")} сом</option>)}
                            </select>
                        </label>
                        <div className="row story-date-fields">
                            <label className="field"><span>Дата</span><input type="date" value={date} onChange={event => setDate(event.target.value)} /></label>
                            <label className="field"><span>Время</span><input type="time" step="1800" value={time} onChange={event => setTime(event.target.value)} /></label>
                        </div>
                        <div className="story-style-options" role="group" aria-label="Шаблон сторис">
                            {(Object.entries(styles) as [StyleName, typeof styles[StyleName]][]).map(([key, item]) =>
                                <button key={key} type="button" className={"story-style" + (style === key ? " selected" : "")}
                                    onClick={() => void chooseStyle(key)} aria-pressed={style === key}>
                                    <span className="story-swatch" style={{ background: item.colors.bg, color: item.colors.text }}>Aa</span>
                                    <span>{item.label}</span>
                                </button>)}
                        </div>
                        {error && <p className="errors" role="alert">{error}</p>}
                        <button className="primary" type="button" disabled={busy || !selected} onClick={() => void prepare()}>
                            {busy ? "Проверяем время…" : "Создать сторис"}
                        </button>
                        {file && <div className="story-actions">
                            <button type="button" onClick={download}>Скачать PNG</button>
                            <button type="button" className="primary" onClick={() => void share()}>Поделиться</button>
                            <button type="button" className="ghost" onClick={() => void navigator.clipboard.writeText(link).catch(() => setError("Не удалось скопировать ссылку."))}>Скопировать ссылку</button>
                            <p className="muted small">В Instagram добавьте скопированную ссылку через стикер «Ссылка». Перед записью доступность времени проверяется повторно.</p>
                        </div>}
                    </div>
                    <div className="story-preview"><canvas ref={canvas} aria-label="Предпросмотр сторис в формате 9 на 16" /><p className="muted small">1080 × 1920 · PNG</p></div>
                </div>
            </section>
        </div>
    );
}
