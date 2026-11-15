<?php
namespace App\Http\Controllers;

use App\Services\ClientIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

final class ClientBookingsController extends Controller
{
    private function account(Request $request): object
    {
        return app(ClientIdentity::class)->account($request) ?? abort(403);
    }

    public function index(Request $request)
    {
        $account = $this->account($request);
        $bookings = DB::table('master_appointments as a')
            ->join('master_profiles as m', 'm.id', '=', 'a.master_profile_id')
            ->where('a.client_account_id', $account->id)
            ->where('a.starts_at', '>=', now('UTC')->subDays(30))
            ->orderBy('a.starts_at')->limit(100)
            ->get(['a.id', 'a.status', 'a.revision', 'a.starts_at', 'a.ends_at',
                'a.service_name_snapshot', 'a.price_som', 'm.display_name as master',
                'm.timezone'])
            ->map(fn ($booking) => [
                'id' => $booking->id,
                'status' => $booking->status,
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
                'service' => $booking->service_name_snapshot,
                'price' => $booking->price_som,
                'master' => $booking->master,
                'timezone' => $booking->timezone,
                'manageUrl' => URL::temporarySignedRoute('booking.manage', now()->addDays(30), [
                    'appointment' => $booking->id, 'revision' => $booking->revision,
                ]),
            ]);
        return Inertia::render('ClientBookings', [
            'bookings' => $bookings,
            'notificationsEnabled' => (bool) $account->notifications_enabled,
        ]);
    }

    public function notifications(Request $request)
    {
        $account = $this->account($request);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        DB::table('client_accounts')->where('id', $account->id)->update([
            'notifications_enabled' => (bool) $data['enabled'], 'updated_at' => now(),
        ]);
        return back();
    }

    public function logout(Request $request)
    {
        app(ClientIdentity::class)->logout($request);
        return redirect('/');
    }
}
