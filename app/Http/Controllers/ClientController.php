<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ClientController extends Controller
{
    private function scopedClient(Request $request, int $client): array
    {
        $master = MasterProfile::query()->where('user_id', $request->user()->id)->firstOrFail();
        $clientRow = DB::table('master_clients')
            ->where('master_profile_id', $master->id)
            ->where('id', $client)->whereNull('archived_at')->first();
        abort_unless($clientRow, 404);

        return [$master, $clientRow];
    }

    public function show(Request $request, int $client)
    {
        [$master, $customer] = $this->scopedClient($request, $client);
        $visits = $master->appointments()
            ->where('master_client_id', $customer->id)
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get(['id', 'starts_at', 'ends_at', 'status', 'price_som', 'service_name_snapshot']);

        $payload = [
            'client' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone_digits' => $customer->phone_digits,
                'notes' => $customer->notes,
            ],
            'timezone' => $master->timezone,
            'visits' => $visits,
            'visitsShown' => $visits->count(),
            'visitCount' => $master->appointments()->where('master_client_id', $customer->id)->where('status', 'completed')->count(),
        ];
        if ($request->expectsJson()) {
            return response()->json($payload)->header('Cache-Control', 'private, no-store');
        }
        return redirect()->route('dashboard');
    }

    public function candidates(Request $request, int $client)
    {
        [$master] = $this->scopedClient($request, $client);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = trim($data['q'] ?? '');
        $clients = DB::table('master_clients')
            ->where('master_profile_id', $master->id)
            ->whereNull('archived_at')
            ->where('id', '<>', $client)
            ->when($q !== '', function ($query) use ($q) {
                $digits = preg_replace('/\\D/', '', $q);
                $query->where(function ($query) use ($q, $digits) {
                    $query->where('name', 'like', '%'.addcslashes($q, '%_\\').'%');
                    if ($digits !== '') {
                        $query->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->orderBy('name')->limit(30)->get(['id', 'name', 'phone_digits']);
        return response()->json($clients)->header('Cache-Control', 'private, no-store');
    }

    public function merge(Request $request, int $client)
    {
        $data = $request->validate(['source_id' => ['required', 'integer', 'different:client']]);
        [$master] = $this->scopedClient($request, $client);
        $sourceId = (int) $data['source_id'];
        if ($sourceId === $client) {
            throw ValidationException::withMessages(['source_id' => 'Нельзя объединить карточку с самой собой.']);
        }

        DB::transaction(function () use ($master, $client, $sourceId) {
            // Match the booking lock order: master row first, then clients and appointments.
            DB::table('master_profiles')->where('id', $master->id)->lockForUpdate()->first();
            $rows = DB::table('master_clients')
                ->where('master_profile_id', $master->id)
                ->whereNull('archived_at')
                ->whereIn('id', [$client, $sourceId])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($rows->count() === 2, 404);
            $target = $rows->get($client);
            $source = $rows->get($sourceId);
            $targetNotes = trim((string) $target->notes);
            $sourceNotes = trim((string) $source->notes);
            $notes = $targetNotes;
            if ($sourceNotes !== '' && $sourceNotes !== $targetNotes) {
                $notes = $targetNotes === '' ? $sourceNotes : $targetNotes."\n\n".$sourceNotes;
            }
            if (mb_strlen($notes) > 2000) {
                throw ValidationException::withMessages(['source_id' => 'Заметки слишком длинные. Сократите их перед объединением.']);
            }
            DB::table('master_appointments')
                ->where('master_profile_id', $master->id)
                ->where('master_client_id', $sourceId)
                ->update(['master_client_id' => $client]);
            DB::table('master_clients')->where('id', $client)->where('master_profile_id', $master->id)
                ->update(['notes' => $notes === '' ? null : $notes, 'updated_at' => now()]);
            DB::table('master_clients')->where('id', $sourceId)->where('master_profile_id', $master->id)->delete();
        }, 3);
        return response()->json(['merged' => true])->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request, int $client)
    {
        [$master] = $this->scopedClient($request, $client);
        DB::transaction(function () use ($master, $client) {
            DB::table('master_profiles')->where('id', $master->id)->lockForUpdate()->first();
            $updated = DB::table('master_clients')
                ->where('master_profile_id', $master->id)
                ->where('id', $client)->whereNull('archived_at')
                ->update(['archived_at' => now(), 'updated_at' => now()]);
            abort_unless($updated === 1, 404);
        }, 3);
        return response()->json(['archived' => true])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, int $client)
    {
        [$master, $customer] = $this->scopedClient($request, $client);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone_digits' => ['sometimes', 'required', 'string', 'regex:/^[0-9]{7,20}$/', Rule::unique('master_clients', 'phone_digits')->where('master_profile_id', $master->id)->ignore($customer->id)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
        DB::table('master_clients')
            ->where('master_profile_id', $master->id)
            ->where('id', $customer->id)
            ->update(array_merge($data, ['updated_at' => now()]));

        if ($request->expectsJson()) {
            return response()->json(['saved' => true])->header('Cache-Control', 'private, no-store');
        }
        return back()->with('success', 'Данные клиента сохранены.');
    }
}
