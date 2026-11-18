<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 本人可保存多条社交主页网址
     */
    public function test_user_can_update_social_links(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patch('/my/profile', [
            'social_links' => [
                'https://www.xiaohongshu.com/user/profile/abc',
                'https://weibo.com/u/123',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'social_links' => json_encode([
                'https://www.xiaohongshu.com/user/profile/abc',
                'https://weibo.com/u/123',
            ]),
        ]);
    }

    /**
     * 网址格式非法被拦截
     */
    public function test_invalid_social_url_rejected(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => ['not a url'],
        ]);

        $response->assertSessionHasErrors('social_links.0');
    }

    /**
     * 空白项 / 重复项被自动清理
     */
    public function test_blank_and_duplicate_links_cleaned(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patch('/my/profile', [
            'social_links' => [
                '  https://weibo.com/u/1  ',
                '',
                'https://weibo.com/u/1',
                'https://douyin.com/@x',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'social_links' => json_encode([
                'https://weibo.com/u/1',
                'https://douyin.com/@x',
            ]),
        ]);
    }

    /**
     * 不带协议自动补全 http://
     */
    public function test_missing_protocol_auto_prepends_http(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => ['weibo.com/u/123'],
        ])->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'social_links' => json_encode(['http://weibo.com/u/123']),
        ]);
    }

    /**
     * 协议相对 //xxx 自动补全 http://
     */
    public function test_protocol_relative_auto_prepends_http(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => ['//weibo.com/u/123'],
        ])->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'social_links' => json_encode(['http://weibo.com/u/123']),
        ]);
    }

    /**
     * 已带协议原样保留
     */
    public function test_existing_protocol_kept(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => ['https://weibo.com/u/123'],
        ])->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'social_links' => json_encode(['https://weibo.com/u/123']),
        ]);
    }

    /**
     * 最多 5 条，超出被拦截
     */
    public function test_max_five_links_enforced(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $urls = collect(range(1, 6))
            ->map(fn ($i) => "https://example.com/{$i}")
            ->all();

        $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => $urls,
        ])->assertSessionHasErrors('social_links');
    }

    /**
     * 空数组清空全部链接
     */
    public function test_empty_links_clear_all(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'social_links' => ['https://weibo.com/u/123'],
        ]);

        $this->actingAs($user);

        $this->from('/my/profile')->patch('/my/profile', [
            'social_links' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('entrepreneurs', [
            'user_id' => $user->id,
            'social_links' => json_encode([]),
        ]);
    }

    /**
     * 名片按域名识别展示社交图标与链接（多条）
     */
    public function test_card_shows_social_icon_links(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => [
                'https://www.xiaohongshu.com/user/profile/abc',
                'https://weibo.com/u/123',
            ],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/xiaohongshu.svg');
        $response->assertSee('icons/weibo.svg');
        $response->assertSee('https://www.xiaohongshu.com/user/profile/abc');
        $response->assertSee('https://weibo.com/u/123');
    }

    /**
     * 域名识别：weibo.com 匹配微博图标
     */
    public function test_domain_maps_to_weibo_icon(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://weibo.com/u/123'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/weibo.svg');
    }

    /**
     * 域名识别：微博手机域名 weibo.cn 也匹配微博图标
     */
    public function test_domain_maps_weibo_cn_to_icon(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://m.weibo.cn/u/123'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/weibo.svg');
    }

    /**
     * 未知域名使用默认 google 图标
     */
    public function test_unknown_domain_uses_default_icon(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://example.com'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/google.svg');
    }

    /**
     * 非 http(s) 协议不渲染可点击链接（协议白名单）
     */
    public function test_non_http_scheme_not_linked(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['ftp://example.com'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertDontSee('ftp://example.com');
        $response->assertDontSee('icons/google.svg');
    }

    /**
     * 品牌域名使用 logo 图标
     */
    public function test_brand_domain_uses_logo_icon(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://www.abincheung.com/about'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/logo.svg');
    }

    /**
     * 品牌子域名（xxx.abincheung.com 等）同样使用 logo 图标
     */
    public function test_brand_subdomain_uses_logo_icon(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://xxx.61ml.com/about'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertSee('icons/logo.svg');
    }

    /**
     * 仿冒域名（结尾非主域）不命中品牌 logo，回退默认图标
     */
    public function test_lookalike_domain_not_matched(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => ['https://www.61lm.com.cn/about'],
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        // 仿冒域名不映射品牌 logo，回退默认图标。
        // 注：菜单头部现合法渲染 icons/logo.svg，故不能用页面级 assertDontSee；以「链接已渲染 + 默认图标」验证。
        $response->assertSee('61lm.com.cn');
        $response->assertSee('icons/google.svg');
    }

    /**
     * 未设置社交链接时不渲染图标
     */
    public function test_card_hides_social_icon_when_no_url(): void
    {
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'social_links' => null,
        ]);

        $response = $this->get('/entrepreneurs/'.$entrepreneur->id);

        $response->assertStatus(200);
        $response->assertDontSee('icons/xiaohongshu.svg');
    }
}
