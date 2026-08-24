<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ShareSlugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 旧数字链接 /entrepreneurs/{id} 仍可用
     */
    public function test_legacy_url_still_works(): void
    {
        $entrepreneur = Entrepreneur::factory()->create();

        $this->get('/entrepreneurs/'.$entrepreneur->id)->assertStatus(200);
    }

    /**
     * 设置 slug 后短链 /u/{slug} 可访问
     */
    public function test_short_slug_url_accessible(): void
    {
        $entrepreneur = Entrepreneur::factory()->create(['share_slug' => 'zhangsan']);

        $this->get('/u/zhangsan')->assertStatus(200)->assertSee($entrepreneur->name);
    }

    /**
     * 不带 slug 的档案，/u/{anything} 返回 404
     */
    public function test_nonexistent_slug_returns_404(): void
    {
        $this->get('/u/nobody')->assertStatus(404);
    }

    /**
     * slug 含连字符
     */
    public function test_slug_with_hyphen(): void
    {
        $entrepreneur = Entrepreneur::factory()->create(['share_slug' => 'li-si-2024']);

        $this->get('/u/li-si-2024')->assertStatus(200);
    }

    /**
     * 纯数字 slug 不会被保存（避免与 /entrepreneurs/{id} 冲突）
     */
    public function test_pure_numeric_slug_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch('/my/profile', [
                'share_slug' => '12345',
            ])
            ->assertSessionHasErrors(['share_slug']);

        $this->assertDatabaseMissing('entrepreneurs', [
            'id' => $entrepreneur->id,
            'share_slug' => '12345',
        ]);
    }

    /**
     * 斜杠格式不合法：大写字母被拒绝
     */
    public function test_uppercase_slug_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch('/my/profile', [
                'share_slug' => 'ZhangSan',
            ])
            ->assertSessionHasErrors(['share_slug']);
    }

    /**
     * slug 唯一性：被其他用户占用不可保存
     */
    public function test_duplicate_slug_rejected_on_update(): void
    {
        $other = Entrepreneur::factory()->create(['share_slug' => 'zhangsan']);
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch('/my/profile', [
                'share_slug' => 'zhangsan',
            ])
            ->assertSessionHasErrors(['share_slug']);

        $this->assertDatabaseMissing('entrepreneurs', [
            'id' => $entrepreneur->id,
            'share_slug' => 'zhangsan',
        ]);
    }

    /**
     * 用户可修改自己的 slug（覆盖自己之前设置的）
     */
    public function test_owner_can_update_own_slug(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'share_slug' => 'oldslug',
        ]);

        $this->actingAs($user)
            ->patch('/my/profile', [
                'share_slug' => 'newslug',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'share_slug' => 'newslug',
        ]);
    }

    /**
     * slug 留空不报错（可选字段）
     */
    public function test_empty_slug_is_allowed(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patch('/my/profile', [
                'share_slug' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'share_slug' => null,
        ]);
    }

    /**
     * check-slug 端点：返回可用
     */
    public function test_check_slug_returns_available_when_free(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/my/profile/check-slug?slug=free-slug')
            ->assertStatus(200)
            ->assertExactJson(['available' => true]);
    }

    /**
     * check-slug 端点：已被其他用户占用返回不可用
     */
    public function test_check_slug_returns_taken_when_occupied(): void
    {
        Entrepreneur::factory()->create(['share_slug' => 'taken']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/my/profile/check-slug?slug=taken')
            ->assertExactJson(['available' => false]);
    }

    /**
     * check-slug 端点：本人已占用视为可用（用户可编辑）
     */
    public function test_check_slug_returns_available_for_own_slug(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'share_slug' => 'mine',
        ]);

        $this->actingAs($user)
            ->getJson('/my/profile/check-slug?slug=mine')
            ->assertExactJson(['available' => true]);
    }

    /**
     * check-slug 端点：未登录重定向
     */
    public function test_check_slug_requires_login(): void
    {
        Auth::logout();

        // JSON 请求被 auth 中间件拦截时返回 401（非 HTML 重定向的 302）
        $this->getJson('/my/profile/check-slug?slug=anything')
            ->assertStatus(401);
    }

    /**
     * 纯数字 slug 端点返回不可用
     */
    public function test_check_slug_rejects_pure_numeric(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/my/profile/check-slug?slug=12345')
            ->assertExactJson(['available' => false]);
    }

    /**
     * og-url 走短链
     */
    public function test_og_url_uses_short_slug(): void
    {
        $entrepreneur = Entrepreneur::factory()->create(['share_slug' => 'zhangsan']);

        $this->get('/u/zhangsan')
            ->assertSee('property="og:url" content="' . route('entrepreneurs.show', 'zhangsan') . '"', false);
    }

    /**
     * og-url 无 slug 时回退数字 id
     */
    public function test_og_url_falls_back_to_id_without_slug(): void
    {
        $entrepreneur = Entrepreneur::factory()->create();

        $this->get('/entrepreneurs/'.$entrepreneur->id)
            ->assertSee('property="og:url" content="' . route('entrepreneurs.show', $entrepreneur->id) . '"', false);
    }

    /**
     * 首页登录后走 slug 重定向
     */
    public function test_home_redirects_to_slug_when_set(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'share_slug' => 'myslug',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/u/myslug');
    }

    /**
     * 首页登录后无 slug 回退数字 id
     */
    public function test_home_redirects_to_id_without_slug(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/')
            ->assertRedirect('/u/' . Entrepreneur::first()->id);
    }
}