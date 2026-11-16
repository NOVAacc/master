import { Head, Link } from "@inertiajs/react";
import { useMemo, useState } from "react";
import AppointmentCalendar from "../AppointmentCalendar";
import { Brand } from "../Shared";
import { addDays, localDay } from "../calendar";
import type { Appointment, Hour, Service } from "../types";

const timezone = "Asia/Bishkek";
const hours: Hour[] = [1, 2, 3, 4, 5, 6].map(weekday => ({
    weekday,
    starts_at: "09:00",
    ends_at: "18:00",
    break_starts_at: "12:00",
    break_ends_at: "13:00",
}));
const services: Service[] = [
    { id: 1, name: "Стрижка", description: "", duration_minutes: 60, price_som: 800, is_active: true },
    { id: 2, name: "Укладка", description: "", duration_minutes: 30, price_som: 400, is_active: true },
];
const clientNames = ["Айдана", "Эрика", "Алина", "Мария"];

function exampleAppointments(day: string): Appointment[] {
    return [
        { id: 1, client_name: clientNames[0], client_phone: "Демонстрационные данные",
            client_comment: "", starts_at: `${day}T10:00:00+06:00`, ends_at: `${day}T11:00:00+06:00`,
            price_som: 800, service_name_snapshot: "Стрижка", status: "confirmed",
            revision: 1, notification_revision: 0, master_service_id: 1 },
        { id: 2, client_name: clientNames[1], client_phone: "Демонстрационные данные",
            client_comment: "", starts_at: `${day}T14:00:00+06:00`, ends_at: `${day}T14:30:00+06:00`,
            price_som: 400, service_name_snapshot: "Укладка", status: "pending",
            revision: 1, notification_revision: 0, master_service_id: 2 },
        { id: 3, client_name: clientNames[2], client_phone: "Демонстрационные данные",
            client_comment: "", starts_at: `${day}T15:30:00+06:00`, ends_at: `${day}T16:30:00+06:00`,
            price_som: 800, service_name_snapshot: "Стрижка", status: "confirmed",
            revision: 1, notification_revision: 0, master_service_id: 1 },
    ];
}

export default function Demo() {
    const today = localDay(new Date(), timezone);
    const dayOfWeek = new Date(`${today}T12:00:00Z`).getUTCDay();
    const exampleDay = addDays(today, dayOfWeek === 0 ? 1 : 0);
    const [date, setDate] = useState(exampleDay);
    const [section, setSection] = useState<"appointments" | "services" | "clients" | "profile" | "schedule" | "hot-slots" | "telegram">("appointments");
    const bookings = useMemo(() => exampleAppointments(date), [date]);
    const [hotOfferCreated, setHotOfferCreated] = useState(false);
    const [notificationsEnabled, setNotificationsEnabled] = useState(true);

    return <div className="demo-page">
        <Head title="Демо кабинета мастера — BOSH">
            <meta name="description" content="Посмотрите, как устроены календарь, записи и услуги в BOSH. Демонстрация без регистрации и изменения данных." />
        </Head>
        <header className="demo-topbar">
            <Brand />
            <span className="demo-indicator">Демо · без регистрации, без сохранения данных</span>
            <Link className="primary" href="/register">Создать свой кабинет</Link>
        </header>
        <main className="demo-layout">
            <aside className="demo-sidebar panel">
                <h1>Кабинет мастера</h1>
                <p className="muted small">Актуальный обзор возможностей BOSH на демонстрационных данных.</p>
                <nav aria-label="Разделы демонстрации">
                    {([
                        ["appointments", "Записи"],
                        ["services", "Услуги"],
                        ["clients", "Клиенты"],
                        ["profile", "Моя страница"],
                        ["schedule", "Расписание"],
                        ["hot-slots", "Горящие окошки"],
                        ["telegram", "Telegram"],
                    ] as const).map(([id, title]) =>
                        <button key={id} type="button" className={section === id ? "active" : ""} aria-current={section === id ? "page" : undefined}
                            onClick={() => setSection(id)}>{title}</button>
                    )}
                </nav>
                <p className="muted small demo-side-note">Все действия здесь демонстрационные. Реальные сообщения, записи и предложения не создаются.</p>
            </aside>
            <section className="demo-content panel">
                {section === "appointments" && <>
                    <div className="row between wrap">
                        <div><p className="eyebrow">РАБОЧИЙ КАЛЕНДАРЬ</p><h2>Записи клиентов</h2></div>
                        <span className="demo-indicator">Пример записей</span>
                    </div>
                    <p className="muted small">Это календарь BOSH, используемый в кабинете мастера. Переключайте даты и нажимайте на записи для просмотра деталей.</p>
                    <AppointmentCalendar appointments={bookings} blocks={[]} hours={hours} timezone={timezone} date={date}
                        loading={false} onNavigate={setDate} renderDetails={appointment =>
                            <div className="demo-appointment-detail">
                                <p><strong>Клиент:</strong> {appointment.client_name}</p>
                                <p><strong>Услуга:</strong> {appointment.service_name_snapshot}</p>
                                <p><strong>Стоимость:</strong> {appointment.price_som} сом</p>
                                <p><strong>Статус:</strong> {appointment.status === "confirmed" ? "Подтверждена" : "Ожидает"}</p>
                                <p className="muted small">Демонстрационная запись. Редактирование отключено.</p>
                            </div>
                        } />
                </>}
                {section === "services" && <>
                    <p className="eyebrow">СПИСОК УСЛУГ</p><h2>Ваши услуги</h2>
                    <p className="muted small">В рабочем кабинете можно добавлять и редактировать услуги, указывать стоимость, длительность и доступность для записи.</p>
                    <div className="demo-list">{services.map(service => <article key={service.id} className="demo-list-item">
                        <div><strong>{service.name}</strong><p className="muted small">{service.duration_minutes} мин · Доступна для записи</p></div>
                        <strong>{service.price_som} сом</strong>
                    </article>)}</div>
                </>}
                {section === "clients" && <>
                    <p className="eyebrow">КЛИЕНТСКАЯ БАЗА</p><h2>Клиенты</h2>
                    <p className="muted small">В рабочем кабинете хранятся контакты, история посещений и заметки по клиентам.</p>
                    <div className="demo-list">{clientNames.map((name, index) =>
                        <article key={name} className="demo-list-item"><div><strong>{name}</strong><p className="muted small">{index === 0 ? "Стрижка" : "Укладка"} · пример записи</p></div><span className="demo-indicator">Демо</span></article>)}</div>
                </>}
                {section === "schedule" && <>
                    <p className="eyebrow">РАБОЧЕЕ ВРЕМЯ</p><h2>Моё расписание</h2>
                    <p className="muted small">Мастер настраивает дни, рабочие часы и перерывы. Отдельные интервалы можно закрыть для записи.</p>
                    <div className="demo-list">{["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"].map((day, i) =>
                        <div className="demo-list-item" key={day}><div><strong>{day}</strong><p className="muted small">{i === 6 ? "Выходной" : "Перерыв 12:00–13:00"}</p></div>
                        <span>{i === 6 ? "Закрыто" : "09:00–18:00"}</span></div>)}</div>
                    <div className="demo-example-note">В реальном кабинете рабочие часы и недоступное время можно редактировать.</div>
                </>}
                {section === "hot-slots" && <>
                    <p className="eyebrow">ПРОДВИЖЕНИЕ</p><h2>Горящие окошки</h2>
                    <p className="muted small">Выберите свободное время, создайте предложение и поделитесь сторис в Instagram или WhatsApp.</p>
                    <div className="demo-hot-layout">
                        <div className="demo-hot-config">
                            <strong>Свободно сегодня</strong>
                            <p>16:30 · Стрижка · 800 сом</p>
                            <p className="muted small">Скидка необязательна. Клиент перейдёт к записи по персональной ссылке.</p>
                            <button type="button" className="primary" onClick={() => setHotOfferCreated(true)}>{hotOfferCreated ? "Сторис создана" : "Создать пример сторис"}</button>
                        </div>
                        <div className="demo-hot-story"><span>BOSH</span><small>ЕСТЬ ВРЕМЯ ДЛЯ ВАС</small><strong>СВОБОДНОЕ ОКОШКО</strong><b>16:30</b><p>Стрижка · 800 сом</p><span className="demo-hot-story-action">Записаться</span></div>
                    </div>
                    {hotOfferCreated && <p className="demo-example-note" role="status">Пример готов. В настоящем кабинете сторис можно сохранить и опубликовать.</p>}
                </>}
                {section === "telegram" && <>
                    <p className="eyebrow">УВЕДОМЛЕНИЯ</p><h2>Telegram и напоминания</h2>
                    <p className="muted small">Новые записи и отмены приходят сразу. Клиенты могут получать напоминания за 24 и 2 часа, а мастер — за 30 минут и вечернюю сводку.</p>
                    <div className="demo-telegram-list">
                        <article className="demo-telegram-message"><span className="demo-indicator">Мастеру · новая запись</span><strong>BOSH · Новая запись</strong><p>Айдана · Стрижка<br />Сегодня, 10:00<br />800 сом</p><small>Пример уведомления</small></article>
                        <article className="demo-telegram-message"><span className="demo-indicator">Клиенту · напоминание</span><strong>BOSH · Скоро ваша запись</strong><p>До визита осталось 2 часа.<br />Мастер: Алина<br />Стрижка · 14:30</p><small>Пример уведомления</small></article>
                    </div>
                    <label className="demo-toggle"><input type="checkbox" checked={notificationsEnabled} onChange={e => setNotificationsEnabled(e.target.checked)} /> Напоминания и вечерняя сводка мастеру <span className="demo-indicator">Демо</span></label>
                    <p className="muted small">Настройки в этом примере не сохраняются и сообщения не отправляются.</p>
                </>}
                {section === "profile" && <>
                    <p className="eyebrow">ПУБЛИЧНАЯ СТРАНИЦА</p><h2>Моя страница</h2>
                    <p>В BOSH мастер получает собственную ссылку для записи клиентов. Клиент выбирает услугу и свободное время без регистрации.</p>
                    <p className="muted small">В кабинете можно добавить аватар, обложку, описание, адрес с 2ГИС, Instagram и WhatsApp. Страницу можно отправить клиентам по ссылке.</p>
                    <div className="demo-profile-preview"><div className="demo-profile-cover"></div><div className="demo-profile-avatar">Э</div><strong>Эрика · Мастер красоты</strong><p className="muted small">Стрижка · Укладка</p><div className="demo-profile-service">Стрижка <b>800 сом</b></div><div className="demo-profile-service">Укладка <b>400 сом</b></div><span className="primary">Выбрать время</span><small>Пример страницы · без реальной записи</small></div>
                    <Link className="ghost" href="/">Посмотреть пример записи</Link>
                </>}
                <div className="demo-footer">
                    <p>Все имена и записи демонстрационные. Действия не меняют реальные данные и не отправляют сообщения.</p>
                    <Link className="primary" href="/register">Начать бесплатно</Link>
                </div>
            </section>
        </main>
    </div>;
}
