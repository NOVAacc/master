import { useI18n } from "./i18n";
import { Link, usePage } from "@inertiajs/react";
import type { ReactNode } from "react";
export function Notice() {
    const { t } = useI18n();
    const { flash } = usePage<{ flash: { success?: string; error?: string } }>().props;
    return flash.error ? (
        <div role="alert" className="errors">{t(flash.error)}</div>
    ) : flash.success ? (
        <div role="status" className="notice">{t(flash.success)}</div>
    ) : null;
}
export function Errors({ errors }: { errors: Record<string, string> }) {
    const { t } = useI18n();
    return Object.keys(errors).length ? (
        <div role="alert" className="errors">
            {Object.entries(errors).map(([key, value]) => (
                <p key={key}>{t(value)}</p>
            ))}
        </div>
    ) : null;
}
export function Field({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <label className="field">
            <span>{label}</span>
            {children}
        </label>
    );
}
export function Brand() {
    const { t } = useI18n();
    return (
        <Link href="/" className="brand" aria-label={t("BOSH — главная страница")}>
            <BrandMark />
            BOSH
        </Link>
    );
}

export function BrandMark({ small = false }: { small?: boolean }) {
    return (
        <span className={`brand-mark${small ? " brand-mark-small" : ""}`} aria-hidden="true">
            <img src={small ? "/brand/bosh-clock.svg" : "/brand/bosh-clock-animated.svg"} width="835" height="1020" alt="" decoding="async" />
        </span>
    );
}
