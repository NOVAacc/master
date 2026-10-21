import type { ReactNode } from "react";
const paths: Record<string, ReactNode> = {
    bell: <><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" /><path d="M10 21h4" /></>,
    link: <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.71" />,
    pencil: <><path d="m15 5 4 4M4 20l4.2-.9L19 8.3a2.1 2.1 0 0 0-3-3L5.2 16.1 4 20Z" /></>,
    calendar: (
        <>
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M7 3v4m10-4v4M3 11h18m-14 4h2m4 0h2m-8 3h2" />
        </>
    ),
    grid: (
        <>
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" />
        </>
    ),
    clock: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </>
    ),
    user: (
        <>
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21v-2a8 8 0 0 1 16 0v2" />
        </>
    ),
    arrow: <path d="M5 12h14m-6-6 6 6-6 6" />,
    location: (
        <>
            <path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z" />
            <circle cx="12" cy="10" r="2" />
        </>
    ),
    check: <path d="m5 12 4 4L19 6" />,
    monitor: (
        <>
            <rect x="3" y="4" width="18" height="13" rx="2" />
            <path d="M8 21h8m-4-4v4" />
        </>
    ),
    devices: (
        <>
            <rect x="2" y="5" width="15" height="11" rx="2" />
            <path d="M5 19h9m-4-3v3" />
            <rect x="17" y="9" width="5" height="11" rx="1.2" />
            <path d="M19 18h1" />
        </>
    ),
    phone: (
        <>
            <rect x="6" y="2" width="12" height="20" rx="3" />
            <path d="M11 18h2" />
        </>
    ),
    settings: (
        <>
            <circle cx="12" cy="12" r="3" />
            <path d="M10 2h4l.65 2.35 1.8.75 2.12-1.2 2.83 2.83-1.2 2.12.75 1.8L23 11v4l-2.35.65-.75 1.8 1.2 2.12-2.83 2.83-2.12-1.2-1.8.75L14 24h-4l-.65-2.35-1.8-.75-2.12 1.2-2.83-2.83 1.2-2.12-.75-1.8L1 15v-4l2.35-.65.75-1.8-1.2-2.12L5.73 3.6l2.12 1.2 1.8-.75L10 2Z" transform="translate(0 -1)" />
        </>
    ),
};
export default function Icon({
    name,
    className = "",
}: {
    name: string;
    className?: string;
}) {
    return (
        <svg
            className={`icon ${className}`}
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {paths[name] || paths.grid}
        </svg>
    );
}
