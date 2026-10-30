import { useState } from "react";

export default function ShareProfile({ url }: { url: string }) {
    const [copied, setCopied] = useState(false);
    const [qrOpen, setQrOpen] = useState(false);
    const [error, setError] = useState("");
    const whatsapp = "https://wa.me/?text=" + encodeURIComponent("Запишитесь ко мне онлайн: " + url);
    const qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=" + encodeURIComponent(url);
    const copy = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true); setError("");
        } catch {
            setError("Не удалось скопировать автоматически. Выделите ссылку и скопируйте её.");
        }
    };
    const share = async () => {
        if (!navigator.share) return copy();
        try { await navigator.share({ title: "Моя страница BOSH", url }); }
        catch (e) { if ((e as Error).name !== "AbortError") setError("Не удалось поделиться. Скопируйте ссылку."); }
    };
    return <div className="bosh-share-panel">
        <label className="field"><span>Ссылка для записи</span><input aria-label="Ваша ссылка" readOnly value={url} onFocus={e => e.currentTarget.select()} /></label>
        <div className="bosh-share-buttons">
            <button type="button" className="primary" onClick={copy}>{copied ? "Скопировано" : "Копировать"}</button>
            <button type="button" className="button" onClick={share}>Поделиться</button>
            <a className="button" href={whatsapp} target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <a className="button" href={url} target="_blank" rel="noopener noreferrer">Посмотреть страницу</a>
        </div>
        <button type="button" className="button small" onClick={() => setQrOpen(open => !open)}>{qrOpen ? "Скрыть QR-код" : "Показать QR-код"}</button>
        {qrOpen && <div className="bosh-share-qr">
            <img src={qr} alt="QR-код страницы мастера" width="200" height="200" loading="lazy" />
            <a className="button small" href={qr} target="_blank" rel="noopener noreferrer">Сохранить изображение</a>
            <p className="muted small">QR создаётся внешним сервисом; ему передаётся только публичная ссылка на страницу.</p>
        </div>}
        {error && <p className="errors" role="alert">{error}</p>}
    </div>;
}
