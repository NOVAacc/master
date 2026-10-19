import { useState } from "react";
import Icon from "./Icon";
import { BrandMark } from "./Shared";

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
            <div className="footer-identity">
                <a href="/" aria-label="BOSH — главная страница"><BrandMark small /></a>
                <p className="muted small">BOSH — время для вашего дела.</p>
            </div>
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
                        aria-label={label === "Авто" ? "Автоматический режим" : label === "Мобильная" ? "Мобильная версия" : "Десктопная версия"}
                        title={label === "Авто" ? "Автоматический режим" : label === "Мобильная" ? "Мобильная версия" : "Десктопная версия"}
                        onClick={() => selectMode(value)}
                    >
                        <Icon name={value === "auto" ? "devices" : value === "mobile" ? "phone" : "monitor"} />
                    </button>
                ))}
            </div>
        </footer>
    );
}
