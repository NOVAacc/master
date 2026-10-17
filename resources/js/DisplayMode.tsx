import { useState } from "react";
import Icon from "./Icon";

type DisplayMode = "auto" | "mobile" | "desktop";
const storageKey = "master.display-mode";

function applyMode(mode: DisplayMode) {
    document.documentElement.dataset.displayMode = mode;
    document
        .querySelector('meta[name="viewport"]')
        ?.setAttribute(
            "content",
            mode === "desktop"
                ? "width=1100"
                : "width=device-width, initial-scale=1",
        );
}

let initialMode: DisplayMode = "auto";
try {
    const saved = localStorage.getItem(storageKey);
    if (saved === "mobile" || saved === "desktop") initialMode = saved;
} catch {
    // The switch also works when browser storage is unavailable.
}
applyMode(initialMode);

export default function DisplayModeFooter() {
    const [mode, setMode] = useState<DisplayMode>(initialMode);
    function selectMode(next: DisplayMode) {
        applyMode(next);
        setMode(next);
        try {
            localStorage.setItem(storageKey, next);
        } catch {
            /* Keep the selection for this session. */
        }
    }
    return (
        <footer className="site-footer">
            <p className="muted small">BOSH — время для вашего дела.</p>
            <div
                className="display-switch"
                role="group"
                aria-label="Версия сайта"
            >
                {(
                    [
                        ["auto", "Авто"],
                        ["mobile", "Мобильная"],
                        ["desktop", "Десктопная"],
                    ] as const
                ).map(([value, label]) => (
                    <button
                        key={value}
                        type="button"
                        aria-pressed={mode === value}
                        onClick={() => selectMode(value)}
                    >
                        {value !== "auto" && (
                            <Icon
                                name={value === "mobile" ? "phone" : "monitor"}
                            />
                        )}
                        {label}
                    </button>
                ))}
            </div>
        </footer>
    );
}
