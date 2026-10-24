<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_category_directory_uses_every_site_industry_and_activity(): void
    {
        $this->get('/admin/categories')->assertRedirect('/admin/login');
        $this->admin();

        $response = $this->get('/admin/categories')->assertOk()
            ->assertSee('Категории мастеров')
            ->assertSee('Тематических сторис пока нет')
            ->assertSee('/admin/stories#nails');

        $industries = config('industries');
        $this->assertCount(6, $industries);
        $this->assertSame(56, array_sum(array_map(fn ($row) => count($row['activities']), $industries)));
        foreach ($industries as $industry) {
            $response->assertSee($industry['name']);
            foreach ($industry['activities'] as $name) {
                $response->assertSee(e($name), false);
            }
        }
    }

    public function test_template_management_is_admin_only(): void
    {
        $this->get('/admin/stories')->assertRedirect('/admin/login');
        $this->post('/admin/stories/hair/nude', ['font'=>'serif','scale'=>100])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(), 'web')
            ->post('/admin/stories/hair/nude', ['font'=>'serif','scale'=>100])->assertRedirect('/admin/login');
    }

    public function test_admin_can_update_fonts_and_generated_catalog(): void
    {
        $this->admin();
        $this->get('/admin/stories')->assertOk()->assertSee('Шаблоны сторис');
        $this->post('/admin/stories/hair/nude', [
            'font'=>'serif','scale'=>110,'text_color'=>'#112233','image_position'=>'bottom','image_horizontal'=>'right','image_zoom'=>130,'shade'=>20,
        ])->assertRedirect('/admin/stories/hair/nude');
        $this->assertDatabaseHas('site_settings', ['key'=>'story.hair.nude']);
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')->assertOk()
            ->assertJsonPath('hair.nude.font','serif')
            ->assertJsonPath('hair.nude.scale',110)
            ->assertJsonPath('hair.nude.text_color','#112233')
            ->assertJsonPath('hair.nude.image_position','bottom')
            ->assertJsonPath('hair.nude.shade',20)
            ->assertJsonPath('hair.nude.image_horizontal','right')
            ->assertJsonPath('hair.nude.image_zoom',130);
    }

    public function test_invalid_template_and_font_are_rejected(): void
    {
        $this->admin();
        $this->post('/admin/stories/hair/unknown', ['font'=>'system','scale'=>100])->assertNotFound();
        $this->post('/admin/stories/hair/nude', ['font'=>'javascript','scale'=>100])->assertSessionHasErrors('font');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>999])->assertSessionHasErrors('scale');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'image_position'=>'diagonal'])->assertSessionHasErrors('image_position');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'shade'=>90])->assertSessionHasErrors('shade');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'image_horizontal'=>'diagonal'])->assertSessionHasErrors('image_horizontal');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'image_zoom'=>500])->assertSessionHasErrors('image_zoom');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'text_color'=>'bad'])->assertSessionHasErrors('text_color');
    }

    public function test_new_font_styles_and_preset_colors_are_available_to_all_templates(): void
    {
        $this->admin();
        $this->get('/admin/stories/lashes/nude')->assertOk()
            ->assertSee('Элегантный')
            ->assertSee('Классический')
            ->assertSee('Акцентный')
            ->assertSee('Моноширинный')
            ->assertSee('Пудровый')
            ->assertSee('Красный BOSH')
            ->assertSee('story-color-swatch');

        $this->post('/admin/stories/lashes/nude', [
            'font' => 'elegant', 'scale' => 100, 'text_color' => '#F6D7E3',
        ])->assertRedirect('/admin/stories/lashes/nude');

        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')
            ->assertOk()
            ->assertJsonPath('lashes.nude.font', 'elegant')
            ->assertJsonPath('lashes.nude.text_color', '#F6D7E3');

        $this->admin();
        $this->post('/admin/stories/lashes/nude', [
            'font' => 'mono', 'scale' => 100, 'text_color' => '',
        ])->assertRedirect('/admin/stories/lashes/nude');
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')
            ->assertJsonPath('lashes.nude.font', 'mono')
            ->assertJsonPath('lashes.nude.text_color', '');
    }

    public function test_admin_upload_is_private_versioned_and_removable(): void
    {
        Storage::fake('local');
        $this->admin();
        $upload = $this->post('/admin/stories/brows/soft-pink', [
            'font'=>'rounded','scale'=>100,
            'image'=>UploadedFile::fake()->image('portrait.jpg',1080,1920),
        ]);
        $upload->assertRedirect('/admin/stories/brows/soft-pink');
        $entry=json_decode(\App\Models\SiteSetting::whereKey('story.brows.soft-pink')->value('value'),true);
        $this->assertNotEmpty($entry['path']);
        Storage::disk('local')->assertExists($entry['path']);
        $catalog=$this->actingAs(User::factory()->create(), 'web')->getJson('/stories/templates/catalog')->json();
        $url=$catalog['brows']['soft-pink']['image_url'];
        $this->assertNotNull($url);
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->get(str_replace(pathinfo($entry['path'],PATHINFO_FILENAME),str_repeat('a',40),$url))->assertNotFound();
        $this->admin();
        $this->post('/admin/stories/brows/soft-pink', [
            'font'=>'system','scale'=>100,'remove_image'=>1,
        ])->assertRedirect('/admin/stories/brows/soft-pink');
        Storage::disk('local')->assertMissing($entry['path']);
        $this->get($url)->assertNotFound();
    }
    public function test_complete_catalog_has_all_72_templates_with_usable_photo_references(): void
    {
        $this->admin();
        $controller = \App\Http\Controllers\StoryTemplateAdminController::class;
        $this->assertCount(9, $controller::COLLECTIONS);
        $catalog = $controller::catalog();
        $this->assertCount(9, $catalog);
        foreach ($controller::COLLECTIONS as $theme => $collection) {
            $this->assertCount(8, $collection['ids'], "Missing templates in {$theme}");
            foreach ($collection['ids'] as $id) {
                $this->assertArrayHasKey($id, $catalog[$theme]);
                $this->assertArrayHasKey($id, $controller::LABELS[$theme]);
                $this->assertArrayHasKey($id, $controller::DEFAULT_PHOTOS[$theme]);
                $this->assertNotEmpty($catalog[$theme][$id]['photo_url']);
                $this->assertSame('center', $catalog[$theme][$id]['image_position']);
                $this->assertSame(100, $catalog[$theme][$id]['image_zoom']);
            }
        }
    }

    public function test_nine_categories_have_one_template_edit_page_each(): void
    {
        $this->admin();
        $this->get('/admin/stories')->assertOk()->assertSee('Ресницы')->assertSee('Эпиляция')
            ->assertSee('/admin/stories/barber/classic')
            ->assertSee('role="tablist"', false)
            ->assertSee('Фото используется в')
            ->assertDontSee('name="image"', false);
        $this->get('/admin/stories/lashes/nude')->assertOk()
            ->assertSee('Лёгкий нюд')
            ->assertSee('name="image"', false)
            ->assertDontSee('story-positions');
        $this->get('/admin/stories/invalid/nude')->assertNotFound();
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')->assertOk()
            ->assertJsonCount(8, 'lashes')->assertJsonCount(8, 'makeup')
            ->assertJsonCount(8, 'massage')->assertJsonCount(8, 'skin')
            ->assertJsonCount(8, 'barber')->assertJsonCount(8, 'smooth');
    }

}
