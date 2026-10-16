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
        <Link href="/" className="brand" aria-label="Master — главная страница">
            <span className="brand-mark" aria-hidden="true">
                <i />
            </span>
            master
        </Link>
    );
}
