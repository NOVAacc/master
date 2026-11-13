import { useI18n } from "./i18n";
import { useEffect, useRef, useState, type FormEvent } from 'react';
import axios from 'axios';
import CloseButton from './CloseButton';

export default function FeedbackDrawer() {
    const { t: tr, locale } = useI18n();
    const [open, setOpen] = useState(false);
    const [category, setCategory] = useState('idea');
    const [message, setMessage] = useState('');
    const [busy, setBusy] = useState(false);
    const [sent, setSent] = useState(false);
    const [error, setError] = useState('');
    const dialog = useRef<HTMLDialogElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    useEffect(() => {
        if (open) dialog.current?.showModal();
        else if (dialog.current?.open) dialog.current.close();
    }, [open]);
    function close() { setOpen(false); trigger.current?.focus(); }
    async function submit(event: FormEvent) {
        event.preventDefault();
        if (busy) return;
        setBusy(true); setError('');
        try {
            await axios.post('/feedback', { category, message: message.trim(), page: window.location.pathname });
            setMessage(''); setSent(true);
        } catch (e) {
            const status = axios.isAxiosError(e) ? e.response?.status : undefined;
            setError(status === 429 ? tr("Слишком много сообщений. Попробуйте через 10 минут.") : status === 419 || status === 401 ? tr("Сеанс истёк. Скопируйте сообщение и обновите страницу.") : tr("Не удалось отправить. Текст сохранён в окне — попробуйте ещё раз."));
        } finally { setBusy(false); }
    }
    return <>
        <button ref={trigger} type="button" className="feedback-trigger" aria-haspopup="dialog" aria-expanded={open} onClick={() => { setSent(false); setError(''); setOpen(true); }}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-8 8H4l-2 2v-10a9 9 0 1 1 18 0Z"/><path d="M7 10h8M7 14h5"/></svg>
            <span>{tr("Обратная связь")}</span>
        </button>
        <dialog ref={dialog} className="feedback-drawer" aria-labelledby="feedback-title" onCancel={event => { event.preventDefault(); close(); }} onClick={event => { if (event.target === event.currentTarget) { const r = event.currentTarget.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close(); } }} onClose={() => setOpen(false)}>
            <div className="feedback-heading"><div><span className="feedback-eyebrow">{tr("Делаем BOSH лучше")}</span><h2 id="feedback-title">{tr("Обратная связь")}</h2></div><CloseButton onClick={close} /></div>
            {sent ? <div className="feedback-success" role="status"><span aria-hidden="true">✓</span><h3>{tr("Спасибо за помощь!")}</h3><p>{tr("Ваше сообщение отправлено создателю BOSH.")}</p><button type="button" className="feedback-send" onClick={close}>{tr("Готово")}</button><button type="button" className="feedback-another" onClick={() => setSent(false)}>{tr("Написать ещё")}</button></div> : <form onSubmit={submit}>
                <p className="feedback-intro">{tr("Что улучшить или что пошло не так? Напишите в двух словах — мы читаем каждое сообщение.")}</p>
                <fieldset className="feedback-categories"><legend>{tr("Тип обращения")}</legend>{[['idea', tr("Идея")], ['bug', tr("Ошибка")], ['other', tr("Замечание")]].map(([value, label]) => <label key={value} className={category === value ? 'selected' : ''}><input type="radio" name="feedback-category" value={value} checked={category === value} onChange={() => setCategory(value)} />{label}</label>)}</fieldset>
                <label className="feedback-label" htmlFor="feedback-message">{tr("Ваше сообщение")}</label>
                <textarea autoFocus id="feedback-message" value={message} onChange={event => setMessage(event.target.value)} placeholder={category === 'bug' ? tr("Что вы делали и что не получилось?") : tr("Мне было бы удобнее, если…")} required minLength={3} maxLength={5000} rows={6} />
                <p className="feedback-context">{tr("Ваше имя и текущая страница приложатся автоматически.")}</p>
                {error && <p className="feedback-error" role="alert">{tr(error)}</p>}
                <button className="feedback-send" disabled={busy || message.trim().length < 3} type="submit">{busy ? tr("Отправляем…") : tr("Отправить")}</button>
            </form>}
        </dialog>
    </>;
}
