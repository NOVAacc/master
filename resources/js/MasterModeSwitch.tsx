import { useI18n } from "./i18n";
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import Icon from './Icon';

export default function MasterModeSwitch({ mode, publicUrl }: { mode: 'dashboard' | 'page'; publicUrl: string }) {
    const { t: tr } = useI18n();
    const [footer, setFooter] = useState<HTMLElement | null>(null);
    useEffect(() => { setFooter(document.getElementById('mobile-master-mode')); }, []);
    const navigation = (mobile: boolean) => <nav className={`master-mode-switch${mobile ? ' mobile-master-mode-switch' : ''}`} aria-label={tr("Режим мастера")}>
        <Link href="/dashboard" className={mode === 'dashboard' ? 'active' : ''} aria-label={tr("Кабинет мастера")} title={tr("Кабинет мастера")} aria-current={mode === 'dashboard' ? 'page' : undefined}><Icon name="grid" /><span>{tr("Кабинет мастера")}</span></Link>
        <Link href={publicUrl} className={mode === 'page' ? 'active' : ''} aria-label={tr("Страница мастера")} title={tr("Страница мастера")} aria-current={mode === 'page' ? 'page' : undefined}><Icon name="user" /><span>{tr("Страница мастера")}</span></Link>
    </nav>;
    return <>{navigation(false)}{footer && createPortal(navigation(true), footer)}</>;
}
