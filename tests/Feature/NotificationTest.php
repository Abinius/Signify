<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use App\Notifications\EntrepreneurNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理员通过认证 → 档案所属用户收到站内通知
     */
    public function test_approve_notifies_the_entrepreneur_user(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_PENDING,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post("/admin/entrepreneurs/{$entrepreneur->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => EntrepreneurNotification::class,
        ]);

        $n = $user->fresh()->unreadNotifications->first();
        $this->assertSame('认证已通过', $n->data['title']);
    }

    /**
     * 用户发起推荐申请 → 全体管理员收到站内通知
     */
    public function test_featured_request_notifies_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create([
            'user_id' => $user->id,
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => false,
        ]);

        $this->actingAs($user)
            ->post('/my/profile/featured-request', ['reason' => '行业洞察'])
            ->assertSessionHas('success');

        Notification::assertSentTo($admin, EntrepreneurNotification::class, function ($n) {
            return $n->type === EntrepreneurNotification::FEATURED_SUBMITTED;
        });
    }

    /**
     * 未读通知接口返回当前用户的未读列表
     */
    public function test_unread_endpoint_returns_unread_notifications(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));

        $this->actingAs($user)
            ->get('/notifications/unread')
            ->assertOk()
            ->assertJsonPath('0.title', '认证已通过');
    }

    /**
     * 标记已读后通知移出未读列表
     */
    public function test_mark_read_clears_unread(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $id = $user->fresh()->unreadNotifications->first()->id;

        $this->actingAs($user)
            ->post("/notifications/{$id}/read")
            ->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications->count());
    }

    /**
     * index 接口返回已读+未读，含 kind 与 read_at 字段
     */
    public function test_index_returns_all_with_kind_and_read_at(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::REJECTED, $entrepreneur));
        $user->fresh()->notifications()->first()->markAsRead();

        $res = $this->actingAs($user)->get('/notifications')->assertOk();

        $this->assertCount(2, $res->json());
        $this->assertNotNull($res->json('0.read_at'));
        $this->assertNull($res->json('1.read_at'));
        $this->assertSame('approved', $res->json('0.kind'));
        $this->assertSame('rejected', $res->json('1.kind'));
    }

    /**
     * 全部标记已读接口清空未读
     */
    public function test_mark_all_read_clears_unread(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::FEATURED_APPROVED, $entrepreneur));

        $this->assertSame(2, $user->fresh()->unreadNotifications->count());

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications->count());
    }

    /**
     * 通知数据含 kind 字段供前端图标区分
     */
    public function test_notification_data_contains_kind(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::FEATURED_SUBMITTED, $entrepreneur));

        $this->assertSame(
            'featured_submitted',
            $user->fresh()->notifications->first()->data['kind']
        );
    }
}
