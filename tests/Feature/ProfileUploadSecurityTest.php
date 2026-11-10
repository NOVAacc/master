<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function master(): MasterProfile
    {
        $user = User::factory()->create();
        return MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'secure-image-master',
            'display_name' => 'Мастер',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
            'auto_confirm' => false,
        ]);
    }

    private function fields(MasterProfile $master): array
    {
        return [
            'slug' => $master->slug,
            'display_name' => $master->display_name,
            'timezone' => $master->timezone,
            'booking_enabled' => true,
            'auto_confirm' => false,
        ];
    }

    public function test_upload_requires_login(): void
    {
        $this->post('/profile', [])->assertRedirect('/login');
    }

    public function test_avatar_and_cover_are_reencoded_and_attached_only_to_owner(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD required to decode/re-encode images');
        }
        Storage::fake('local');
        $master = $this->master();
        $avatar = UploadedFile::fake()->image('portrait.jpg', 200, 200);
        file_put_contents($avatar->getRealPath(), "\n<?php echo 'INJECTED-CODE'; ?>", FILE_APPEND);
        $cover = UploadedFile::fake()->image('background.png', 400, 200);
        $this->actingAs($master->user)->post('/profile', [
            ...$this->fields($master),
            'avatar' => $avatar,
            'cover' => $cover,
        ])->assertSessionHasNoErrors();

        $master->refresh();
        $this->assertNotNull($master->avatar_path);
        $this->assertNotNull($master->cover_path);
        Storage::disk('local')->assertExists([$master->avatar_path, $master->cover_path]);
        $this->assertStringNotContainsString('INJECTED-CODE', Storage::disk('local')->get($master->avatar_path));
        $this->assertSame(IMAGETYPE_JPEG, getimagesize(Storage::disk('local')->path($master->avatar_path))[2]);
        $this->assertSame(IMAGETYPE_PNG, getimagesize(Storage::disk('local')->path($master->cover_path))[2]);
        $response = $this->get(route('master.image', [
            'master' => $master->slug,
            'kind' => 'avatar',
            'version' => pathinfo($master->avatar_path, PATHINFO_FILENAME),
        ]));
        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_script_files_and_double_extensions_cannot_be_uploaded(): void
    {
        Storage::fake('local');
        $master = $this->master();
        $this->actingAs($master->user)->post('/profile', [
            ...$this->fields($master),
            'avatar' => UploadedFile::fake()->createWithContent('portrait.jpg', '<?php echo "oops"; ?>'),
        ])->assertSessionHasErrors('avatar');
        $this->post('/profile', [
            ...$this->fields($master),
            'avatar' => UploadedFile::fake()->image('portrait.php.jpg', 60, 60),
        ])->assertSessionHasErrors('avatar');
        $this->assertNull($master->fresh()->avatar_path);
    }

    public function test_old_work_gallery_uploads_and_public_access_are_disabled(): void
    {
        Storage::fake('local');
        $master = $this->master();
        $this->actingAs($master->user)->post('/profile', [
            ...$this->fields($master),
            'work1' => UploadedFile::fake()->image('work.jpg', 100, 100),
        ])->assertSessionHasErrors('work1');
        $this->get('/m/'.$master->slug.'/image/work1/'.str_repeat('a', 40))->assertNotFound();
    }
}
