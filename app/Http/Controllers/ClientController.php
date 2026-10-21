<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        return Inertia::render('ClientDetails', $payload);
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
