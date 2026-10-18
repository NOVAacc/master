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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="182 158 880 880" width="40" height="40" focusable="false">
                    <rect x="182" y="158" width="880" height="880" rx="128" fill="#050505" />
                    <path fill="#fff" d="M575 311A342 331 0 1 1 453 352L453 422A284 273 0 1 0 575 370Z" />
                    <path fill="#fff" fillRule="evenodd" d="M478 260A35 35 0 0 1 548 260V486C577 466 613 454 650 454C745 454 822 535 822 636C822 736 745 817 650 817C555 817 478 740 478 650ZM650 528C589 528 540 576 540 637C540 697 589 746 650 746C690 746 726 721 746 683L650 633Z" />
                    <circle cx="649.5" cy="633" r="44.25" fill="#ff2933" />
                </svg>
            </span>
            BOSH
        </Link>
    );
}
