import { LocaleProvider } from "./i18n";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import "../css/app.css";
import SeoHead from "./SeoHead";
import DisplayModeFooter from "./DisplayMode";
createInertiaApp({
    title: (title) => title || "BOSH",
    resolve: async (name) => {
        const pages = import.meta.glob("./Pages/*.tsx");
        const module = (await pages[`./Pages/${name}.tsx`]()) as any;
        module.default.layout = (page: React.ReactNode) => <>{page}<SeoHead /></>;
        return module;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <LocaleProvider><div className="display-frame">
                <App {...props} />
                <DisplayModeFooter initialInstagramUsername={(props.initialPage.props.site as { instagramUsername?: string } | undefined)?.instagramUsername ?? "bosh.kgz"} />
            </div></LocaleProvider>,
        );
    },
    progress: { color: "#252525" },
});
