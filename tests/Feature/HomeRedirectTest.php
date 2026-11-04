<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未登录访问首页 → 渲染落地页（不跳登录）
     */
    public function test_guest_sees_landing_on_home_without_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('每一份引领行业的商业远见都值得被看见');
    }

    /**
     * 登录且已有档案 → 首页跳转个人详情页
     */
    public function test_logged_in_user_redirected_to_own_detail(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertRedirect(route('entrepreneurs.show', $entrepreneur->id));
    }

    /**
     * 登录但无档案 → 首页跳转个人中心（引导创建）
     */
    public function test_logged_in_without_profile_redirected_to_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertRedirect(route('profile.show'));
    }

    /**
     * 本人可查看自己的待审核档案（无视状态）
     */
    public function test_user_can_view_own_pending_profile(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_PENDING,
        ]);

        $this->actingAs($user);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee($entrepreneur->name);
    }

    /**
     * 企业家库仅收录"推荐"档案
     */
    public function test_directory_only_shows_featured(): void
    {
        $featured = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);
        $notFeatured = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
        ]);

        $response = $this->get('/entrepreneurs');

        $response->assertSee($featured->name);
        $response->assertDontSee($notFeatured->name);
    }
}
