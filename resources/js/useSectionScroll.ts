import { useEffect } from "react";

/** Timed scrolling for homepage anchors, with native history and interruption. */
export default function useSectionScroll() {
    useEffect(() => {
        let frame = 0;
        let target: HTMLElement | null = null;
        const cancel = () => {
            cancelAnimationFrame(frame);
            frame = 0;
            target = null;
        };
        const click = (event: MouseEvent) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>('.landing a[href^="#"]') : null;
            const hash = link?.getAttribute("href");
            if (!hash || hash === "#") return;
            const section = document.getElementById(hash.slice(1));
            if (!section) return;
            event.preventDefault();
            cancel();
            if (location.hash !== hash) history.pushState(history.state, "", hash);
            const from = window.scrollY;
            const offset = parseFloat(getComputedStyle(section).scrollMarginTop) || 0;
            const to = Math.max(0, Math.min(
                from + section.getBoundingClientRect().top - offset,
                document.documentElement.scrollHeight - window.innerHeight,
            ));
            const focusSection = () => {
                const previous = section.getAttribute("tabindex");
                section.setAttribute("tabindex", "-1");
                section.focus({ preventScroll: true });
                if (previous === null) section.removeAttribute("tabindex");
                else section.setAttribute("tabindex", previous);
            };
            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                window.scrollTo({ top: to, behavior: "instant" });
                focusSection();
                return;
            }
            target = section;
            const started = performance.now();
            const animate = (now: number) => {
                if (!target) return;
                const progress = Math.min(1, (now - started) / 1600);
                const eased = progress < .5 ? 4 * progress ** 3 : 1 - ((-2 * progress + 2) ** 3) / 2;
                window.scrollTo({ top: from + (to - from) * eased, behavior: "instant" });
                if (progress < 1) frame = requestAnimationFrame(animate);
                else {
                    frame = 0;
                    target = null;
                    focusSection();
                }
            };
            frame = requestAnimationFrame(animate);
        };
        document.addEventListener("click", click);
        window.addEventListener("wheel", cancel, { passive: true });
        window.addEventListener("touchstart", cancel, { passive: true });
        window.addEventListener("pointerdown", cancel, { passive: true });
        window.addEventListener("keydown", cancel);
        window.addEventListener("popstate", cancel);
        return () => {
            cancel();
            document.removeEventListener("click", click);
            window.removeEventListener("wheel", cancel);
            window.removeEventListener("touchstart", cancel);
            window.removeEventListener("pointerdown", cancel);
            window.removeEventListener("keydown", cancel);
            window.removeEventListener("popstate", cancel);
        };
    }, []);
}
