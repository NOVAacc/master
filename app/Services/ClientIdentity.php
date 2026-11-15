<?php
namespace App\Services;

use App\Models\MasterAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

final class ClientIdentity
{
    private const COOKIE = 'bosh_client_session';

    public function account(Request $request): ?object
    {
        $token = $request->cookie(self::COOKIE);
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return null;

        return DB::table('client_account_sessions as s')
            ->join('client_accounts as c', 'c.id', '=', 's.client_account_id')
            ->where('s.token_hash', hash('sha256', $token))
            ->where('s.expires_at', '>', now())
            ->first(['c.id', 'c.telegram_chat_id', 'c.notifications_enabled']);
    }

    public function claimBrowser(Request $request): bool
    {
        $claims = $request->session()->get('client_telegram_pending', []);
        if (!is_array($claims) || !$claims) return $this->account($request) !== null;
        foreach ($claims as $appointmentId => $secret) {
            if (!ctype_digit((string) $appointmentId) || !is_string($secret)) continue;
            $booking = MasterAppointment::query()->whereKey((int)$appointmentId)
                ->where('client_telegram_browser_claim_hash', hash('sha256', $secret))
                ->whereNotNull('client_account_id')->first();
            if (!$booking) continue;

            // One browser can retain only one client identity at a time.
            $current = $this->account($request);
            if ($current && (int) $current->id !== (int) $booking->client_account_id) continue;

            $token = bin2hex(random_bytes(32));
            DB::transaction(function () use ($booking, $token) {
                $updated = DB::table('master_appointments')->where('id', $booking->id)
                    ->where('client_telegram_browser_claim_hash', $booking->client_telegram_browser_claim_hash)
                    ->update(['client_telegram_browser_claim_hash' => null]);
                if (!$updated) return;
                DB::table('client_account_sessions')->insert([
                    'client_account_id' => $booking->client_account_id, 'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            // Session cookie is HttpOnly, Secure, SameSite=Lax and encrypted by Laravel.
            $hasToken = DB::table('client_account_sessions')->where('token_hash', hash('sha256', $token))->exists();
            if (!$hasToken) continue;
            Cookie::queue(cookie(self::COOKIE, $token, 60 * 24 * 365, '/', null, true, true, false, 'lax'));
            $request->session()->forget('client_telegram_pending');
            return true;
        }
        return $this->account($request) !== null;
    }

    public function logout(Request $request): void
    {
        $token = $request->cookie(self::COOKIE);
        if (is_string($token)) DB::table('client_account_sessions')->where('token_hash', hash('sha256', $token))->delete();
        Cookie::queue(Cookie::forget(self::COOKIE));
        $request->session()->forget('client_telegram_pending');
    }
}
