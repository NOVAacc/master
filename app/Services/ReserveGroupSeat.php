<?php

namespace App\Services;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReserveGroupSeat
{
    public function reserve(int $sessionId, string $name, string $rawPhone): void
    {
        $digits = WhatsApp::phone($rawPhone);
        if (! $digits) {
            throw ValidationException::withMessages([
                'client_phone' => 'Введите корректный номер телефона.',
            ]);
        }

        DB::transaction(function () use ($sessionId, $name, $rawPhone, $digits) {
            // All mutators acquire the owner row first to serialize a master's
            // calendar, followed by the group session, then participant rows.
            $ownerId = DB::table('master_group_sessions')
                ->where('id', $sessionId)->value('master_profile_id');
            abort_unless($ownerId, 404);

            $master = MasterProfile::whereKey($ownerId)->lockForUpdate()->firstOrFail();
            $row = DB::table('master_group_sessions')
                ->where('id', $sessionId)
                ->where('master_profile_id', $master->id)
                ->lockForUpdate()->firstOrFail();

            if (! $master->booking_enabled || $master->is_demo
                || $row->status !== 'open'
                || $row->reserved_seats >= $row->capacity
                || CarbonImmutable::parse($row->starts_at, 'UTC')->isPast()) {
                throw ValidationException::withMessages(['booking' => 'Нет свободных мест.']);
            }
            if (DB::table('master_group_participants')
                ->where('master_group_session_id', $sessionId)
                ->where('phone_digits', $digits)
                ->where('client_name', $name)->exists()) {
                throw ValidationException::withMessages([
                    'client_phone' => 'Этот участник уже записан на занятие.',
                ]);
            }

            DB::table('master_group_participants')->insert([
                'master_group_session_id' => $sessionId,
                'client_name' => $name,
                'client_phone' => $rawPhone,
                'phone_digits' => $digits,
                'status' => 'confirmed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Even if a future writer fails to honor the shared master lock,
            // the database will never increment the counter beyond capacity.
            $claimed = DB::table('master_group_sessions')
                ->where('id', $sessionId)
                ->where('status', 'open')
                ->whereColumn('reserved_seats', '<', 'capacity')
                ->update([
                    'reserved_seats' => DB::raw('reserved_seats + 1'),
                    'updated_at' => now(),
                ]);
            if ($claimed !== 1) {
                // Transaction rollback removes the participant we inserted.
                throw ValidationException::withMessages(['booking' => 'Нет свободных мест.']);
            }
        }, 3);
    }
}
