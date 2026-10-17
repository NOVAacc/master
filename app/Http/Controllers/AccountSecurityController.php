<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountSecurityController extends Controller
{
    public function password(Request $request)
    {
        $user = $request->user();
        $rules = ['password' => ['required', 'string', 'min:5', 'max:128', 'confirmed']];
        if ($user->password_initialized_at || $user->email) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $data = $request->validate($rules);
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_initialized_at' => now(),
            'failed_login_attempts' => 0,
            'login_locked_until' => null,
            'remember_token' => Str::random(60),
        ])->save();
        return back()->with('success', 'Пароль сохранён. Теперь входите по номеру телефона и паролю.');
    }

    private function ensureMailer(): void
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['email' => 'Отправка писем пока не подключена. Попробуйте позже.']);
        }
    }

    private function sendVerification(User $user): void
    {
        $this->ensureMailer();
        $url = URL::temporarySignedRoute('security.email.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1((string) $user->email),
        ]);
        Mail::raw("Подтвердите адрес электронной почты для BOSH. Ссылка действует 60 минут:\n".$url, function ($message) use ($user) {
            $message->to($user->email)->subject('BOSH — подтверждение почты');
        });
    }

    public function email(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);
        $email = strtolower(trim($data['email']));
        $this->ensureMailer();
        $user = $request->user();
        if ($user->email !== $email) {
            $user->forceFill(['email' => $email, 'email_verified_at' => null])->save();
        }
        $this->sendVerification($user);
        return back()->with('success', 'На указанную почту отправлена ссылка подтверждения.');
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        if (!$user->email || $user->email_verified_at) return back();
        $this->sendVerification($user);
        return back()->with('success', 'Письмо подтверждения отправлено повторно.');
    }

    public function verify(Request $request, User $user, string $hash)
    {
        abort_unless(hash_equals(sha1((string) $user->email), $hash), 403);
        $user->forceFill(['email_verified_at' => now()])->save();
        return redirect()->route('login')->with('success', 'Почта подтверждена. Теперь можно восстановить пароль через email.');
    }

    public function forgotPage() { return Inertia::render('PasswordRecovery', ['reset' => false]); }

    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc']]);
        $this->ensureMailer();
        $user = User::where('email', strtolower(trim($data['email'])))->whereNotNull('email_verified_at')->first();
        if ($user) Password::sendResetLink(['email' => $user->email]);
        return back()->with('success', 'Если почта подтверждена и привязана к аккаунту, вы получите ссылку для сброса пароля.');
    }

    public function resetPage(Request $request, string $token)
    {
        return Inertia::render('PasswordRecovery', ['reset' => true, 'token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string', 'min:5', 'max:128', 'confirmed'],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        if (!User::where('email', $data['email'])->whereNotNull('email_verified_at')->exists()) {
            throw ValidationException::withMessages(['email' => 'Не удалось восстановить пароль.']);
        }
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'password_initialized_at' => now(),
                'failed_login_attempts' => 0,
                'login_locked_until' => null,
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Ссылка недействительна или истекла. Запросите новую.']);
        }
        return redirect()->route('login')->with('success', 'Пароль обновлён. Войдите по телефону и новому паролю.');
    }
}
