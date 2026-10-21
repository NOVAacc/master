import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import "../css/app.css";
import DisplayModeFooter from "./DisplayMode";
createInertiaApp({
    title: () => "BOSH",
    resolve: async (name) => {
        const pages = import.meta.glob("./Pages/*.tsx");
        return (await pages[`./Pages/${name}.tsx`]()) as any;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <div className="display-frame">
                <App {...props} />
                <DisplayModeFooter />
            </div>,
        );
    },
    progress: { color: "#252525" },
});
