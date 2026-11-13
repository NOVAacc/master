import { Link } from '@inertiajs/react';

export default function MasterModeSwitch({ mode, publicUrl }: { mode: 'dashboard' | 'page'; publicUrl: string }) {
    return <nav className="master-mode-switch" aria-label="Режим мастера">
        <Link href="/dashboard" className={mode === 'dashboard' ? 'active' : ''} aria-current={mode === 'dashboard' ? 'page' : undefined}>Кабинет мастера</Link>
        <Link href={publicUrl} className={mode === 'page' ? 'active' : ''} aria-current={mode === 'page' ? 'page' : undefined}>Страница мастера</Link>
    </nav>;
}
