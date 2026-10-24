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
