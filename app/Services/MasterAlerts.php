<?php
namespace App\Services;
use App\Models\MasterAppointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
final class MasterAlerts {
 public function record(MasterAppointment $a,string $kind): void {
  try {
   $m=$a->masterProfile;
   $titles=['new'=>'Новая запись','cancelled'=>'Запись отменена','rescheduled'=>'Запись перенесена','confirmed'=>'Запись подтверждена'];
   $title=$titles[$kind]??'Изменение записи';
   $when=$a->starts_at->copy()->setTimezone($m->timezone)->format('d.m.Y H:i');
   $body="Клиент: {$a->client_name}\nУслуга: {$a->service_name_snapshot}\nДата: {$when}\nСтоимость: {$a->price_som} сом";
   $inserted=DB::table('master_alerts')->insertOrIgnore([
    'master_profile_id'=>$m->id,'master_appointment_id'=>$a->id,
    'event_key'=>"{$a->id}:{$a->revision}:{$kind}",'kind'=>$kind,'title'=>$title,
    'message'=>$body,'created_at'=>now(),'updated_at'=>now()
   ]);
   $token=config('services.telegram.bot_token');
   if (!$inserted||!$m->telegram_chat_id||!$m->telegram_alerts_enabled||!$token) return;
   $r=Http::connectTimeout(2)->timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage',[
    'chat_id'=>$m->telegram_chat_id,'text'=>"BOSH · {$title}\n{$body}",
    'reply_markup'=>['inline_keyboard'=>[[['text'=>'Открыть BOSH','url'=>route('dashboard')]]]]
   ]);
   if (!$r->successful()) Log::warning('Telegram notification rejected',['master_id'=>$m->id,'status'=>$r->status()]);
  } catch (\Throwable $e) {
   Log::warning('Master alert failed',['appointment_id'=>$a->id,'error'=>get_class($e)]);
  }
 }
}