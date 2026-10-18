import { useRef, useState } from "react";

export default function WorkGallery({ images }: { images: (string | null | undefined)[] }) {
    const photos = images.filter((url): url is string => !!url);
    const dialog = useRef<HTMLDialogElement>(null);
    const [selected, setSelected] = useState("");
    if (!photos.length) return null;
    return (
        <section className="work-gallery" aria-label="Работы мастера">
            <div className="work-gallery-grid">
                {photos.map((url, i) => (
                    <button type="button" key={url} aria-label={`Открыть работу ${i + 1}`}
                        onClick={() => { setSelected(url); dialog.current?.showModal(); }}>
                        <img src={url} alt={`Работа мастера ${i + 1}`} loading="lazy" />
                    </button>
                ))}
            </div>
            <dialog className="work-photo-dialog" ref={dialog}
                aria-label="Просмотр работы мастера"
                onClick={e => { if (e.target === e.currentTarget) dialog.current?.close(); }}>
                <button type="button" className="ghost" aria-label="Закрыть просмотр" autoFocus onClick={() => dialog.current?.close()}><span aria-hidden="true">✕</span></button>
                {selected && <img src={selected} alt="Работа мастера крупным планом" />}
            </dialog>
        </section>
    );
}
