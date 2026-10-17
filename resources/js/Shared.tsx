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
            <span className="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none" focusable="false">
                    <path d="M10 7h7a5 5 0 0 1 0 10h-7V7Zm0 10h8a4 4 0 0 1 0 8h-8V17Z" stroke="currentColor" strokeWidth="3" strokeLinejoin="round" />
                </svg>
            </span>
            BOSH
        </Link>
    );
}
