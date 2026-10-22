import axios from "axios";
import { useEffect, useRef, useState } from "react";
import CloseButton from "./CloseButton";
import type { Master, Service } from "./types";

type Style = "minimal" | "dark" | "soft" | "accent" | "premium";
const palettes: Record<Style, { name: string; bg: string; fg: string; sub: string; action: string; actionText: string }> = {
    minimal: { name: "Минимализм", bg: "#F5F4F1", fg: "#202020", sub: "#76716D", action: "#202020", actionText: "#FFFFFF" },
    dark: { name: "Графит", bg: "#1B1D22", fg: "#FFFFFF", sub: "#B8BAC0", action: "#FFFFFF", actionText: "#1B1D22" },
    soft: { name: "Нежный", bg: "#F8E3E8", fg: "#6C304B", sub: "#9E6B80", action: "#6C304B", actionText: "#FFFFFF" },
    accent: { name: "Акцент", bg: "#CE303E", fg: "#FFFFFF", sub: "#FBE5E7", action: "#FFFFFF", actionText: "#AE2632" },
    premium: { name: "Премиум", bg: "#26342D", fg: "#F3EAD7", sub: "#C9BBA3", action: "#DDC390", actionText: "#26342D" },
};
const WIDTH = 1080;
const HEIGHT = 1920;

function wrappedText(ctx: CanvasRenderingContext2D, value: string, maxWidth: number, maxLines: number) {
    const words = value.trim().split(/\s+/);
    const result: string[] = [];
    let line = "";
    for (const word of words) {
        const attempt = line ? line + " " + word : word;
        if (ctx.measureText(attempt).width > maxWidth && line) {
            result.push(line);
            line = word;
        } else line = attempt;
    }
    if (line) result.push(line);
    const clipped = result.slice(0, maxLines);
    if (result.length > maxLines && clipped.length) {
        let last = clipped[clipped.length - 1];
        while (last && ctx.measureText(last + "…").width > maxWidth) last = last.slice(0, -1);
        clipped[clipped.length - 1] = last + "…";
    }
    return clipped;
}
function drawStory(canvas: HTMLCanvasElement, master: Master, service: Service, date: string, time: string, style: Style, link: string) {
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Браузер не поддерживает создание изображений.");
    const p = palettes[style];
    ctx.fillStyle = p.bg;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    const margin = 100;
    ctx.textBaseline = "top";
    ctx.fillStyle = p.fg;
    ctx.font = "bold 45px system-ui, sans-serif";
    ctx.fillText("BOSH.", margin, 132);
    ctx.font = "500 36px system-ui, sans-serif";
    const mast = wrappedText(ctx, master.display_name, 650, 2);
    mast.forEach((line, i) => ctx.fillText(line, margin, 235 + i * 48));
    ctx.fillStyle = p.sub;
    ctx.font = "500 38px system-ui, sans-serif";
    ctx.fillText("ЕСТЬ СВОБОДНОЕ ВРЕМЯ", margin, 580);
    ctx.fillStyle = p.fg;
    ctx.font = "bold 94px system-ui, sans-serif";
    wrappedText(ctx, "Освободилось окно", 870, 3).forEach((line, i) => ctx.fillText(line, margin, 690 + i * 112));
    ctx.font = "600 54px system-ui, sans-serif";
    wrappedText(ctx, service.name, 860, 2).forEach((line, i) => ctx.fillText(line, margin, 1030 + i * 68));
    const niceDate = new Date(date + "T12:00:00Z").toLocaleDateString("ru-RU", { timeZone: "UTC", day: "numeric", month: "long" });
    ctx.font = "bold 138px system-ui, sans-serif";
    ctx.fillText(time, margin, 1230);
    ctx.font = "500 49px system-ui, sans-serif";
    ctx.fillText(niceDate + " · " + service.price_som.toLocaleString("ru-RU") + " сом", margin, 1400);
    ctx.fillStyle = p.action;
    ctx.beginPath();
    ctx.roundRect(margin, 1570, WIDTH - margin * 2, 108, 54);
    ctx.fill();
    ctx.fillStyle = p.actionText;
    ctx.font = "600 48px system-ui, sans-serif";
    ctx.fillText("Записаться", margin + 55, 1594);
    ctx.fillStyle = p.sub;
    ctx.font = "500 28px system-ui, sans-serif";
    wrappedText(ctx, link, WIDTH - margin * 2, 2).forEach((line, i) => ctx.fillText(line, margin, 1740 + i * 38));
}
function toBlob(canvas: HTMLCanvasElement): Promise<Blob> {
    return new Promise((resolve, reject) => canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error("Не удалось сохранить PNG.")), "image/png"));
}
export default function StoryCreator({ master, services, onClose, initialDate, initialTime, initialService }: {
    master: Master; services: Service[]; onClose: () => void;
    initialDate?: string; initialTime?: string; initialService?: number;
}) {
    const activeServices = services.filter(s => s.is_active);
    const [serviceId, setServiceId] = useState(initialService && activeServices.some(s => s.id === initialService) ? initialService : activeServices[0]?.id || 0);
    const [date, setDate] = useState(initialDate || new Intl.DateTimeFormat("en-CA", { timeZone: master.timezone, year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date()));
    const [time, setTime] = useState(initialTime || "12:00");
    const [style, setStyle] = useState<Style>(master.story_style && master.story_style in palettes ? master.story_style as Style : "minimal");
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [prepared, setPrepared] = useState<File | null>(null);
    const canvas = useRef<HTMLCanvasElement>(null);
    const service = activeServices.find(s => s.id === serviceId);
    const link = new URL(`/m/${encodeURIComponent(master.slug)}?date=${encodeURIComponent(date)}&service=${serviceId}&time=${encodeURIComponent(time)}`, window.location.origin).toString();

    useEffect(() => {
        setPrepared(null);
        setError("");
        if (service && canvas.current && /^\d{4}-\d{2}-\d{2}$/.test(date) && /^\d{2}:\d{2}$/.test(time)) {
            drawStory(canvas.current, master, service, date, time, style, link);
        }
    }, [master, service, date, time, style, link]);
    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => { if (event.key === "Escape") onClose(); };
        window.addEventListener("keydown", handleKey);
        return () => window.removeEventListener("keydown", handleKey);
    }, [onClose]);
    const chooseStyle = (next: Style) => {
        setStyle(next);
        axios.post("/stories/style", { style: next }).catch(() => setError("Не удалось запомнить стиль. Изображение можно сохранить."));
    };
    async function prepare() {
        if (!service || !canvas.current) return;
        setBusy(true);
        setError("");
        setPrepared(null);
        try {
            const response = await axios.get<{available: boolean; reason?: string}>("/stories/availability", {params: {service: service.id, date, time}});
            if (!response.data.available) throw new Error(response.data.reason || "Это время сейчас недоступно.");
            drawStory(canvas.current, master, service, date, time, style, link);
            const blob = await toBlob(canvas.current);
            setPrepared(new File([blob], `bosh-${date}-${time.replace(":", "-")}.png`, { type: "image/png" }));
        } catch (e) {
            setError(e instanceof Error ? e.message : "Не удалось создать сторис. Попробуйте снова.");
        } finally { setBusy(false); }
    }
    function download() {
        if (!prepared) return;
        const url = URL.createObjectURL(prepared);
        const a = document.createElement("a");
        a.href = url; a.download = prepared.name;
        document.body.append(a); a.click(); a.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 10000);
    }
    async function share() {
        if (!prepared) return;
        if (!navigator.canShare?.({files: [prepared]}) || !navigator.share) {
            download();
            setError("Системная отправка недоступна. PNG сохранён на устройство.");
            return;
        }
        try {
            await navigator.share({files: [prepared], title: "Свободное время"});
        } catch (e) {
            if ((e as DOMException).name !== "AbortError") setError("Не удалось поделиться. Используйте «Скачать PNG».");
        }
    }
    return <div className="story-overlay" role="presentation" onMouseDown={e => { if (e.target === e.currentTarget) onClose(); }}>
        <section className="story-dialog panel" role="dialog" aria-modal="true" aria-label="Создать сторис">
            <div className="row between"><h2>Сторис об окне</h2><CloseButton onClick={onClose} label="Закрыть" /></div>
            <div className="story-grid">
                <div className="story-fields">
                    <label>Услуга<select value={serviceId} onChange={e => setServiceId(Number(e.target.value))}>{activeServices.map(s => <option key={s.id} value={s.id}>{s.name} · {s.price_som.toLocaleString("ru-RU")} сом</option>)}</select></label>
                    <div className="story-input-row">
                        <label>Дата<input type="date" value={date} onChange={e => setDate(e.target.value)} /></label>
                        <label>Время<input type="time" step="1800" value={time} onChange={e => setTime(e.target.value)} /></label>
                    </div>
                    <div className="story-palettes" role="group" aria-label="Стиль сторис">
                        {(Object.entries(palettes) as [Style, typeof palettes[Style]][]).map(([key, p]) =>
                            <button type="button" key={key} className={style === key ? "story-palette selected" : "story-palette"} aria-pressed={style === key} onClick={() => chooseStyle(key)}>
                                <span className="story-color" style={{background: p.bg, color: p.fg}}>Aa</span><span>{p.name}</span>
                            </button>)}
                    </div>
                    <p className="muted small">Сторис создаётся в размере 1080 × 1920. Ссылка на запись подготовлена отдельно для стикера Instagram.</p>
                    <div className="row wrap">
                        <button type="button" className="primary" disabled={busy || !service} onClick={() => void prepare()}>{busy ? "Проверяем время…" : "Создать сторис"}</button>
                        {prepared && <button type="button" className="ghost" onClick={download}>Скачать PNG</button>}
                        {prepared && <button type="button" className="ghost" onClick={() => void share()}>Поделиться</button>}
                        {prepared && <button type="button" className="ghost" onClick={() => navigator.clipboard.writeText(link).then(() => setError("Ссылка скопирована.")).catch(() => setError("Не удалось скопировать ссылку."))}>Копировать ссылку</button>}
                    </div>
                    {error && <p role="status" className="muted small">{error}</p>}
                </div>
                <canvas ref={canvas} className="story-preview" aria-label="Предпросмотр сторис" />
            </div>
        </section>
    </div>;
}
