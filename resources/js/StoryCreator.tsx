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

type Theme = "nails"|"hair"|"barber"|"brows"|"makeup"|"massage"|"skin"|"general";
const themes: Record<Theme,string> = {nails:"Маникюр",hair:"Волосы",barber:"Барбер",brows:"Брови и ресницы",makeup:"Макияж",massage:"Массаж",skin:"Косметология",general:"Универсальный"};
const rules: [Theme,string[]][]=[
 ["nails",["маникюр","педикюр","ногт","nail","шеллак","гель лак"]],
 ["brows",["бров","ресниц","lash","brow","ламинирован"]],
 ["makeup",["визаж","макияж","makeup","мейкап"]],
 ["massage",["массаж","спа","spa","релакс"]],
 ["skin",["косметолог","пилинг","чистка лица","эпиляц","депиляц"]],
 ["barber",["барбер","бород","фейд","fade","мужская стрижка","брить"]],
 ["hair",["стриж","окрашив","волос","причес","причёс","парикмах","hair"]]
];
const norm = (v:string) => v.toLowerCase().replace(/ё/g,"е");
export function storyTheme(service:Service,master:Master):Theme {
 for(const value of [service.name,service.description||"",[master.specialty,master.activity,master.industry].filter(Boolean).join(" ")]) {
  for(const [theme,words] of rules) if(words.some(word=>norm(value).includes(norm(word)))) return theme;
 }
 return "general";
}
function drawArt(ctx:CanvasRenderingContext2D,theme:Theme){
 ctx.save();ctx.translate(725,710);ctx.globalAlpha=.36;
 const c:Record<Theme,string>={nails:"#C73855",hair:"#65413D",barber:"#353C44",brows:"#6D453D",makeup:"#B84963",massage:"#A27868",skin:"#BB8B7A",general:"#A5636A"};
 ctx.fillStyle=c[theme];ctx.strokeStyle=c[theme];ctx.lineCap="round";ctx.lineJoin="round";
 if(theme==="nails"){
  ctx.rotate(-.22);ctx.beginPath();ctx.moveTo(-150,180);ctx.lineTo(-125,-95);ctx.quadraticCurveTo(-118,-156,-87,-143);ctx.quadraticCurveTo(-63,-138,-70,-102);ctx.lineTo(-72,27);ctx.lineTo(-38,-205);ctx.quadraticCurveTo(-28,-253,5,-235);ctx.quadraticCurveTo(24,-226,17,-190);ctx.lineTo(-7,29);ctx.lineTo(46,-219);ctx.quadraticCurveTo(60,-267,91,-244);ctx.quadraticCurveTo(110,-230,99,-195);ctx.lineTo(55,34);ctx.lineTo(113,-149);ctx.quadraticCurveTo(128,-186,156,-163);ctx.quadraticCurveTo(171,-147,157,-117);ctx.lineTo(87,100);ctx.quadraticCurveTo(186,29,214,77);ctx.quadraticCurveTo(232,109,196,140);ctx.lineTo(106,236);ctx.quadraticCurveTo(-45,292,-150,180);ctx.fill();
  ctx.globalAlpha=.95;ctx.fillStyle="#F8D5D9";for(const [x,y] of [[-99,-122],[-8,-214],[69,-224],[132,-148]]){ctx.beginPath();ctx.roundRect(x-18,y-8,37,65,18);ctx.fill();}
 } else if(theme==="hair"||theme==="barber"){
  ctx.beginPath();ctx.arc(0,12,194,Math.PI,0);ctx.lineTo(165,208);ctx.lineTo(-161,208);ctx.closePath();ctx.fill();ctx.globalAlpha=.7;ctx.fillStyle="#D5AE98";ctx.beginPath();ctx.ellipse(0,10,121,169,0,0,Math.PI*2);ctx.fill();
  if(theme==="barber"){ctx.fillStyle="#363A41";ctx.fillRect(90,-230,78,238);for(let x=98;x<166;x+=13)ctx.fillRect(x,-260,6,32);}
 }else if(theme==="brows"||theme==="makeup"||theme==="skin"){
  ctx.beginPath();ctx.ellipse(0,0,207,245,0,0,Math.PI*2);ctx.fill();
  ctx.globalAlpha=.7;ctx.fillStyle="#F2CFBE";ctx.beginPath();ctx.ellipse(0,0,161,208,0,0,Math.PI*2);ctx.fill();
  ctx.strokeStyle="#573E3C";ctx.lineWidth=22;ctx.beginPath();ctx.moveTo(-124,-49);ctx.quadraticCurveTo(-71,-88,-27,-55);ctx.moveTo(28,-55);ctx.quadraticCurveTo(75,-88,125,-49);ctx.stroke();
  if(theme==="makeup"){ctx.fillStyle="#B83D5B";ctx.fillRect(122,68,36,144);}
 }else if(theme==="massage"){
  ctx.beginPath();ctx.arc(0,-142,82,0,Math.PI*2);ctx.fill();ctx.lineWidth=85;ctx.beginPath();ctx.moveTo(-160,172);ctx.quadraticCurveTo(-65,-10,16,90);ctx.quadraticCurveTo(118,176,199,39);ctx.stroke();
 }else{ctx.lineWidth=30;ctx.beginPath();ctx.arc(0,0,207,0,Math.PI*2);ctx.moveTo(0,-134);ctx.lineTo(0,0);ctx.lineTo(120,82);ctx.stroke();}
 ctx.restore();
}

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
function drawStory(canvas: HTMLCanvasElement, master: Master, service: Service, date: string, time: string, style: Style, link: string, theme: Theme) {
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Браузер не поддерживает создание изображений.");
    const p = palettes[style];
    ctx.fillStyle = p.bg;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    drawArt(ctx, theme);
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
    const url = new URL(link);
    const displayLink = url.host + url.pathname;
    ctx.fillText(displayLink.length > 46 ? displayLink.slice(0, 43) + "…" : displayLink, margin, 1740);
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
    const [themeChoice,setThemeChoice]=useState<Theme|null>(null);
    const service = activeServices.find(s => s.id === serviceId);
    const theme=themeChoice ?? (service ? storyTheme(service,master) : "general");
    useEffect(()=>setThemeChoice(null),[serviceId]);
    const link = new URL(`/m/${encodeURIComponent(master.slug)}?date=${encodeURIComponent(date)}&service=${serviceId}&time=${encodeURIComponent(time)}`, window.location.origin).toString();

    useEffect(() => {
        setPrepared(null);
        setError("");
        if (service && canvas.current && /^\d{4}-\d{2}-\d{2}$/.test(date) && /^\d{2}:\d{2}$/.test(time)) {
            drawStory(canvas.current, master, service, date, time, style, link, theme);
        }
    }, [master, service, date, time, style, link, theme]);
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
            drawStory(canvas.current, master, service, date, time, style, link, theme);
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
                    <label>Тематика изображения<select value={theme} onChange={e=>setThemeChoice(e.target.value as Theme)}>{(Object.keys(themes) as Theme[]).map(key=><option key={key} value={key}>{themes[key]}</option>)}</select></label>
                    <p className="muted small">Тематика подобрана по услуге автоматически. Можно изменить.</p>
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
