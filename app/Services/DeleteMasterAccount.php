<?php

namespace App\Services;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteMasterAccount
{
    /** Delete only the selected account. Returns whether its private uploads were also removed. */
    public function delete(User $user, ?string $currentPassword = null): bool
    {
        $masterId = DB::transaction(function () use ($user, $currentPassword) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($currentPassword !== null && ! Hash::check($currentPassword, $lockedUser->password)) {
                throw ValidationException::withMessages(['deletion_password' => 'Неверный текущий пароль. Аккаунт не удалён.']);
            }
            $master = MasterProfile::where('user_id', $lockedUser->id)->lockForUpdate()->first();
            DB::table('sessions')->where('user_id', $lockedUser->id)->delete();
            if ($lockedUser->email !== null) {
                DB::table('password_reset_tokens')->where('email', $lockedUser->email)->delete();
            }
            // Foreign keys cascade through the master's CRM, bookings, groups and notifications.
            $lockedUser->delete();
            return $master?->id;
        }, 3);

        // Files are removed only after the database transaction commits.
        if ($masterId !== null) {
            try {
                if (! Storage::disk('local')->deleteDirectory('profile-images/'.$masterId)) {
                    throw new \RuntimeException('Could not remove deleted master upload directory: '.$masterId);
                }
            } catch (\Throwable $error) {
                report($error);
                return false;
            }
        }
        return true;
    }
}
