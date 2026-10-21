import type { ButtonHTMLAttributes } from "react";

/** Shared compact dismissal control for BOSH overlays and detail panels. */
export default function CloseButton({
    label = "Закрыть",
    className = "",
    type = "button",
    ...props
}: Omit<ButtonHTMLAttributes<HTMLButtonElement>, "aria-label"> & {
    label?: string;
}) {
    return (
        <button
            {...props}
            type={type}
            className={["bosh-close-button", className].filter(Boolean).join(" ")}
            aria-label={label}
            title={label}
        >
            <svg aria-hidden="true" viewBox="0 0 16 16" width="12" height="12" fill="none"
                stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
                <path d="M3.5 3.5l9 9m0-9-9 9" />
            </svg>
        </button>
    );
}
