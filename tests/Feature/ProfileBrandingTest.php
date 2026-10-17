<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function profile(): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'profile-photo', 'display_name' => 'Айзада', 'timezone' => 'Asia/Bishkek']);

        return [$user, $master];
    }

    private function fields(): array
    {
        return ['display_name' => 'Айзада', 'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => false];
    }

    private function image(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j7F8AAAAASUVORK5CYII='));
    }

    public function test_avatar_and_cover_are_persisted_and_publicly_served_as_images_without_exposing_paths(): void
    {
        Storage::fake('local');
        [$user, $master] = $this->profile();
        $this->actingAs($user)->post('/profile', [...$this->fields(), 'avatar' => $this->image(), 'cover' => $this->image('cover.png'),
            'tagline' => 'Внимание к деталям', 'instagram_username' => 'https://www.instagram.com/aizada.lashes/?igsh=example', 'profile_theme' => 'sage'])
            ->assertSessionHasNoErrors();
        $master->refresh();
        Storage::disk('local')->assertExists($master->avatar_path);
        Storage::disk('local')->assertExists($master->cover_path);
        $this->assertSame('aizada.lashes', $master->instagram_username);
        $this->assertSame('https://www.instagram.com/aizada.lashes/', $master->instagram_url);
        $this->assertArrayNotHasKey('avatar_path', $master->toArray());
        $this->get($master->avatar_url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($master->cover_url)->assertOk();
        $this->get('/m/profile-photo')->assertInertia(fn (Assert $page) => $page
            ->where('master.avatar_url', $master->avatar_url)->where('master.cover_url', $master->cover_url)
            ->where('master.instagram_url', 'https://www.instagram.com/aizada.lashes/')->where('master.tagline', 'Внимание к деталям')
            ->missing('master.avatar_path')->missing('master.cover_path'));
    }

    public function test_replacing_and_removing_images_keeps_other_profile_files_intact(): void
    {
        Storage::fake('local');
        [$user, $master] = $this->profile();
        $this->actingAs($user)->post('/profile', [...$this->fields(), 'avatar' => $this->image(), 'cover' => $this->image('cover.png')])->assertSessionHasNoErrors();
        $master->refresh();
        $old = $master->avatar_path;
        $oldUrl = $master->avatar_url;
        $cover = $master->cover_path;
        $this->post('/profile', [...$this->fields(), 'avatar' => $this->image('new.png')])->assertSessionHasNoErrors();
        $master->refresh();
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($master->avatar_path);
        Storage::disk('local')->assertExists($cover);
        $this->get($oldUrl)->assertNotFound();
        $current = $master->avatar_path;
        $this->post('/profile', [...$this->fields(), 'remove_avatar' => true])->assertSessionHasNoErrors();
        $this->assertNull($master->fresh()->avatar_url);
        Storage::disk('local')->assertMissing($current);
        Storage::disk('local')->assertExists($cover);
    }

    public function test_invalid_uploads_and_instagram_urls_are_rejected_without_partial_profile_updates(): void
    {
        Storage::fake('local');
        [$user, $master] = $this->profile();
        $this->actingAs($user)->post('/profile', [...$this->fields(), 'display_name' => 'Changed', 'avatar' => UploadedFile::fake()->createWithContent('file.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('avatar');
        $this->assertSame('Айзада', $master->fresh()->display_name);
        $this->post('/profile', [...$this->fields(), 'instagram_username' => 'https://instagram.com.evil.test/aizada/'])->assertSessionHasErrors('instagram_username');
        $this->post('/profile', [...$this->fields(), 'instagram_username' => '@aizada.lashes'])->assertSessionHasNoErrors();
        $this->assertSame('aizada.lashes', $master->fresh()->instagram_username);
        $this->post('/profile', [...$this->fields(), 'instagram_username' => ''])->assertSessionHasNoErrors();
        $this->assertNull($master->fresh()->instagram_url);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_each_master_can_only_change_their_own_profile_and_image_urls_cannot_be_mixed(): void
    {
        Storage::fake('local');
        [$user, $first] = $this->profile();
        $other = User::factory()->create();
        $second = MasterProfile::create(['user_id' => $other->id, 'slug' => 'other-photo', 'display_name' => 'Другой', 'timezone' => 'Asia/Bishkek']);
        $this->actingAs($user)->post('/profile', [...$this->fields(), 'id' => $second->id, 'avatar_path' => 'arbitrary-file', 'avatar' => $this->image()])->assertSessionHasNoErrors();
        $first->refresh();
        $this->assertNull($second->fresh()->avatar_path);
        $version = pathinfo($first->avatar_path, PATHINFO_FILENAME);
        $this->get('/m/other-photo/image/avatar/'.$version)->assertNotFound();
        $this->get('/m/profile-photo/image/cover/'.$version)->assertNotFound();
        $this->get('/m/profile-photo/image/avatar/not-a-version')->assertNotFound();
    }

    public function test_upload_allowlist_rejects_disguises_arrays_and_oversized_files_atomically(): void
    {
        Storage::fake('local');
        [$user, $master] = $this->profile();
        $this->actingAs($user);
        foreach (['payload.php', 'payload.php.png', 'wrong.jpg', 'file.html'] as $name) {
            $this->post('/profile', [...$this->fields(), 'display_name' => 'Changed', 'avatar' => $this->image($name)])
                ->assertSessionHasErrors('avatar');
        }
        $this->post('/profile', [...$this->fields(), 'avatar' => UploadedFile::fake()->create('large.png', 2049, 'image/png')])->assertSessionHasErrors('avatar');
        $this->post('/profile', [...$this->fields(), 'avatar' => [$this->image()]])->assertSessionHasErrors('avatar');
        $this->post('/profile', [...$this->fields(), 'attachment' => $this->image()])->assertSessionHasErrors('avatar');
        $this->post('/services', ['attachment' => $this->image()])->assertStatus(422);
        $this->assertSame('Айзада', $master->fresh()->display_name);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_image_delivery_rejects_traversal_and_symlink_even_with_tampered_database_path(): void
    {
        Storage::fake('local');
        [$user, $master] = $this->profile();
        $disk = Storage::disk('local');
        $version = str_repeat('a', 40);
        $disk->put($version.'.png', $this->image()->getContent());
        $master->update(['avatar_path' => 'profile-images/'.$master->id.'/../../'.$version.'.png']);
        $this->get($master->avatar_url)->assertNotFound();
        $disk->makeDirectory('profile-images/'.$master->id);
        $path = 'profile-images/'.$master->id.'/'.$version.'.png';
        symlink($disk->path($version.'.png'), $disk->path($path));
        $master->update(['avatar_path' => $path]);
        $this->get($master->avatar_url)->assertNotFound();
        $this->actingAs($user)->post('/profile', [...$this->fields(), 'remove_avatar' => true])->assertSessionHasNoErrors();
        $disk->assertExists($version.'.png');
    }

    public function test_upload_requires_authentication_and_profile_requests_are_rate_limited(): void
    {
        Storage::fake('local');
        [$user] = $this->profile();
        $this->post('/profile', [...$this->fields(), 'avatar' => $this->image()])->assertRedirect('/login');
        $this->actingAs($user);
        for ($i = 0; $i < 20; $i++) {
            $this->post('/profile', $this->fields())->assertSessionHasNoErrors();
        }
        $this->post('/profile', [...$this->fields(), 'avatar' => $this->image()])->assertStatus(429);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
