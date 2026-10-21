import { useEffect, useState } from "react";
import Icon from "./Icon";
import { BrandMark } from "./Shared";

type DisplayMode = "auto" | "mobile" | "desktop";
const storageKey = "master.display-mode";
const phoneWidth = "(max-width: 700px)";

function isPhone() {
    return window.matchMedia(phoneWidth).matches;
}

function applyMode(mode: DisplayMode) {
    const effectiveMode = isPhone() ? "auto" : mode;
    document.documentElement.dataset.displayMode = effectiveMode;
    document
        .querySelector('meta[name="viewport"]')
        ?.setAttribute(
            "content",
            effectiveMode === "desktop"
                ? "width=1100"
                : "width=device-width, initial-scale=1",
        );
}

let initialMode: DisplayMode = "auto";
try {
    const saved = localStorage.getItem(storageKey);
    if (saved === "mobile" || saved === "desktop") initialMode = saved;
} catch {
    // Storage can be disabled; responsive mode must still work.
}
applyMode(initialMode);

export default function DisplayModeFooter() {
    const [mode, setMode] = useState<DisplayMode>(initialMode);

    useEffect(() => {
        const media = window.matchMedia(phoneWidth);
        const syncMode = () => applyMode(mode);
        syncMode();
        media.addEventListener("change", syncMode);
        return () => media.removeEventListener("change", syncMode);
    }, [mode]);

    function selectMode(next: DisplayMode) {
        if (isPhone()) return;
        applyMode(next);
        setMode(next);
        try {
            localStorage.setItem(storageKey, next);
        } catch {
            // Keep the selection for the current session.
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
