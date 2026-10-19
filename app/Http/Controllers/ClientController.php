<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class ClientController extends Controller
{
    private function scopedClient(Request $request, int $client): array
    {
        $master = MasterProfile::query()->where('user_id', $request->user()->id)->firstOrFail();
        $clientRow = DB::table('master_clients')
            ->where('master_profile_id', $master->id)
            ->where('id', $client)->first();
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

        return Inertia::render('ClientDetails', [
            'client' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone_digits' => $customer->phone_digits,
                'notes' => $customer->notes,
            ],
            'timezone' => $master->timezone,
            'visits' => $visits,
            'visitsShown' => $visits->count(),
        ]);
    }

    public function update(Request $request, int $client)
    {
        [$master, $customer] = $this->scopedClient($request, $client);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::table('master_clients')
            ->where('master_profile_id', $master->id)
            ->where('id', $customer->id)
            ->update(['notes' => $data['notes'] ?? null, 'updated_at' => now()]);

        return back()->with('success', 'Заметка о клиенте сохранена.');
    }
}
