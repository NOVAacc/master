import { useRef, useState } from 'react';
import axios from 'axios';
import { useI18n } from './i18n';

export default function PaymentToggle({ appointmentId, paid, onSaved }: {
    appointmentId: number; paid: boolean; onSaved: (status: string) => void;
}) {
    const { t: tr } = useI18n();
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const inFlight = useRef(false);
    async function update(next: boolean) {
        if (inFlight.current) return;
        inFlight.current = true; setBusy(true); setError('');
        try {
            const { data } = await axios.post<{payment_status: string}>(`/appointments/${appointmentId}/payment`, { paid: next }, { headers: { Accept: 'application/json' } });
            onSaved(data.payment_status);
        } catch {
            setError('Не удалось сохранить оплату. Повторите попытку.');
        } finally { inFlight.current = false; setBusy(false); }
    }
    return <div className="appointment-payment">
        <label className="appointment-paid-toggle"><input type="checkbox" role="switch" checked={paid} disabled={busy} onChange={event => void update(event.target.checked)} /><span className="appointment-paid-track" aria-hidden="true" /><span>{tr('Оплачено')}</span></label>
        <small className="muted" role="status">{busy ? tr('Сохраняем…') : paid ? tr('Оплата отмечена') : tr('Оплата не отмечена')}</small>
        {error && <p className="errors" role="alert">{tr(error)}</p>}
    </div>;
}
