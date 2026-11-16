import LanguageSwitch from "../i18n";
import { useI18n } from "../i18n";
import { Link, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { Brand } from "../Shared";
import Icon from "../Icon";
import useSectionScroll from "../useSectionScroll";

import kyrgyzFlag from "../../../public/brand/kyrgyzstan-flag.svg?url";

const services = [
    { name: "Стрижка", duration: "45 мин", price: "500 сом" },
    { name: "Укладка", duration: "30 мин", price: "400 сом" },
];
const slots = ["10:00", "11:30", "14:00", "15:30"];
const advantages = [
    { icon: "link", title: "Одна ссылка для записи", text: "Клиент видит ваши услуги, цены и свободные часы — без переписки." },
    { icon: "calendar", title: "Календарь без накладок", text: "Рабочие часы, перерывы и записи в одном календаре. Занятое время недоступно для бронирования." },
    { icon: "user", title: "История клиентов", text: "Контакты, прошлые записи и заметки доступны в карточке клиента." },
];
const steps = [
    { title: "Создайте аккаунт", text: "Укажите контакты и придумайте пароль. Активируйте профиль по ссылке из письма." },
    { title: "Добавьте услуги и график", text: "Укажите цены, длительность услуг и удобные часы работы." },
    { title: "Отправьте ссылку клиентам", text: "Они выберут время сами, а запись появится в календаре." },
];
const questions = [
    { title: "Сколько стоит BOSH?", text: "BOSH — бесплатный сервис для мастеров и клиентов." },
    { title: "Нужно ли клиенту регистрироваться?", text: "Нет. Клиент открывает вашу ссылку, выбирает услугу и доступное время, затем указывает имя и телефон." },
    { title: "Можно записать клиента самостоятельно?", text: "Да. В кабинете есть быстрая запись: выберите клиента из своей базы или добавьте нового, укажите услугу и время." },
    { title: "Как подтверждаются записи?", text: "Вы выбираете режим в настройках: автоматическое подтверждение или подтверждение вручную. Ожидающие записи видны в уведомлениях и списке записей." },
    { title: "Нужно ли устанавливать приложение?", text: "Нет. BOSH работает в браузере телефона и компьютера. Настройки, календарь и клиентская база доступны в вашем кабинете." },
    { title: "Где клиент найдёт мою страницу?", text: "По прямой ссылке, которой вы делитесь. Главная страница BOSH рассказывает о сервисе, а ваша страница предназначена для записи именно к вам." },
];

function BookingExample() {
    const { t } = useI18n();
    const [service, setService] = useState(0);
    const [slot, setSlot] = useState("11:30");
    const [complete, setComplete] = useState(false);
    return (
        <div className="landing-example">
            <div className="landing-example-top">{t("Ваша страница записи")}<span className="landing-example-tag">{t("ПРИМЕР")}</span></div>
            <div className="landing-example-body">
                <div className="landing-example-heading"><span className="landing-demo-symbol"><Icon name="calendar" /></span><div><h2>{t("Запись к вам онлайн")}</h2><p>{t("Выберите услугу и удобное время")}</p></div></div>
                {complete ? (
                    <div className="landing-example-success" role="status">
                        <span><Icon name="check" /></span>
                        <h3>{t("Время выбрано")}</h3>
                        <p>{t(services[service].name)} · {slot}</p>
                        <p className="muted">{t("Это демо. Для настоящей записи клиент ещё указывает имя и телефон.")}</p>
                        <button type="button" className="ghost" onClick={() => setComplete(false)}>{t("Попробовать ещё раз")}</button>
                    </div>
                ) : (
                    <>
                        <p className="landing-demo-label">01 <span>{t("Услуга")}</span></p>
                        <div className="landing-example-services">
                            {services.map((item, i) => <button type="button" key={item.name} aria-pressed={service === i} onClick={() => setService(i)}>
                                <span className="landing-radio" aria-hidden="true">{service === i && <span />}</span>
                                <span><strong>{t(item.name)}</strong><small>{t(item.duration)}</small></span><b>{t(item.price)}</b>
                            </button>)}
                        </div>
                        <div className="landing-demo-date"><p className="landing-demo-label">02 <span>{t("Свободное время")}</span></p><span>{t("Пример дня")}</span></div>
                        <div className="landing-example-slots">
                            {slots.map(time => <button type="button" key={time} aria-pressed={slot === time} onClick={() => setSlot(time)}>{time}</button>)}
                        </div>
                        <button type="button" className="primary landing-example-next" onClick={() => setComplete(true)}>{t("Выбрать это время")}<Icon name="arrow" /></button>
                    </>
                )}
                <p className="landing-example-disclaimer">{t("Интерактивный пример · запись не отправляется")}</p>
            </div>
            <div className="landing-example-bottom"><Icon name="check" /><span>{t("Клиенту не нужен аккаунт")}</span><Icon name="phone" /></div>
        </div>
    );
}

type Statistics = { masters: number; appointments: number; updatedAt: string };

function HomeStatistics({ statistics }: { statistics: Statistics }) {
    const { t, locale } = useI18n();
    const root = useRef<HTMLElement>(null);
    const [progress, setProgress] = useState(0);
    useEffect(() => {
        let frame = 0;
        const element = root.current;
        if (!element) return;
        const animate = () => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                setProgress(1);
                return;
            }
            const start = performance.now();
            const tick = (now: number) => {
                const elapsed = Math.min((now - start) / 1400, 1);
                setProgress(1 - Math.pow(1 - elapsed, 3));
                if (elapsed < 1) frame = requestAnimationFrame(tick);
            };
            frame = requestAnimationFrame(tick);
        };
        if (!('IntersectionObserver' in window)) { animate(); return () => cancelAnimationFrame(frame); }
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) { observer.disconnect(); animate(); }
        }, { threshold: 0.2 });
        observer.observe(element);
        return () => { observer.disconnect(); cancelAnimationFrame(frame); };
    }, [statistics.masters, statistics.appointments]);
    const format = new Intl.NumberFormat(locale === 'ky' ? 'ky-KG' : 'ru-RU');
    return <section className="landing-statistics" ref={root} aria-label={t("BOSH в цифрах")}>
        <div className="landing-statistics-heading"><h2>{t("BOSH в цифрах")}</h2><p>{t("Мастера и записи нашего сервиса")}</p></div>
        <dl>{[[statistics.masters, t("Мастеров на BOSH")], [statistics.appointments, t("Записей создано")]].map(([count, label]) => <div key={String(label)}><dt>{label}</dt><dd><span aria-hidden="true">{format.format(Math.round(Number(count) * progress))}</span><span className="sr-only">{format.format(Number(count))}</span></dd></div>)}</dl>
        <p className="landing-statistics-updated">{t("Обновлено")} <time dateTime={statistics.updatedAt}>{new Intl.DateTimeFormat(locale === 'ky' ? 'ky-KG' : 'ru-RU', { timeZone: 'Asia/Bishkek', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(statistics.updatedAt))}</time> · {t("Время Бишкека")}</p>
    </section>;
}

export default function Home() {
    const { t } = useI18n();
    useSectionScroll();
    const { auth, statistics } = usePage<{ auth: { user: { name: string } | null }; statistics: Statistics }>().props;
    const destination = auth.user ? "/dashboard" : "/register";
    const action = auth.user ? t("Перейти в кабинет") : t("Начать бесплатно");
    return (
        <div className="landing">

            <header className="landing-header">
                <Brand /><span className="landing-mobile-name">BOSH</span>
                <nav className="landing-header-sections" aria-label={t("О сервисе")}><a href="#features">{t("Возможности")}</a><a href="#start">{t("Как начать")}</a><a href="#questions">{t("Вопросы")}</a></nav>
                <div className="landing-header-actions"><LanguageSwitch /><Link className={auth.user ? "landing-account-button" : undefined} href={auth.user ? "/dashboard" : "/login"}>{auth.user && <span className="landing-account-icon"><Icon name="grid" /></span>}{auth.user ? t("Кабинет мастера") : t("Войти")}{auth.user && <Icon name="arrow" />}</Link>{!auth.user && <Link className="primary" href="/register">{t("Начать бесплатно")}<Icon name="arrow" /></Link>}</div>
            </header>
            <main>
                <section className="landing-hero">
                    <div className="landing-hero-copy">

                        <p className="landing-origin"><img src={kyrgyzFlag} alt="" />{t("Сделано в Кыргызстане")}</p>
                        <h1>{t("Вы занимаетесь делом.")}<br /><span>{t("Клиенты записываются сами.")}</span></h1>
                        <p className="landing-lead">{t("Создайте страницу с услугами и свободным временем. Клиенты выберут удобный час, а вы увидите запись в календаре.")}</p>
                        <p className="landing-languages">{t("На русском и кыргызском языках — для вас и ваших клиентов.")}</p>
                        <div className="landing-hero-actions"><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link><Link className="landing-text-link" href="/demo">{t("Посмотреть демо")}<Icon name="arrow" /></Link></div>
                        <p className="landing-hero-note">{t("Бесплатно · Работает в браузере")}</p>
                    </div>
                    <BookingExample />
                </section>
                <HomeStatistics statistics={statistics} />
                <section className="landing-audience" aria-label={t("Для кого BOSH")}><p>{t("Для тех, к кому приходят по записи")}</p><div>{[t("Красота и уход"), t("Здоровье"), t("Спорт"), t("Обучение"), t("Консультации"), t("Услуги")].map(item => <span key={item}>{t(item)}</span>)}</div></section>
                <section id="features" className="landing-section">
                    <div className="landing-section-heading"><h2>{t("Всё для записи клиентов")}</h2></div>
                    <div className="landing-benefits">{advantages.map(item => <article key={item.title}><span className="landing-feature-icon"><Icon name={item.icon} /></span><h3>{t(item.title)}</h3><p>{t(item.text)}</p></article>)}</div>
                    <div className="landing-workspace">
                        <div className="landing-workspace-copy"><h3>{t("Рабочий день в календаре")}</h3><p>{t("Предстоящие записи, свободные окна и перерывы видны в календаре. Клиента, который записался по телефону или лично, можно добавить самостоятельно.")}</p><ul><li><Icon name="check" /> {t("Быстрая запись новых и постоянных клиентов")}</li><li><Icon name="check" /> {t("Подтверждение автоматически или вручную")}</li><li><Icon name="check" /> {t("Рабочие часы и обеденный перерыв")}</li></ul></div>
                        <div className="landing-day" aria-label={t("Иллюстрация расписания")}><div className="landing-day-heading"><span><Icon name="calendar" />{t("Ваш день")}</span><span className="landing-example-tag">{t("ПРИМЕР")}</span></div>
                            <div className="landing-day-row"><time>09:00</time><div className="landing-day-booking"><strong>{t("Консультация")}</strong><small>09:00 – 10:00 <span>{t("Подтверждена")}</span></small></div></div>
                            <div className="landing-day-row"><time>10:00</time><div className="landing-day-free">{t("Свободное время")}</div></div>
                            <div className="landing-day-row"><time>11:00</time><div className="landing-day-booking pending-example"><strong>{t("Индивидуальное занятие")}</strong><small>11:00 – 12:00 <span>{t("Ожидает подтверждения")}</span></small></div></div>
                            <div className="landing-day-row"><time>12:00</time><div className="landing-day-break"><Icon name="clock" />{t("Обеденный перерыв")}</div></div>
                        </div>
                    </div>
                </section>
                <section id="start" className="landing-section landing-start">
                    <div className="landing-section-heading"><h2>{t("Как начать")}</h2></div>
                    <div className="landing-steps">{steps.map((item, i) => <article key={item.title}><span className="landing-step-number">0{i + 1}</span><h3>{t(item.title)}</h3><p>{t(item.text)}</p></article>)}</div>
                    <Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link>
                </section>
                <section id="questions" className="landing-section landing-faq"><div className="landing-section-heading"><h2>{t("Частые вопросы")}</h2></div><div>{questions.map(item => <details key={item.title}><summary>{t(item.title)}<span aria-hidden="true">+</span></summary><p>{t(item.text)}</p></details>)}</div></section>
                <section className="landing-final"><h2>{t("Создайте свою страницу записи")}</h2><p>{t("Добавьте услуги и поделитесь ссылкой с клиентами. Бесплатно.")}</p><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link></section>
            </main>
        </div>
    );
}
