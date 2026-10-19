<?php

namespace Database\Seeders;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoMastersSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = json_decode(file_get_contents(database_path('demo/profiles.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($profiles) {
            foreach ($profiles as $item) {
                $email = $item['slug'].'@example.invalid';
                $existing = MasterProfile::where('slug', $item['slug'])->first();
                if ($existing) {
                    if (! $existing->is_demo || $existing->user->email !== $email) {
                        throw new \RuntimeException('Demo profile slug is already in use.');
                    }
                    continue;
                }
                if (User::where('email', $email)->exists()) {
                    throw new \RuntimeException('Demo account already exists without its profile.');
                }
                $user = User::create(['name' => $item['name'].' · демо', 'email' => $email,
                    'password' => Hash::make(Str::random(64))]);
                $master = MasterProfile::create([
                    'user_id' => $user->id, 'slug' => $item['slug'], 'display_name' => $item['name'],
                    'specialty' => $item['specialty'], 'bio' => "Демонстрационный профиль. Мастер вымышленный; стоковая фотография используется как иллюстрация. Запись недоступна.\n\n".$item['bio'],
                    'address' => 'Бишкек · демо-студия', 'timezone' => 'Asia/Bishkek',
                    'booking_enabled' => false, 'is_demo' => true, 'auto_confirm' => false,
                    'whatsapp_notifications' => false, 'profile_theme' => $item['theme'],
                ]);
                $photo = file_get_contents(database_path('demo/'.$item['photo']));
                if ($photo === false || ! str_starts_with($photo, "\xFF\xD8\xFF")) {
                    throw new \RuntimeException('Demo photograph is missing or invalid.');
                }
                $path = 'profile-images/'.$master->id.'/'.sha1($photo).'.jpg';
                if (! Storage::disk('local')->put($path, $photo)) {
                    throw new \RuntimeException('Cannot store demo photograph.');
                }
                $master->update(['avatar_path' => $path]);
                foreach ($item['services'] as $order => $service) {
                    $master->services()->create(['name' => $service['name'], 'description' => $service['description'],
                        'duration_minutes' => $service['minutes'], 'price_som' => $service['price'],
                        'is_active' => true, 'sort_order' => $order + 1]);
                }
                foreach (range(1, 6) as $weekday) {
                    DB::table('master_working_hours')->insert(['master_profile_id' => $master->id,
                        'weekday' => $weekday, 'starts_at' => '10:00:00', 'ends_at' => '19:00:00',
                        'break_starts_at' => '12:00:00', 'break_ends_at' => '13:00:00',
                        'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }
}
