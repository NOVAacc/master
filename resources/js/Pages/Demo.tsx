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
const clientNames = ["Демо-клиент 1", "Демо-клиент 2"];

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
    ];
}

export default function Demo() {
    const today = localDay(new Date(), timezone);
    const dayOfWeek = new Date(`${today}T12:00:00Z`).getUTCDay();
    const exampleDay = addDays(today, dayOfWeek === 0 ? 1 : 0);
    const [date, setDate] = useState(exampleDay);
    const [section, setSection] = useState<"appointments" | "services" | "clients" | "profile">("appointments");
    const bookings = useMemo(() => exampleAppointments(exampleDay), [exampleDay]);

    return <div className="demo-page">
        <Head title="Демо кабинета мастера — BOSH">
            <meta name="description" content="Посмотрите, как устроены календарь, записи и услуги в BOSH. Демонстрация без регистрации и изменения данных." />
        </Head>
        <header className="demo-topbar">
            <Brand />
            <span className="demo-indicator">Демонстрация · только просмотр</span>
            <Link className="primary" href="/register">Создать свой кабинет</Link>
        </header>
        <main className="demo-layout">
            <aside className="demo-sidebar panel">
                <h1>Кабинет мастера</h1>
                <p className="muted small">Пример заполненного кабинета. Здесь нет настоящих клиентов и записей.</p>
                <nav aria-label="Разделы демонстрации">
                    {([
                        ["appointments", "Записи"],
                        ["services", "Услуги"],
                        ["clients", "Клиенты"],
                        ["profile", "Моя страница"],
                    ] as const).map(([id, title]) =>
                        <button key={id} type="button" className={section === id ? "active" : ""} aria-current={section === id ? "page" : undefined}
                            onClick={() => setSection(id)}>{title}</button>
                    )}
                </nav>
                <p className="muted small demo-side-note">В настоящем кабинете также доступны настройка расписания, быстрая запись, уведомления и создание сторис. Здесь эти действия не выполняются.</p>
            </aside>
            <section className="demo-content panel">
                {section === "appointments" && <>
                    <div className="row between wrap">
                        <div><p className="eyebrow">РАБОЧИЙ КАЛЕНДАРЬ</p><h2>Записи клиентов</h2></div>
                        <span className="demo-indicator">Пример записей</span>
                    </div>
                    <p className="muted small">Это календарь, который используется в BOSH. Нажмите на запись, чтобы посмотреть её данные. Даты можно переключать.</p>
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
                {section === "profile" && <>
                    <p className="eyebrow">ПУБЛИЧНАЯ СТРАНИЦА</p><h2>Моя страница</h2>
                    <p>В BOSH мастер получает собственную ссылку для записи клиентов. Клиент выбирает услугу и свободное время без регистрации.</p>
                    <p className="muted small">В кабинете можно добавить фотографию, описание, адрес, ссылку на 2ГИС и Instagram. Это необязательные настройки.</p>
                    <p className="muted small">Для просмотра оформления публичной записи используйте интерактивный пример на главной странице BOSH.</p>
                    <Link className="ghost" href="/">Посмотреть пример записи</Link>
                </>}
                <div className="demo-footer">
                    <p>Все данные вымышлены. Эта демонстрация ничего не сохраняет и не отправляет сообщения.</p>
                    <Link className="primary" href="/register">Начать бесплатно</Link>
                </div>
            </section>
        </main>
    </div>;
}
