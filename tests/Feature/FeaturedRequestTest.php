<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未登录不能发起推荐申请（auth 中间件拦截）
     */
    public function test_guest_cannot_request_featured(): void
    {
        Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
        ]);

        $response = $this->post('/my/profile/featured-request', ['reason' => '理由']);

        $response->assertRedirect('/login');
    }

    /**
     * 本人可发起推荐申请（含理由），状态变为待审核
     */
    public function test_user_can_request_featured_with_reason(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '我对行业有深度洞察']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
            'featured_reason' => '我对行业有深度洞察',
        ]);
    }

    /**
     * 申请理由为空被拒绝
     */
    public function test_reason_is_required(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
        ]);

        $this->actingAs($user);

        $response = $this->from('/my/profile')->post('/my/profile/featured-request', ['reason' => '']);

        $response->assertSessionHasErrors('reason');
        $this->assertNull(Entrepreneur::find($entrepreneur->id)->featured_request_status);
    }

    /**
     * 已有待审核申请时不可重复提交
     */
    public function test_cannot_request_twice_while_pending(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '再次申请']);

        $response->assertSessionHas('error');
    }

    /**
     * 档案未认证不可申请推荐
     */
    public function test_cannot_request_when_not_approved(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_PENDING,
            'is_featured' => false,
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '理由']);

        $response->assertSessionHas('error');
        $this->assertNull(Entrepreneur::find($entrepreneur->id)->featured_request_status);
    }

    /**
     * 已获推荐不可再申请
     */
    public function test_cannot_request_when_already_featured(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '理由']);

        $response->assertSessionHas('error');
    }

    /**
     * 管理员通过推荐申请 → 成为推荐并进入智库
     */
    public function test_admin_can_approve_featured_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/featured-requests/{$entrepreneur->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'is_featured' => true,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_APPROVED,
        ]);

        // 进入智库列表
        $this->get('/entrepreneurs')->assertSee($entrepreneur->name);
    }

    /**
     * 管理员拒绝推荐申请 → 写入拒绝时间（冷却期起点）
     */
    public function test_admin_can_reject_featured_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/featured-requests/{$entrepreneur->id}/reject");

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
        ]);
        $this->assertNotNull(Entrepreneur::find($entrepreneur->id)->featured_rejected_at);
    }

    /**
     * 被拒后冷却期内（15 天内）不可再次申请
     */
    public function test_cannot_request_within_cooldown(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
            'featured_rejected_at' => now()->subDays(3),
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '再试试']);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
        ]);
    }

    /**
     * 冷却期（15 天）结束后可再次申请
     */
    public function test_can_request_after_cooldown(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
            'featured_rejected_at' => now()->subDays(16),
        ]);

        $this->actingAs($user);

        $response = $this->post('/my/profile/featured-request', ['reason' => '冷却结束再次申请']);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
            'featured_reason' => '冷却结束再次申请',
        ]);
    }

    /**
     * JSON 请求返回 {ok: true}（抽屉内联提交）
     */
    public function test_featured_request_returns_json_when_wants_json(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
        ]);

        $res = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson('/my/profile/featured-request', ['reason' => '深度洞察']);

        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(
            Entrepreneur::FEATURED_STATUS_PENDING,
            Entrepreneur::find($entrepreneur->id)->featured_request_status
        );
    }

    /**
     * 冷却期内 JSON 请求返回 422 + featured 字段错误
     */
    public function test_featured_request_json_returns_422_on_cooldown(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
            'featured_rejected_at' => now()->subDays(3),
        ]);

        $res = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson('/my/profile/featured-request', ['reason' => '再试']);

        $res->assertStatus(422)->assertJsonPath('errors.featured.0', fn ($m) => str_contains($m, '天'));
    }

    /**
     * 非管理员不能审核推荐申请
     */
    public function test_regular_user_cannot_review_featured(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);

        $this->actingAs($user);

        $response = $this->post("/admin/featured-requests/{$entrepreneur->id}/approve");

        // AdminMiddleware 拦截非管理员
        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);
    }

    /**
     * 管理员取消推荐后申请状态重置（允许再次申请）
     */
    public function test_toggle_featured_off_resets_request_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $entrepreneur = Entrepreneur::factory()->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_APPROVED,
        ]);

        $this->actingAs($admin);

        $response = $this->post("/admin/entrepreneurs/{$entrepreneur->id}/toggle-featured");

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'is_featured' => false,
        ]);
        $this->assertNull(Entrepreneur::find($entrepreneur->id)->featured_request_status);
        $this->assertNull(Entrepreneur::find($entrepreneur->id)->featured_rejected_at);
    }
}
