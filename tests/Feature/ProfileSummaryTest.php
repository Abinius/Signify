<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_returns_slug_and_urls(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'share_slug' => 'abin',
        ]);

        $res = $this->actingAs($user)->getJson('/my/summary');

        $res->assertOk()
            ->assertJsonPath('has_entrepreneur', true)
            ->assertJsonPath('status', Entrepreneur::STATUS_APPROVED)
            ->assertJsonPath('status_label', '已收录')
            ->assertJsonPath('slug', 'abin')
            ->assertJsonPath('card_url', route('entrepreneurs.show', 'abin'))
            ->assertJsonPath('check_slug_url', route('profile.check-slug'))
            ->assertJsonPath('update_url', route('profile.update'))
            ->assertJsonPath('featured_url', route('profile.featured-request'))
            ->assertJsonPath('can_apply_featured', true);
    }

    public function test_summary_featured_label_for_pending(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
        ]);

        $res = $this->actingAs($user)->getJson('/my/summary');

        $res->assertOk()
            ->assertJsonPath('featured_label', '推荐待审核')
            ->assertJsonPath('can_apply_featured', false);
    }

    public function test_summary_featured_label_when_featured(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);

        $res = $this->actingAs($user)->getJson('/my/summary');

        $res->assertOk()
            ->assertJsonPath('featured_label', '已推荐')
            ->assertJsonPath('can_apply_featured', false);
    }

    public function test_summary_no_entrepreneur(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/my/summary');

        $res->assertOk()->assertJsonPath('has_entrepreneur', false);
    }

    public function test_summary_requires_auth(): void
    {
        $this->getJson('/my/summary')->assertUnauthorized();
    }

    /**
     * 抽屉内联保存专属链接：PATCH JSON 只提交 share_slug
     */
    public function test_slug_can_be_updated_via_json(): void
    {
        $user = User::factory()->create();
        Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'share_slug' => null,
        ]);

        $res = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson('/my/profile', ['_method' => 'PATCH', 'share_slug' => 'new-slug']);

        $res->assertOk()->assertJsonPath('ok', true)->assertJsonPath('slug', 'new-slug');
        $this->assertSame('new-slug', Entrepreneur::first()->share_slug);
    }

    /**
     * 占用的 slug 不可保存：返回 422
     */
    public function test_taken_slug_rejected_via_json(): void
    {
        $owner = User::factory()->create();
        Entrepreneur::factory()->create(['user_id' => $owner->id, 'share_slug' => 'taken']);

        $user = User::factory()->create();
        Entrepreneur::factory()->create(['user_id' => $user->id, 'status' => Entrepreneur::STATUS_APPROVED]);

        $res = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->postJson('/my/profile', ['_method' => 'PATCH', 'share_slug' => 'taken']);

        $res->assertStatus(422);
    }
}
