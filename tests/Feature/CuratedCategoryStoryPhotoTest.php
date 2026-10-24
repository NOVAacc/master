<?php

namespace Tests\Feature;

use App\Http\Controllers\CategoryStoryTemplateController;
use App\Models\Administrator;
use App\Models\SiteSetting;
use App\Support\CategoryStoryPhotoLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CuratedCategoryStoryPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    private function configData(): array
    {
        return [
            'font' => 'classic', 'text_color' => '#171717',
            'scale' => 100, 'shade' => 10,
            'image_position' => 'center', 'image_horizontal' => 'center',
            'image_zoom' => 100,
        ];
    }

    public function test_curated_nail_collection_has_eight_unique_verified_source_references(): void
    {
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'nails'));
        $ids = [];
        foreach (['editorial','proof','premium','warm','urban','soft','promo','seasonal'] as $role) {
            $photo = CategoryStoryPhotoLibrary::get('beauty', 'nails', $role);
            $this->assertNotNull($photo);
            $this->assertSame('Pexels Free', $photo['license']);
            $this->assertStringStartsWith('https://images.pexels.com/photos/', $photo['url']);
            $this->assertStringStartsWith('https://www.pexels.com/photo/', $photo['page']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $photo['version']);
            $ids[] = $photo['id'];
        }
        $this->assertCount(8, array_unique($ids));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'barber'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'yoga'));
        $allIds = [];
        foreach (CategoryStoryPhotoLibrary::COLLECTIONS as $industry => $categories) {
            foreach ($categories as $activity => $photos) {
                $this->assertCount(8, $photos, "Missing roles for {$industry}/{$activity}");
                foreach (array_keys(\App\Support\CategoryStoryCatalog::ROLES) as $role) {
                    $this->assertNotNull(CategoryStoryPhotoLibrary::get($industry, $activity, $role));
                    $allIds[] = CategoryStoryPhotoLibrary::get($industry, $activity, $role)['id'];
                }
            }
        }
        $this->assertCount(24, $allIds);
        $this->assertCount(24, array_unique($allIds));
        $this->assertNull(CategoryStoryPhotoLibrary::get('medicine', 'dentistry', 'editorial'));
    }

    public function test_curated_image_is_proxied_with_validation_and_cached_without_outbound_repeats(): void
    {
        Storage::fake('local');
        $this->admin();
        $image = UploadedFile::fake()->image('photo.jpg', 1080, 1920);
        $jpg = file_get_contents($image->getPathname());
        Http::fake(['images.pexels.com/*' => Http::response($jpg, 200, ['Content-Type' => 'image/jpeg'])]);
        $config = CategoryStoryTemplateController::config('beauty', 'nails', 'proof');
        $this->assertSame('curated', $config['photo_origin']);
        $this->assertFalse($config['has_photo']);
        $this->assertNotNull($config['image_url']);
        $this->get($config['image_url'])->assertOk()->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($config['image_url'])->assertOk();
        Http::assertSentCount(1);
        $this->assertCount(1, Storage::disk('local')->files('category-story-curated'));
        $this->get(str_replace(basename($config['image_url']), str_repeat('a', 40), $config['image_url']))
            ->assertNotFound();
    }

    public function test_custom_upload_overrides_curated_photo_and_removal_restores_it(): void
    {
        Storage::fake('local');
        $this->admin();
        $path = '/admin/categories/beauty/nails/stories/proof';
        $this->post($path, [...$this->configData(),
            'image' => UploadedFile::fake()->image('mine.png', 1080, 1920),
        ])->assertRedirect($path);
        $custom = CategoryStoryTemplateController::config('beauty', 'nails', 'proof');
        $this->assertTrue($custom['has_photo']);
        $this->assertSame('uploaded', $custom['photo_origin']);

        $this->post($path, [...$this->configData(), 'remove_image' => 1])->assertRedirect($path);
        $restored = CategoryStoryTemplateController::config('beauty', 'nails', 'proof');
        $this->assertFalse($restored['has_photo']);
        $this->assertSame('curated', $restored['photo_origin']);
        $this->assertNotSame($custom['image_url'], $restored['image_url']);
        $this->assertDatabaseHas('site_settings', ['key' => 'category-story.beauty.nails.proof']);
    }

    public function test_remote_failure_never_exposes_remote_error_or_changes_existing_uploaded_photos(): void
    {
        Storage::fake('local');
        Http::fake(['images.pexels.com/*' => Http::response('Service unavailable', 503)]);
        $this->admin();
        $config = CategoryStoryTemplateController::config('beauty', 'nails', 'editorial');
        $this->get($config['image_url'])->assertNotFound();
        $this->assertEmpty(Storage::disk('local')->files('category-story-curated'));
        $this->get('/admin/categories/beauty/nails/stories')->assertOk()
            ->assertSee('Фото BOSH');
    }
}
