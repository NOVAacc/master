import { Head, Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Brand } from "../Shared";
import Icon from "../Icon";
import useSectionScroll from "../useSectionScroll";

const services = [
    { name: "Стрижка", duration: "45 мин", price: "500 сом" },
    { name: "Укладка", duration: "30 мин", price: "400 сом" },
];
const slots = ["10:00", "11:30", "14:00", "15:30"];
const advantages = [
    { icon: "link", title: "Одна ссылка для записи", text: "Услуги, цены и свободное время — на вашей странице. Отправьте ссылку клиенту или добавьте её в Instagram." },
    { icon: "calendar", title: "Расписание под ваш день", text: "Настройте рабочие часы и перерыв. Занятое время не предлагается другим клиентам." },
    { icon: "user", title: "Клиенты всегда под рукой", text: "Контакты, история посещений и заметки собраны в карточке клиента." },
];
const steps = [
    { title: "Зарегистрируйтесь", text: "Укажите имя и телефон. Подтвердите номер кодом из SMS." },
    { title: "Настройте свою страницу", text: "Добавьте услуги, цены, продолжительность и рабочие часы. Оформление можно дополнить позже." },
    { title: "Поделитесь ссылкой", text: "Разместите её в соцсетях или отправьте клиентам. Записи появятся в вашем календаре." },
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
            <div className="landing-example-top"><span className="landing-live-dot" />Ваша страница записи<span className="landing-example-tag">ПРИМЕР</span></div>
            <div className="landing-example-body">
                <div className="landing-example-heading"><span className="landing-demo-symbol"><Icon name="calendar" /></span><div><h2>Ваше дело. Ваши правила.</h2><p>Выберите услугу и удобное время</p></div></div>
                {complete ? (
                    <div className="landing-example-success" role="status">
                        <span><Icon name="check" /></span>
                        <h3>Вот так просто.</h3>
                        <p>{services[service].name} · {slot}</p>
                        <p className="muted">В настоящей записи клиент дальше укажет имя и телефон.</p>
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
                <div className="landing-header-actions"><Link href={auth.user ? "/dashboard" : "/login"}>{auth.user ? "Мой кабинет" : "Войти"}</Link>{!auth.user && <Link className="primary" href="/register">Начать бесплатно <Icon name="arrow" /></Link>}</div>
            </header>
            <main>
                <section className="landing-hero">
                    <div className="landing-hero-copy">
                        <p className="landing-kicker"><span />ОНЛАЙН-ЗАПИСЬ ДЛЯ ВАШЕГО ДЕЛА</p>
                        <h1>Вы занимаетесь делом.<br /><span>Клиенты записываются сами.</span></h1>
                        <p className="landing-lead">Ваша страница с услугами, свободное время и записи — в одном месте. Меньше переписок о времени. Больше внимания клиентам.</p>
                        <div className="landing-hero-actions"><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link><a className="landing-text-link" href="#start">Как это работает <span aria-hidden="true">↗</span></a></div>
                        <p className="landing-hero-note"><Icon name="check" /> Бесплатно. На телефоне и компьютере.</p>
                    </div>
                    <BookingExample />
                </section>
                <section className="landing-audience" aria-label="Для кого BOSH"><p>Для тех, к кому приходят по записи</p><div>{["Красота и уход", "Здоровье", "Спорт", "Обучение", "Консультации", "Услуги"].map(item => <span key={item}>{item}</span>)}</div></section>
                <section id="features" className="landing-section">
                    <div className="landing-section-heading"><p className="landing-kicker">ПРОСТОЙ ПОРЯДОК В ДЕЛАХ</p><h2>Всё нужное.<br /><span>В одном рабочем пространстве.</span></h2></div>
                    <div className="landing-benefits">{advantages.map(item => <article key={item.title}><span className="landing-feature-icon"><Icon name={item.icon} /></span><h3>{item.title}</h3><p>{item.text}</p></article>)}</div>
                    <div className="landing-workspace">
                        <div className="landing-workspace-copy"><p className="landing-kicker">ДЕНЬ ПОД КОНТРОЛЕМ</p><h3>Посмотрите на день.<br />И сразу всё понятно.</h3><p>Предстоящие записи, свободные окна и перерывы видны в календаре. Клиента, который записался по телефону или лично, можно добавить самостоятельно.</p><ul><li><Icon name="check" /> Быстрая запись новых и постоянных клиентов</li><li><Icon name="check" /> Подтверждение автоматически или вручную</li><li><Icon name="check" /> Рабочие часы и обеденный перерыв</li></ul></div>
                        <div className="landing-day" aria-label="Иллюстрация расписания"><div className="landing-day-heading"><span><Icon name="calendar" />Ваш день</span><span className="landing-example-tag">ПРИМЕР</span></div>
                            <div className="landing-day-row"><time>09:00</time><div className="landing-day-booking"><strong>Консультация</strong><small>09:00 – 10:00 <span>Подтверждена</span></small></div></div>
                            <div className="landing-day-row"><time>10:00</time><div className="landing-day-free"><span />Свободное время</div></div>
                            <div className="landing-day-row"><time>11:00</time><div className="landing-day-booking pending-example"><strong>Индивидуальное занятие</strong><small>11:00 – 12:00 <span>Ожидает подтверждения</span></small></div></div>
                            <div className="landing-day-row"><time>12:00</time><div className="landing-day-break"><Icon name="clock" />Обеденный перерыв</div></div>
                        </div>
                    </div>
                </section>
                <section id="start" className="landing-section landing-start">
                    <div className="landing-section-heading"><p className="landing-kicker">ОТ ИДЕИ К ПЕРВОЙ ЗАПИСИ</p><h2>Начните с трёх<br /><span>простых шагов.</span></h2></div>
                    <div className="landing-steps">{steps.map((item, i) => <article key={item.title}><span className="landing-step-number">0{i + 1}</span><h3>{item.title}</h3><p>{item.text}</p></article>)}</div>
                    <Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link>
                </section>
                <section className="landing-client">
                    <div><p className="landing-kicker">УДОБНО И ВАШИМ КЛИЕНТАМ</p><h2>Выбрать. Записаться.<br /><span>Прийти к вам.</span></h2><p>Клиент открывает вашу ссылку, видит стоимость и продолжительность услуги, выбирает свободное время и оставляет контакты. Без регистрации и установки приложения.</p></div>
                    <ol><li><span>1</span><div><strong>Открывает вашу страницу</strong><p>По ссылке из соцсетей или сообщения</p></div></li><li><span>2</span><div><strong>Выбирает услугу и время</strong><p>Только доступные для записи часы</p></div></li><li><span>3</span><div><strong>Оставляет имя и телефон</strong><p>А вы видите новую запись в кабинете</p></div></li></ol>
                </section>
                <section id="questions" className="landing-section landing-faq"><div className="landing-section-heading"><p className="landing-kicker">ПЕРЕД НАЧАЛОМ</p><h2>Есть вопросы?</h2></div><div>{questions.map(item => <details key={item.title}><summary>{item.title}<span aria-hidden="true">+</span></summary><p>{item.text}</p></details>)}</div></section>
                <section className="landing-final"><p className="landing-kicker">ВАШ СЛЕДУЮЩИЙ ШАГ</p><h2>Пусть запись<br /><span>работает на вас.</span></h2><p>Создайте страницу бесплатно и поделитесь ею с клиентами.</p><Link className="primary" href={destination}>{action}<Icon name="arrow" /></Link></section>
            </main>
        </div>
    );
}
