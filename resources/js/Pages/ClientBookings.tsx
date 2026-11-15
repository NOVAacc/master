import { Head, router } from "@inertiajs/react";
import { useI18n } from "../i18n";
import { Brand } from "../Shared";

type Booking = {id:number;status:string;starts_at:string;ends_at:string;service:string;price:number;master:string;timezone:string;manageUrl:string};
export default function ClientBookings({bookings,notificationsEnabled}: {bookings:Booking[];notificationsEnabled:boolean}) {
 const {t:tr,locale}=useI18n();
 const date = (b:Booking) => new Intl.DateTimeFormat(locale === "ky" ? "ky-KG" : "ru-RU", {
   timeZone:b.timezone, dateStyle:"medium",timeStyle:"short"
 }).format(new Date(b.starts_at+"Z"));
 return <>
  <Head title={tr("Мои записи · BOSH")} />
  <main className="container" style={{maxWidth:760,margin:"24px auto",padding:"16px",display:"grid",gap:18}}>
   <Brand />
   <h1>{tr("Мои записи")}</h1>
   <p className="muted">{tr("Все записи, которые вы подтвердили через Telegram или сделали в этом браузере после подключения.")}</p>
   <section className="panel" style={{padding:20,display:"grid",gap:12}}>
    <h2>{tr("Уведомления в Telegram")}</h2>
    <label className="check"><input type="checkbox" checked={notificationsEnabled} onChange={e=>router.post("/my-bookings/notifications",{enabled:e.target.checked},{preserveScroll:true})} />
     {tr("Получать сообщения о подтверждениях, переносах и отменах")}
    </label>
   </section>
   <section style={{display:"grid",gap:12}}>
    {bookings.map(b=><article key={b.id} className="panel" style={{padding:18,display:"grid",gap:8}}>
     <strong>{b.master}</strong>
     <span>{b.service}</span>
     <span>{date(b)} · {Number(b.price).toLocaleString("ru-RU")} {tr("сом")}</span>
     <span className="muted small">{tr(b.status==="cancelled"?"Отменена":b.status==="confirmed"?"Подтверждена":"Ожидает подтверждения")}</span>
     <a className="button" href={b.manageUrl} referrerPolicy="no-referrer">{tr("Открыть запись")}</a>
    </article>)}
    {bookings.length===0 && <p className="muted">{tr("Записей пока нет.")}</p>}
   </section>
   <button type="button" className="ghost" onClick={()=>router.post("/my-bookings/logout")}>{tr("Отключить это устройство")}</button>
  </main>
 </>;
}
