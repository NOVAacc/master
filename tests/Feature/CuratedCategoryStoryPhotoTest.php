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
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'salon'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'veterinary'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'psychology'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'medical-center'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'aesthetic'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'diagnostics'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'checkups'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'exams'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'tutoring'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'children'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'professional'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'courses'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'bodywork'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'tuning'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'inspection'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'dealership'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'rental'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'pool'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'martial-arts'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'games'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'children'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'section'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'water'));
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
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'dentistry'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('medicine', 'massage'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'brows-lashes'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'car-wash'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'languages'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('household', 'photo'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('household', 'interior'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('household', 'cleaning'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('household', 'pets'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'music'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'dance'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'tires'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'spa'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'tanning'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'stretching'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('education', 'driving'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'kids-hair'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('auto', 'mechanic'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'racket'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'tattoo'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'waxing'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('sport', 'trainer'));
        $this->assertSame(8, CategoryStoryPhotoLibrary::count('beauty', 'cosmetology'));
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
        $this->assertCount(392, $allIds);
        $this->assertCount(392, array_unique($allIds));
        $this->assertNotNull(CategoryStoryPhotoLibrary::get('beauty', 'salon', 'editorial'));
        $this->assertNull(CategoryStoryPhotoLibrary::get('beauty', 'unknown', 'editorial'));
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
