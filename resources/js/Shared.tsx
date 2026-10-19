import { Link, usePage } from "@inertiajs/react";
import type { ReactNode } from "react";
export function Notice() {
    const { flash } = usePage<{ flash: { success?: string } }>().props;
    return flash.success ? (
        <div role="status" className="notice">
            {flash.success}
        </div>
    ) : null;
}
export function Errors({ errors }: { errors: Record<string, string> }) {
    return Object.keys(errors).length ? (
        <div role="alert" className="errors">
            {Object.entries(errors).map(([key, value]) => (
                <p key={key}>{value}</p>
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
    return (
        <Link href="/" className="brand" aria-label="BOSH — главная страница">
            <BrandMark />
            BOSH
        </Link>
    );
}

export function BrandMark({ small = false }: { small?: boolean }) {
    return (
        <span className={`brand-mark${small ? " brand-mark-small" : ""}`} aria-hidden="true">
            <img src="/brand/bosh-logo-blink.gif" width="500" height="500" alt="" decoding="async" />
        </span>
    );
}
