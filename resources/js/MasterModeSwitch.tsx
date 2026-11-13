import { useI18n } from "./i18n";
import { Link } from '@inertiajs/react';

export default function MasterModeSwitch({ mode, publicUrl }: { mode: 'dashboard' | 'page'; publicUrl: string }) {
    const { t: tr, locale } = useI18n();
    return <nav className="master-mode-switch" aria-label={tr("Режим мастера")}>
        <Link href="/dashboard" className={mode === 'dashboard' ? 'active' : ''} aria-current={mode === 'dashboard' ? 'page' : undefined}>{tr("Кабинет мастера")}</Link>
        <Link href={publicUrl} className={mode === 'page' ? 'active' : ''} aria-current={mode === 'page' ? 'page' : undefined}>{tr("Страница мастера")}</Link>
    </nav>;
}
