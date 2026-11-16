import { Head, usePage } from "@inertiajs/react";
type Seo = { title: string; description: string; canonical: string; index: boolean; schema: object | null };
export default function SeoHead() {
    const { seo } = usePage<{ seo: Seo }>().props;
    return <Head title={seo.title}>
        <meta name="description" content={seo.description} head-key="description" />
        <meta name="robots" content={seo.index ? "index, follow, max-image-preview:large" : "noindex, follow"} head-key="robots" />
        <link rel="canonical" href={seo.canonical} head-key="canonical" />
        <meta property="og:title" content={seo.title} head-key="og:title" />
        <meta property="og:description" content={seo.description} head-key="og:description" />
        <meta property="og:url" content={seo.canonical} head-key="og:url" />
        <meta name="twitter:title" content={seo.title} head-key="twitter:title" />
        <meta name="twitter:description" content={seo.description} head-key="twitter:description" />
        {seo.schema && <script type="application/ld+json" head-key="schema">{JSON.stringify(seo.schema).replace(/</g, "\\u003c")}</script>}
    </Head>;
}
