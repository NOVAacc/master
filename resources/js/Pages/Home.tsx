import { Head, Link, usePage } from "@inertiajs/react";
import { Brand } from "../Shared";
import Icon from "../Icon";
import { MasterAvatar } from "../ProfileIdentity";

type Master = {
    slug: string;
    is_demo?: boolean;
    display_name: string;
    specialty: string | null;
    address: string | null;
    price_from: number;
    completed_count: number;
    avatar_url?: string | null;
    cover_url?: string | null;
    profile_theme?: string | null;
};

function MasterCard({
    master,
    popular = false,
}: {
    master: Master;
    popular?: boolean;
}) {
    return (
        <Link
            className="master-card panel"
            href={`/m/${encodeURIComponent(master.slug)}`}
        >
            <div
                className={`master-card-cover theme-${master.profile_theme || "graphite"}`}
            >
                {master.cover_url && (
                    <img src={master.cover_url} alt="" loading="lazy" />
                )}
            </div>
            <div className="master-card-identity">
                <MasterAvatar master={master} large />
                <div>
                    <h3>{master.display_name}</h3>
                    {master.is_demo && <span className="badge">Демо-профиль</span>}
                    <p className="muted small">
                        {master.specialty || "Частный специалист"}
                    </p>
                </div>
            </div>
            {master.address && (
                <p className="master-address muted small">
                    <Icon name="location" />
                    {master.address}
                </p>
            )}
            {popular && (
                <p className="small muted">
                    Завершённых записей: {master.completed_count}
                </p>
            )}
            <div className="row between master-card-footer">
                <span>от {master.price_from.toLocaleString("ru-RU")} сом</span>
                <span className="card-book-action">
                    {master.is_demo ? "Смотреть услуги" : "Записаться"} <Icon name="arrow" />
                </span>
            </div>
        </Link>
    );
}

export default function Home({
    newMasters,
    topMasters,
}: {
    newMasters: Master[];
    topMasters: Master[];
}) {
    const { auth } = usePage<{ auth: { user: { name: string } | null } }>()
        .props;
    return (
        <div className="catalog-wrap">
            <Head title="Мастера и онлайн-запись" />
            <header className="catalog-header row between">
                <Brand />
                <nav className="row" aria-label="Личный кабинет">
                    <Link href={auth.user ? "/dashboard" : "/login"}>
                        {auth.user ? "Мой кабинет" : "Войти"}
                    </Link>
                    {!auth.user && (
                        <Link className="primary" href="/register">
                            Стать мастером
                        </Link>
                    )}
                </nav>
            </header>
            <main>
                <section className="catalog-hero panel">
                    <p className="eyebrow">ОНЛАЙН-ЗАПИСЬ · BOSH</p>
                    <h1>
                        Найдите своего мастера.
                        <br />
                        Выберите удобное время.
                    </h1>
                    <p className="muted">
                        Знакомьтесь со специалистами, смотрите услуги и
                        записывайтесь онлайн.
                    </p>
                    <div className="hero-note">
                        <Icon name="check" /> Услуги, цены и свободное время — в
                        профиле мастера
                    </div>
                    <nav className="row wrap" aria-label="Каталог мастеров">
                        <a href="#new" className="primary">
                            Новые мастера
                        </a>
                        <a href="#top" className="ghost">
                            Популярные мастера
                        </a>
                    </nav>
                </section>
                <section
                    id="new"
                    className="catalog-section"
                    aria-labelledby="new-title"
                >
                    <p className="eyebrow">НОВЫЕ ЗНАКОМСТВА</p>
                    <h2 id="new-title">Новые мастера</h2>
                    <p className="muted">
                        Специалисты, которые недавно присоединились к BOSH.
                    </p>
                    {newMasters.length ? (
                        <div className="masters-grid">
                            {newMasters.map((master) => (
                                <MasterCard key={master.slug} master={master} />
                            ))}
                        </div>
                    ) : (
                        <div className="panel empty">
                            Здесь появятся мастера с открытой записью и
                            добавленными услугами.{" "}
                            <Link href={auth.user ? "/dashboard" : "/register"}>
                                Создать свой профиль
                            </Link>
                        </div>
                    )}
                </section>
                <section
                    id="top"
                    className="catalog-section"
                    aria-labelledby="top-title"
                >
                    <p className="eyebrow">ВЫБОР КЛИЕНТОВ</p>
                    <h2 id="top-title">Популярные мастера</h2>
                    <p className="muted">
                        По количеству завершённых записей через BOSH.
                    </p>
                    {topMasters.length ? (
                        <div className="masters-grid">
                            {topMasters.map((master) => (
                                <MasterCard
                                    key={master.slug}
                                    master={master}
                                    popular
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="panel empty">
                            Популярные мастера появятся после первых завершённых
                            записей.
                        </div>
                    )}
                </section>
            </main>
        </div>
    );
}
