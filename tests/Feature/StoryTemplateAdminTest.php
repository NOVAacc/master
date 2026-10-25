<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\CategoryStoryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoryTemplateAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    public function test_admin_navigation_keeps_category_story_editor_and_removes_old_stories_pages(): void
    {
        $this->admin();
        $this->get('/admin/categories')->assertOk()
            ->assertSee('Категории мастеров')
            ->assertSee('8 шаблонов сторис')
            ->assertDontSee('href="'.route('admin.categories').'/../stories"', false)
            ->assertDontSee('/admin/stories#');
        $this->get('/admin/stories')->assertNotFound();
        $this->get('/admin/stories/hair/nude')->assertNotFound();
        $this->post('/admin/stories/hair/nude', ['font' => 'system'])->assertNotFound();
        $this->get('/admin/categories/beauty/nails/stories')->assertOk()->assertSee('Чистый editorial');
        $this->get('/admin/categories/beauty/nails/stories/editorial')->assertOk()->assertSee('Сохранить шаблон');
    }

    public function test_category_directory_reflects_all_real_master_activities(): void
    {
        $this->get('/admin/categories')->assertRedirect('/admin/login');
        $this->admin();
        $response = $this->get('/admin/categories')->assertOk();
        $industries = config('industries');
        $this->assertCount(6, $industries);
        $this->assertSame(57, array_sum(array_map(fn ($row) => count($row['activities']), $industries)));
        foreach ($industries as $industry) {
            $response->assertSee($industry['name']);
            foreach ($industry['activities'] as $name) $response->assertSee(e($name), false);
        }
        $this->assertCount(456, array_merge(...array_map(
            fn ($entries) => array_merge(...array_map(
                fn ($entry) => array_column($entry['templates'], 'key'),
                array_values($entries))),
            array_values(CategoryStoryCatalog::all()))));
    }

    public function test_legacy_story_catalog_still_serves_all_72_templates(): void
    {
        $controller = \App\Http\Controllers\StoryTemplateAdminController::class;
        $catalog = $controller::catalog();
        $this->assertCount(9, $catalog);
        foreach ($controller::COLLECTIONS as $theme => $collection) {
            $this->assertCount(8, $collection['ids']);
            foreach ($collection['ids'] as $id) {
                $this->assertArrayHasKey($id, $catalog[$theme]);
                $this->assertNotEmpty($catalog[$theme][$id]['photo_url']);
            }
        }
        $this->getJson('/stories/templates/catalog')->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')->assertOk()->assertJsonCount(8, 'nails');
    }

    public function test_saved_legacy_photo_remains_accessible_without_admin_editor(): void
    {
        Storage::fake('local');
        $theme = 'brows'; $template = 'soft-pink';
        $version = str_repeat('a', 40);
        $path = "story-templates/{$theme}/{$template}/{$version}.jpg";
        $image = \Illuminate\Http\UploadedFile::fake()->image('legacy.jpg', 1080, 1920);
        Storage::disk('local')->put($path, file_get_contents($image->getPathname()));
        SiteSetting::updateOrCreate(['key' => 'story.brows.soft-pink'], ['value' => json_encode([
            'font' => 'rounded', 'scale' => 100, 'text_color' => '#FFFFFF',
            'image_position' => 'bottom', 'image_horizontal' => 'right', 'image_zoom' => 120,
            'shade' => 20, 'path' => $path,
        ])]);

        $this->actingAs(User::factory()->create(), 'web');
        $catalog = $this->getJson('/stories/templates/catalog')->assertOk();
        $catalog->assertJsonPath('brows.soft-pink.image_position', 'bottom')
            ->assertJsonPath('brows.soft-pink.image_horizontal', 'right');
        $url = $catalog->json('brows.soft-pink.image_url');
        $this->assertNotNull($url);
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(str_replace($version, str_repeat('b', 40), $url))->assertNotFound();
    }
}
