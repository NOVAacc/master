import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useI18n } from './i18n';
import { Errors, Field } from './Shared';
import CloseButton from './CloseButton';

export default function DeleteAccount({ accountId, passwordSet }: { accountId: number; passwordSet: boolean }) {
    const { t: tr } = useI18n();
    const [open, setOpen] = useState(false);
    const dialog = useRef<HTMLDialogElement>(null);
    const form = useForm({ confirm_account_id: '', confirm_deletion: false, deletion_password: '' });
    useEffect(() => {
        if (open) dialog.current?.showModal();
        else if (dialog.current?.open) dialog.current.close();
    }, [open]);
    function close() {
        if (form.processing) return;
        setOpen(false);
        form.reset();
        form.clearErrors();
    }
    return <section className="panel account-delete-section" aria-label={tr('Удаление аккаунта')}>
        <h2>{tr('Удаление аккаунта')}</h2>
        <p className="muted">{tr('Ваш профиль, услуги, расписание, клиенты и записи будут удалены. Восстановить аккаунт через сайт будет невозможно.')}</p>
        {!passwordSet && <p className="muted small">{tr('Для удаления нужен текущий пароль. Если вы ещё не задали пароль, создайте его выше.')}</p>}
        <button type="button" className="account-danger-button" onClick={() => setOpen(true)} aria-haspopup="dialog">{tr('Удалить мой аккаунт')}</button>
        <dialog ref={dialog} className="account-delete-dialog" aria-labelledby="delete-account-title" onCancel={event => { event.preventDefault(); close(); }} onClose={close}>
            <div className="account-delete-heading"><h2 id="delete-account-title">{tr('Удалить аккаунт навсегда?')}</h2><CloseButton disabled={form.processing} onClick={close} /></div>
            <p>{tr('Будут удалены ваш профиль и фотографии, услуги, расписание, клиентская база, записи и групповые занятия. Публичная страница перестанет работать, а все сеансы входа завершатся.')}</p>
            <p className="account-delete-warning">{tr('Это действие нельзя отменить.')}</p>
            <p>{tr('ID аккаунта:')} <strong className="account-id-number">{accountId}</strong></p>
            <form onSubmit={event => {
                event.preventDefault();
                form.delete('/account', { preserveScroll: true, onFinish: () => form.reset('deletion_password') });
            }}>
                <Errors errors={form.errors} />
                <Field label={tr('Введите ID аккаунта для подтверждения')}><input required type="text" inputMode="numeric" autoComplete="off" maxLength={20} value={form.data.confirm_account_id} onChange={event => form.setData('confirm_account_id', event.target.value)} disabled={form.processing} /></Field>
                <Field label={tr('Текущий пароль')}><input required type="password" autoComplete="current-password" maxLength={128} value={form.data.deletion_password} onChange={event => form.setData('deletion_password', event.target.value)} disabled={form.processing} /></Field>
                <label className="account-delete-check"><input type="checkbox" required checked={form.data.confirm_deletion} onChange={event => form.setData('confirm_deletion', event.target.checked)} disabled={form.processing} /><span>{tr('Я понимаю, что аккаунт и перечисленные данные будут удалены без возможности восстановления через сайт.')}</span></label>
                <div className="account-delete-actions"><button type="button" className="ghost" disabled={form.processing} onClick={close}>{tr('Отмена')}</button><button type="submit" className="account-danger-button" disabled={form.processing || form.data.confirm_account_id !== String(accountId) || !form.data.confirm_deletion || !form.data.deletion_password}>{form.processing ? tr('Удаляем…') : tr('Удалить навсегда')}</button></div>
            </form>
        </dialog>
    </section>;
}
