import { useEffect, useRef, useState } from "react";
import CloseButton from "./CloseButton";
import { useI18n } from "./i18n";
import type { Master, Service } from "./types";

type Props = { master: Master; services: Service[]; publicUrl: string; onClose: () => void };
const WIDTH = 1080;
const HEIGHT = 1920;
const ITEMS_TOP = 480;
const ITEMS_BOTTOM = 1455;
const IMAGE_SIZE = 218;

function lines(ctx: CanvasRenderingContext2D, value: string, maxWidth: number, maxLines = 2): string[] {
    const words = value.trim().split(/\s+/).filter(Boolean);
    const output: string[] = [];
    let current = "";
    for (const word of words) {
        const candidate = current ? current + " " + word : word;
        if (ctx.measureText(candidate).width <= maxWidth) {
            current = candidate;
        } else {
            if (current) output.push(current);
            current = word;
        }
    }
    if (current) output.push(current);
    // Very long unbroken strings must not overflow the price column.
    const fitted: string[] = [];
    for (const line of output) {
        if (ctx.measureText(line).width <= maxWidth) { fitted.push(line); continue; }
        let part = "";
        for (const char of line) {
            if (ctx.measureText(part + char).width > maxWidth && part) { fitted.push(part); part = ""; }
            part += char;
        }
        if (part) fitted.push(part);
    }
    if (fitted.length > maxLines) {
        const cut = fitted.slice(0, maxLines);
        let last = cut[maxLines - 1];
        while (last && ctx.measureText(last + "…").width > maxWidth) last = last.slice(0, -1);
        cut[maxLines - 1] = last + "…";
        return cut;
    }
    return fitted;
}

function qrImage(publicUrl: string): Promise<HTMLImageElement> {
    const url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=" + encodeURIComponent(publicUrl);
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = "anonymous";
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error("QR-код недоступен. Проверьте соединение и попробуйте ещё раз."));
        image.src = url;
    });
}

function drawPriceList(canvas: HTMLCanvasElement, master: Master, services: Service[], publicUrl: string, qr: HTMLImageElement, page: number, total: number): void {
    canvas.width = WIDTH; canvas.height = HEIGHT;
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Браузер не поддерживает создание изображений.");
    ctx.fillStyle = "#F8F7F4"; ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = "#D22D35"; ctx.fillRect(76, 86, 80, 8);
    ctx.textAlign = "left";
    ctx.fillStyle = "#232323";
    ctx.font = "700 64px Arial, sans-serif";
    const name = lines(ctx, master.display_name || "Мастер", 900, 2);
    name.forEach((text, index) => ctx.fillText(text, 76, 205 + index * 72));
    ctx.fillStyle = "#747474";
    ctx.font = "32px Arial, sans-serif";
    const specialty = lines(ctx, master.specialty || "Онлайн-запись", 910, 1);
    ctx.fillText(specialty[0] ?? "", 78, 290 + (name.length - 1) * 72);
    ctx.fillStyle = "#252525"; ctx.font = "500 84px Arial, sans-serif";
    ctx.fillText("Прайс-лист", 76, 440);
    ctx.fillStyle = "#D8D6D1"; ctx.fillRect(76, 461, 928, 2);
    let y = ITEMS_TOP + 50;
    services.forEach((service) => {
        ctx.font = "500 40px Arial, sans-serif";
        const nameLines = lines(ctx, service.name, 605, 2);
        const height = nameLines.length > 1 ? 138 : 112;
        ctx.textAlign = "left";
        ctx.fillStyle = "#252525";
        nameLines.forEach((line, i) => ctx.fillText(line, 78, y + i * 49));
        ctx.textAlign = "right";
        ctx.font = "700 39px Arial, sans-serif";
        ctx.fillText(Number(service.price_som).toLocaleString("ru-RU") + " сом", 1000, y);
        ctx.fillStyle = "#D8D6D1"; ctx.fillRect(76, y + height - 41, 928, 2);
        y += height;
    });
    ctx.textAlign = "left";
    ctx.fillStyle = "#FFFFFF"; ctx.fillRect(76, 1505, IMAGE_SIZE + 28, IMAGE_SIZE + 28);
    ctx.drawImage(qr, 90, 1519, IMAGE_SIZE, IMAGE_SIZE);
    ctx.fillStyle = "#282828"; ctx.font = "700 39px Arial, sans-serif";
    ctx.fillText("Записаться онлайн", 340, 1594);
    ctx.fillStyle = "#747474"; ctx.font = "30px Arial, sans-serif";
    const hostLabel = lines(ctx, publicUrl.replace(/^https?:\/\//, ""), 635, 2);
    hostLabel.forEach((line, index) => ctx.fillText(line, 340, 1660 + index * 37));
    ctx.fillStyle = "#B6B4AE"; ctx.fillRect(76, 1816, 928, 2);
    ctx.fillStyle = "#747474"; ctx.font = "26px Arial, sans-serif";
    ctx.fillText("Создано в BOSH", 78, 1870);
    if (total > 1) { ctx.textAlign = "right"; ctx.fillText(page + " / " + total, 1000, 1870); }
}

export default function PriceListGenerator({ master, services, publicUrl, onClose }: Props) {
    const { t: tr } = useI18n();
    const active = services.filter(service => service.is_active && Number.isFinite(Number(service.price_som)) && Number(service.price_som) >= 0);
    const [selectedIds, setSelectedIds] = useState<number[]>(() => active.map(service => service.id));
    const [page, setPage] = useState(0);
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [rendered, setRendered] = useState(false);
    const canvas = useRef<HTMLCanvasElement>(null);
    const dialog = useRef<HTMLDialogElement>(null);
    const selected = active.filter(service => selectedIds.includes(service.id));
    // A fixed cap prevents the footer from overlapping long names, even with two-line titles.
    const perPage = 7;
    const chunks = Array.from({length: Math.ceil(selected.length / perPage)}, (_, index) => selected.slice(index * perPage, (index + 1) * perPage));
    const safePage = Math.min(page, Math.max(0, chunks.length - 1));

    useEffect(() => {
        const node = dialog.current;
        node?.showModal();
        return () => node?.close();
    }, []);
    useEffect(() => {
        let cancelled = false;
        setRendered(false);
        setError("");
        if (!chunks.length || !canvas.current) return;
        qrImage(publicUrl).then(image => {
            if (cancelled || !canvas.current) return;
            try {
                drawPriceList(canvas.current, master, chunks[safePage], publicUrl, image, safePage + 1, chunks.length);
                setRendered(true);
            } catch (cause) { setError(cause instanceof Error ? cause.message : tr("Не удалось создать прайс.")); }
        }).catch(cause => { if (!cancelled) setError(cause instanceof Error ? cause.message : tr("Не удалось загрузить QR-код.")); });
        return () => { cancelled = true; };
    }, [master, publicUrl, selectedIds, safePage, services]);

    async function download() {
        if (!selected.length || busy) return;
        setBusy(true); setError("");
        try {
            const image = await qrImage(publicUrl);
            for (let i = 0; i < chunks.length; i++) {
                const output = document.createElement("canvas");
                drawPriceList(output, master, chunks[i], publicUrl, image, i + 1, chunks.length);
                const blob = await new Promise<Blob | null>(resolve => output.toBlob(resolve, "image/png"));
                if (!blob) throw new Error(tr("Не удалось сохранить PNG."));
                const href = URL.createObjectURL(blob);
                const a = document.createElement("a"); a.href = href;
                a.download = "bosh-price-" + (master.slug || "master") + (chunks.length > 1 ? "-" + (i + 1) : "") + ".png";
                document.body.appendChild(a); a.click(); a.remove();
                window.setTimeout(() => URL.revokeObjectURL(href), 60000);
            }
        } catch (cause) { setError(cause instanceof Error ? cause.message : tr("Не удалось скачать прайс.")); }
        finally { setBusy(false); }
    }

    return <dialog ref={dialog} className="service-dialog" style={{width:"min(920px, 96vw)", maxWidth:"96vw"}} onCancel={event => {event.preventDefault(); if (!busy) onClose();}}>
        <div className="service-dialog-header">
            <h2>{tr("Мой прайс")}</h2>
            <CloseButton label={tr("Закрыть")} onClick={onClose} disabled={busy}/>
        </div>
        <div style={{display:"grid", gridTemplateColumns:"repeat(auto-fit, minmax(min(100%, 270px), 1fr))", gap:24, padding:20, alignItems:"start"}}>
            <div>
                <p className="muted small">{tr("Выберите услуги. Прайс для Stories будет создан автоматически.")}</p>
                <div style={{display:"grid", gap:12, marginTop:16, maxHeight:380, overflowY:"auto"}}>
                    {active.map(service => <label className="check" key={service.id}>
                        <input type="checkbox" checked={selectedIds.includes(service.id)} onChange={event => {
                            setSelectedIds(ids => event.target.checked ? [...ids, service.id] : ids.filter(id => id !== service.id));
                            setPage(0);
                        }}/>
                        <span>{service.name} · {Number(service.price_som).toLocaleString("ru-RU")} {tr("сом")}</span>
                    </label>)}
                    {!active.length && <p className="muted">{tr("Добавьте хотя бы одну активную услугу с ценой.")}</p>}
                </div>
                <button className="primary" type="button" style={{marginTop:20, width:"100%"}} onClick={() => void download()} disabled={!selected.length || busy || !rendered}>
                    {busy ? tr("Создаём изображения…") : chunks.length > 1 ? tr("Скачать прайс ({0} стр.)", [chunks.length]) : tr("Скачать PNG")}
                </button>
                {error && <p className="errors" role="alert" style={{marginTop:12}}>{error}</p>}
                <p className="muted small" style={{marginTop:12}}>{tr("Формат: 1080 × 1920. QR-код ведёт на вашу страницу BOSH.")}</p>
            </div>
            <div style={{minWidth:0, textAlign:"center"}}>
                {chunks.length > 0 ? <>
                    <canvas ref={canvas} aria-label={tr("Предпросмотр прайса")} style={{display:"block", width:"100%", maxWidth:310, height:"auto", margin:"auto", background:"#F8F7F4", borderRadius:12, boxShadow:"0 8px 30px #00000015"}}/>
                    {chunks.length > 1 && <div className="row" style={{justifyContent:"center", alignItems:"center", marginTop:12}}>
                        <button type="button" className="ghost" disabled={safePage===0} onClick={() => setPage(p => Math.max(0,p-1))}>{tr("Назад")}</button>
                        <span className="muted small">{safePage+1} / {chunks.length}</span>
                        <button type="button" className="ghost" disabled={safePage===chunks.length-1} onClick={() => setPage(p => Math.min(chunks.length-1,p+1))}>{tr("Далее")}</button>
                    </div>}
                </> : <p className="muted">{tr("Выберите услуги для предпросмотра.")}</p>}
            </div>
        </div>
    </dialog>;
}
