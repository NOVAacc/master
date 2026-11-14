<?php
namespace App\Http\Controllers;
use App\Models\MasterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
final class TelegramMasterController extends Controller {
 public function connect(Request $r) {
  $bot=config('services.telegram.bot_username');
  abort_unless($bot&&preg_match('/^[A-Za-z0-9_]{5,32}$/D',$bot)&&config('services.telegram.bot_token'),503);
  $token=Str::random(40);
  MasterProfile::where('user_id',$r->user()->id)->update([
   'telegram_link_hash'=>hash('sha256',$token),'telegram_link_expires_at'=>now()->addMinutes(15)
  ]);
  return redirect()->away('https://t.me/'.$bot.'?start='.$token);
 }
 public function disconnect(Request $r) {
  MasterProfile::where('user_id',$r->user()->id)->update([
   'telegram_chat_id'=>null,'telegram_link_hash'=>null,'telegram_link_expires_at'=>null
  ]);
  return back();
 }
 public function toggle(Request $r) {
  $data=$r->validate(['enabled'=>['required','boolean']]);
  MasterProfile::where('user_id',$r->user()->id)->update(['telegram_alerts_enabled'=>$data['enabled']]);
  return back();
 }
 public function webhook(Request $r) {
  $secret=config('services.telegram.webhook_secret');
  abort_unless($secret&&hash_equals($secret,(string)$r->header('X-Telegram-Bot-Api-Secret-Token')),403);
  $txt=$r->input('message.text'); $chat=$r->input('message.chat');
  if (!is_string($txt)||!is_array($chat)||($chat['type']??null)!=='private'
    ||!preg_match('/^\/start (c?)([A-Za-z0-9]{40})$/D',$txt,$match)) return response()->json(['ok'=>true]);
  $chatId=(string)($chat['id']??'');
  if (!preg_match('/^[0-9]{1,20}$/D',$chatId)) return response()->json(['ok'=>true]);
  if ($match[1] === 'c') {
    $appointment = \App\Models\MasterAppointment::query()
      ->where('client_telegram_link_hash', hash('sha256', $match[2]))
      ->where('client_telegram_link_expires_at', '>', now())
      ->first();
    if (!$appointment) return response()->json(['ok'=>true]);
    $updated = \App\Models\MasterAppointment::whereKey($appointment->id)
      ->where('client_telegram_link_hash', hash('sha256', $match[2]))
      ->where('client_telegram_link_expires_at', '>', now())
      ->update([
        'client_telegram_chat_id' => $chatId,
        'client_telegram_link_hash' => null,
        'client_telegram_link_expires_at' => null,
      ]);
    if ($updated && config('services.telegram.bot_token')) {
      try {
        $appointment->refresh();
        $when = $appointment->starts_at->copy()->setTimezone($appointment->masterProfile->timezone)->format('d.m.Y H:i');
        Http::timeout(5)->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage',[
          'chat_id'=>$chatId,
          'text'=>"BOSH · Уведомления подключены.\nМастер: {$appointment->masterProfile->display_name}\nЗапись: {$when}\n".app(\App\Services\ClientBookingAlerts::class)->manageUrl($appointment),
          'disable_web_page_preview'=>true,
        ]);
      } catch (\Throwable $e) { /* Opt-in must persist even if welcome delivery fails. */ }
    }
    return response()->json(['ok'=>true]);
  }
  $count=DB::table('master_profiles')->where('telegram_link_hash',hash('sha256',$match[2]))
   ->where('telegram_link_expires_at','>',now())->update([
    'telegram_chat_id'=>$chatId,'telegram_link_hash'=>null,'telegram_link_expires_at'=>null,'updated_at'=>now()
   ]);
  if ($count&&config('services.telegram.bot_token')) {
   try {Http::timeout(5)->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage',[
    'chat_id'=>$chatId,'text'=>'BOSH подключён. Уведомления о записях будут приходить сюда.'
   ]);} catch (\Throwable $e) { /* Connection remains available for later notifications. */ }
  }
  return response()->json(['ok'=>true]);
 }
}