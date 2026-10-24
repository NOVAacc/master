<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\CategoryStoryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryStoryStageTwoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    private function data(): array
    {
        return [
            'font' => 'elegant', 'text_color' => '#FFF5E7', 'scale' => 110, 'shade' => 20,
            'image_position' => 'bottom', 'image_horizontal' => 'left', 'image_zoom' => 120,
        ];
    }

    public function test_all_category_templates_open_in_individual_editors_without_affecting_legacy_catalog(): void
    {
        $this->get('/admin/categories/beauty/nails/stories/editorial')->assertRedirect('/admin/login');
        $this->admin();
        foreach (CategoryStoryCatalog::all() as $industry => $activities) {
            foreach ($activities as $activity => $category) {
                $this->get("/admin/categories/{$industry}/{$activity}/stories")->assertOk()
                    ->assertSee('Этап 2 из 3');
                foreach ($category['templates'] as $template) {
                    $this->get("/admin/categories/{$industry}/{$activity}/stories/{$template['id']}")
                        ->assertOk()->assertSee('Сохранить шаблон')->assertSee('storyPreview');
                }
            }
        }
        $this->get('/admin/categories/beauty/nails/stories/invalid')->assertNotFound();
        $this->get('/admin/categories/invalid/nails/stories/editorial')->assertNotFound();
    }

    public function test_settings_persist_privately_and_autocolor_is_supported(): void
    {
        $url = '/admin/categories/education/languages/stories/editorial';
        $this->admin();
        $this->post($url, $this->data())->assertRedirect($url);
        $value = json_decode(SiteSetting::whereKey('category-story.education.languages.editorial')->value('value'), true);
        $this->assertSame('elegant', $value['font']);
        $this->assertSame('#FFF5E7', $value['text_color']);
        $this->assertSame(120, $value['image_zoom']);
        $this->post($url, [...$this->data(), 'text_color' => ''])->assertRedirect($url);
        $this->assertSame('', json_decode(SiteSetting::whereKey('category-story.education.languages.editorial')->value('value'), true)['text_color']);
        $this->get('/admin/categories/education/languages/stories')->assertOk();
        $this->actingAs(User::factory()->create(), 'web')
            ->post($url, $this->data())->assertRedirect('/admin/login');
        $this->getJson('/stories/templates/catalog')->assertOk()->assertJsonMissingPath('education.languages.editorial');
    }

    public function test_image_upload_replacement_and_removal_protect_old_versions(): void
    {
        Storage::fake('local');
        $this->admin();
        $url = '/admin/categories/auto/tires/stories/proof';
        $upload = UploadedFile::fake()->image('sample.jpg', 1080, 1920);
        $this->post($url, [...$this->data(), 'image' => $upload])->assertRedirect($url);
        $entry = json_decode(SiteSetting::whereKey('category-story.auto.tires.proof')->value('value'), true);
        $this->assertNotEmpty($entry['path']);
        Storage::disk('local')->assertExists($entry['path']);
        $name = pathinfo($entry['path'], PATHINFO_FILENAME);
        $imageUrl = $url.'/image/'.$name;
        $this->get($imageUrl)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($url.'/image/'.str_repeat('a', 40))->assertNotFound();

        $this->post($url, [...$this->data(), 'image' => UploadedFile::fake()->image('replacement.png', 1080, 1920)])
            ->assertRedirect($url);
        Storage::disk('local')->assertMissing($entry['path']);
        $this->get($imageUrl)->assertNotFound();
        $next = json_decode(SiteSetting::whereKey('category-story.auto.tires.proof')->value('value'), true);
        Storage::disk('local')->assertExists($next['path']);

        $this->post($url, [...$this->data(), 'remove_image' => 1])->assertRedirect($url);
        Storage::disk('local')->assertMissing($next['path']);
        $this->assertNull(json_decode(SiteSetting::whereKey('category-story.auto.tires.proof')->value('value'), true)['path']);
    }

    public function test_invalid_parameters_are_rejected_before_writing(): void
    {
        $this->admin();
        $url = '/admin/categories/sport/yoga/stories/premium';
        $this->post($url, [...$this->data(), 'font' => 'malicious'])->assertSessionHasErrors('font');
        $this->post($url, [...$this->data(), 'image_zoom' => 900])->assertSessionHasErrors('image_zoom');
        $this->post($url, [...$this->data(), 'text_color' => '#ABCDEF'])->assertSessionHasErrors('text_color');
        $this->post($url, [...$this->data(), 'image' => UploadedFile::fake()->create('shell.php', 100, 'application/x-php')])
            ->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('site_settings', ['key' => 'category-story.sport.yoga.premium']);
    }
}
