import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import "../css/app.css";
createInertiaApp({
    title: (title) => `${title} · Master`,
    resolve: async (name) => {
        const pages = import.meta.glob("./Pages/*.tsx");
        return (await pages[`./Pages/${name}.tsx`]()) as any;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: "#5263d9" },
});
