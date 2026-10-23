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
            'font'=>'serif','scale'=>110,'text_color'=>'#112233',
        ])->assertRedirect('/admin/stories');
        $this->assertDatabaseHas('site_settings', ['key'=>'story.hair.nude']);
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/stories/templates/catalog')->assertOk()
            ->assertJsonPath('hair.nude.font','serif')
            ->assertJsonPath('hair.nude.scale',110)
            ->assertJsonPath('hair.nude.text_color','#112233');
    }

    public function test_invalid_template_and_font_are_rejected(): void
    {
        $this->admin();
        $this->post('/admin/stories/hair/unknown', ['font'=>'system','scale'=>100])->assertNotFound();
        $this->post('/admin/stories/hair/nude', ['font'=>'javascript','scale'=>100])->assertSessionHasErrors('font');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>999])->assertSessionHasErrors('scale');
        $this->post('/admin/stories/hair/nude', ['font'=>'system','scale'=>100,'text_color'=>'bad'])->assertSessionHasErrors('text_color');
    }

    public function test_admin_upload_is_private_versioned_and_removable(): void
    {
        Storage::fake('local');
        $this->admin();
        $this->post('/admin/stories/brows/soft-pink', [
            'font'=>'rounded','scale'=>100,
            'image'=>UploadedFile::fake()->image('portrait.jpg',1080,1920),
        ])->assertRedirect('/admin/stories');
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
        ])->assertRedirect('/admin/stories');
        Storage::disk('local')->assertMissing($entry['path']);
        $this->get($url)->assertNotFound();
    }
}
