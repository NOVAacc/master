<?php
namespace Tests\Feature;
use App\Models\Administrator;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FeedbackTest extends TestCase {
    use RefreshDatabase;
    public function test_authenticated_master_can_send_feedback_without_spoofing_owner_or_status(): void {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/feedback', ['category' => 'bug', 'message' => '  Не открывается календарь  ', 'page' => '/dashboard', 'user_id' => 999, 'status' => 'reviewed'])->assertCreated();
        $this->assertDatabaseHas('feedback', ['user_id' => $user->id, 'message' => 'Не открывается календарь', 'status' => 'new', 'page' => '/dashboard']);
    }
    public function test_feedback_requires_login_and_valid_input(): void {
        $this->postJson('/feedback', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson('/feedback', ['category' => 'bad', 'message' => '   ', 'page' => 'https://example.com'])->assertUnprocessable()->assertJsonValidationErrors(['category', 'message', 'page']);
        $this->postJson('/feedback', ['category' => 'idea', 'message' => str_repeat('a', 5001)])->assertUnprocessable();
        $this->postJson('/feedback', ['category' => 'idea', 'message' => ['bad']])->assertUnprocessable();
    }
    public function test_feedback_is_rate_limited(): void {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 5; $i++) $this->postJson('/feedback', ['category' => 'idea', 'message' => 'Предложение'])->assertCreated();
        $this->postJson('/feedback', ['category' => 'idea', 'message' => 'Предложение'])->assertStatus(429);
    }
    public function test_only_admin_can_read_and_update_feedback_and_html_is_escaped(): void {
        $user = User::factory()->create();
        $item = Feedback::create(['user_id' => $user->id, 'category' => 'idea', 'message' => '<script>alert(1)</script>']);
        $this->actingAs($user)->get('/admin/feedback')->assertRedirect('/admin/login');
        $this->patch('/admin/feedback/'.$item->id, ['status' => 'reviewed'])->assertRedirect('/admin/login');
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession(['admin_password_stamp' => hash('sha256', $admin->getAuthPassword())]);
        $this->get('/admin/feedback')->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->patch('/admin/feedback/'.$item->id, ['status' => 'reviewed'])->assertRedirect();
        $this->assertDatabaseHas('feedback', ['id' => $item->id, 'status' => 'reviewed']);
        $this->get('/admin/feedback?status=new')->assertDontSee('&lt;script&gt;', false);
        $user->delete();
        $this->assertDatabaseHas('feedback', ['id' => $item->id, 'user_id' => null]);
    }
}
