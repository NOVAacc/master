import { Head, Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Brand } from "../Shared";
import Icon from "../Icon";
import useSectionScroll from "../useSectionScroll";

const audiences = [
    { title: "Красота и уход", icon: "user", examples: "Барберы, парикмахеры, бровисты и мастера маникюра", detail: "Покажите услуги и цены — клиент сам выберет время для визита." },
    { title: "Здоровье", icon: "calendar", examples: "Массажисты и специалисты по восстановлению", detail: "Планируйте сеансы и оставляйте нужные перерывы между приёмами." },
    { title: "Спорт", icon: "clock", examples: "Тренеры и инструкторы", detail: "Соберите тренировки и контакты клиентов в одном календаре." },
    { title: "Обучение", icon: "pencil", examples: "Репетиторы и преподаватели", detail: "Делитесь свободными часами для индивидуальных занятий." },
    { title: "Консультации", icon: "devices", examples: "Консультанты и частные специалисты", detail: "Принимайте запись на консультации по персональной ссылке." },
    { title: "Услуги", icon: "grid", examples: "Все, кто работает по предварительной записи", detail: "Добавьте свои услуги, настройте график и отправьте ссылку клиентам." },
];

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
    const [service, setService] = useState(0);
    const [slot, setSlot] = useState("11:30");
    const [complete, setComplete] = useState(false);
    return (
        <div className="landing-example">
            <div className="landing-example-top">Ваша страница записи<span className="landing-example-tag">ПРИМЕР</span></div>
            <div className="landing-example-body">
                <div className="landing-example-heading"><span className="landing-demo-symbol"><Icon name="calendar" /></span><div><h2>Запись к вам онлайн</h2><p>Выберите услугу и удобное время</p></div></div>
                {complete ? (
                    <div className="landing-example-success" role="status">
                        <span><Icon name="check" /></span>
                        <h3>Время выбрано</h3>
                        <p>{services[service].name} · {slot}</p>
                        <p className="muted">Это демо. Для настоящей записи клиент ещё указывает имя и телефон.</p>
                        <button type="button" className="ghost" onClick={() => setComplete(false)}>Попробовать ещё раз</button>
                    </div>
                ) : (
                    <>
                        <p className="landing-demo-label">01 <span>Услуга</span></p>
                        <div className="landing-example-services">
                            {services.map((item, i) => <button type="button" key={item.name} aria-pressed={service === i} onClick={() => setService(i)}>
                                <span className="landing-radio" aria-hidden="true">{service === i && <span />}</span>
                                <span><strong>{item.name}</strong><small>{item.duration}</small></span><b>{item.price}</b>
                            </button>)}
                        </div>
                        <div className="landing-demo-date"><p className="landing-demo-label">02 <span>Свободное время</span></p><span>Пример дня</span></div>
                        <div className="landing-example-slots">
                            {slots.map(time => <button type="button" key={time} aria-pressed={slot === time} onClick={() => setSlot(time)}>{time}</button>)}
                        </div>
                        <button type="button" className="primary landing-example-next" onClick={() => setComplete(true)}>Выбрать это время <Icon name="arrow" /></button>
                    </>
                )}
                <p className="landing-example-disclaimer">Интерактивный пример · запись не отправляется</p>
            </div>
            <div className="landing-example-bottom"><Icon name="check" /><span>Клиенту не нужен аккаунт</span><Icon name="phone" /></div>
        </div>
    );
}

export default function Home() {
    useSectionScroll();
    const { auth } = usePage<{ auth: { user: { name: string } | null } }>().props;
    const destination = auth.user ? "/dashboard" : "/register";
    const action = auth.user ? "Перейти в кабинет" : "Начать бесплатно";
    return (
        <div className="landing">
            <Head title="BOSH — онлайн-запись для вашего дела">
                <meta name="description" content="BOSH — бесплатный сервис онлайн-записи для мастеров и специалистов. Своя страница, услуги, календарь и клиентская база. Расскажем, как начать принимать записи." />
            </Head>
            <header className="landing-header">
                <Brand /><span className="landing-mobile-name">BOSH</span>
                <nav className="landing-header-sections" aria-label="О сервисе"><a href="#features">Возможности</a><a href="#start">Как начать</a><a href="#questions">Вопросы</a></nav>
                <div className="landing-header-actions"><Link className={auth.user ? "landing-account-button" : undefined} href={auth.user ? "/dashboard" : "/login"}>{auth.user && <span className="landing-account-icon"><Icon name="grid" /></span>}{auth.user ? "Кабинет мастера" : "Войти"}{auth.user && <Icon name="arrow" />}</Link>{!auth.user && <Link className="primary" href="/register">Начать бесплатно <Icon name="arrow" /></Link>}</div>
            </header>
            <main>
                <section className="landing-hero">
                    <div className="landing-hero-copy">
                        
                        <h1>Вы занимаетесь делом.<br /><span>Клиенты записываются сами.</span></h1>
                        <p className="landing-lead">Создайте страницу с услугами и свободным временем. Клиенты выберут удобный час, а вы увидите запись в календаре.</p>
                        <div className="landing-hero-actions"><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link><Link className="landing-text-link" href="/demo">Посмотреть демо <Icon name="arrow" /></Link></div>
                        <p className="landing-hero-note">Бесплатно · Работает в браузере</p>
                        <div className="landing-made-in"><span className="landing-kg-flag" role="img" aria-label="Флаг Кыргызстана">🇰🇬</span><div><strong>Сделано в Кыргызстане</strong><span>Для вашего дела. Для наших людей.</span></div></div>
                    </div>
                    <BookingExample />
                </section>
                <section className="landing-audience landing-audience-cards" aria-labelledby="audience-title"><p className="landing-audience-kicker">ДЛЯ КОГО BOSH</p><h2 id="audience-title">Для тех, к кому приходят по записи</h2><div className="landing-audience-grid">{audiences.map((item, index) => <details className="landing-audience-card" key={item.title} style={{ animationDelay: `${index * 65}ms` }}><summary><span className="landing-audience-icon"><Icon name={item.icon} /></span><strong>{item.title}</strong><span className="landing-audience-examples">{item.examples}</span><span className="landing-audience-more">Как поможет BOSH <span aria-hidden="true">+</span></span></summary><p>{item.detail}</p></details>)}</div></section>
                <section id="features" className="landing-section">
                    <div className="landing-section-heading"><h2>Всё для записи клиентов</h2></div>
                    <div className="landing-benefits">{advantages.map(item => <article key={item.title}><span className="landing-feature-icon"><Icon name={item.icon} /></span><h3>{item.title}</h3><p>{item.text}</p></article>)}</div>
                    <div className="landing-workspace">
                        <div className="landing-workspace-copy"><h3>Рабочий день в календаре</h3><p>Предстоящие записи, свободные окна и перерывы видны в календаре. Клиента, который записался по телефону или лично, можно добавить самостоятельно.</p><ul><li><Icon name="check" /> Быстрая запись новых и постоянных клиентов</li><li><Icon name="check" /> Подтверждение автоматически или вручную</li><li><Icon name="check" /> Рабочие часы и обеденный перерыв</li></ul></div>
                        <div className="landing-day" aria-label="Иллюстрация расписания"><div className="landing-day-heading"><span><Icon name="calendar" />Ваш день</span><span className="landing-example-tag">ПРИМЕР</span></div>
                            <div className="landing-day-row"><time>09:00</time><div className="landing-day-booking"><strong>Консультация</strong><small>09:00 – 10:00 <span>Подтверждена</span></small></div></div>
                            <div className="landing-day-row"><time>10:00</time><div className="landing-day-free">Свободное время</div></div>
                            <div className="landing-day-row"><time>11:00</time><div className="landing-day-booking pending-example"><strong>Индивидуальное занятие</strong><small>11:00 – 12:00 <span>Ожидает подтверждения</span></small></div></div>
                            <div className="landing-day-row"><time>12:00</time><div className="landing-day-break"><Icon name="clock" />Обеденный перерыв</div></div>
                        </div>
                    </div>
                </section>
                <section id="start" className="landing-section landing-start">
                    <div className="landing-section-heading"><h2>Как начать</h2></div>
                    <div className="landing-steps">{steps.map((item, i) => <article key={item.title}><span className="landing-step-number">0{i + 1}</span><h3>{item.title}</h3><p>{item.text}</p></article>)}</div>
                    <Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link>
                </section>
                <section id="questions" className="landing-section landing-faq"><div className="landing-section-heading"><h2>Частые вопросы</h2></div><div>{questions.map(item => <details key={item.title}><summary>{item.title}<span aria-hidden="true">+</span></summary><p>{item.text}</p></details>)}</div></section>
                <section className="landing-final"><h2>Создайте свою страницу записи</h2><p>Добавьте услуги и поделитесь ссылкой с клиентами. Бесплатно.</p><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link></section>
            </main>
        </div>
    );
}
