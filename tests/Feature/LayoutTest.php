<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登录用户菜单：后台入口已移除，通知与设置入口存在
     */
    public function test_menu_has_settings_and_no_backend_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/my/profile')
            ->assertOk()
            ->assertDontSee('管理认证与推荐')
            ->assertDontSee('链接、推荐、密码与通知')
            ->assertDontSee('编辑名片与个人资料')
            ->assertSee('首页');
    }

    /**
     * 管理员在前台：设置弹窗 tab 栏有后台入口
     */
    public function test_admin_sees_backend_shortcut_in_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Entrepreneur::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/my/profile')
            ->assertOk()
            ->assertSee('后台 →');
    }

    /**
     * 普通用户：设置弹窗无后台入口
     */
    public function test_regular_user_no_backend_shortcut(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/my/profile')
            ->assertOk()
            ->assertDontSee('后台 →');
    }

    /**
     * 管理员在后台布局：设置弹窗不显示后台入口（避免自指）；后台菜单无「账户设置」入口
     */
    public function test_admin_backend_hides_shortcut(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('后台 →')
            ->assertDontSee('账户设置');
    }

    /**
     * 设置弹窗含密码折叠容器与 aria-expanded 初值
     */
    public function test_settings_modal_has_password_collapse(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/my/profile')
            ->assertOk()
            ->assertSee('修改密码')
            ->assertSee('pw-panel');
    }

    /**
     * 菜单首页：访客落地页 200 且含「首页」；登录用户点击 route('home') 302 跳到本人名片详情页
     */
    public function test_menu_home_redirects_owner_to_card(): void
    {
        // 访客：落地页 200，菜单含「首页」
        $this->get('/')->assertOk()->assertSee('首页');

        // 登录卡片本人：route('home') 302 跳到本人名片详情页
        $owner = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($owner)
            ->get('/')
            ->assertRedirect($entrepreneur->url);
    }
}
