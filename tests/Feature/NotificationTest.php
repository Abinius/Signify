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
     * 通知列表页渲染已读+未读（含标题）
     */
    public function test_index_page_lists_all_notifications(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::REJECTED, $entrepreneur));
        $user->fresh()->notifications()->first()->markAsRead();

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('认证已通过')
            ->assertSee('认证未通过');
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

    /**
     * recent 接口返回已读+未读，且每条带 read 字段
     */
    public function test_recent_returns_read_and_unread_with_read_flag(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::REJECTED, $entrepreneur));
        $user->fresh()->notifications()->first()->markAsRead();

        $this->actingAs($user)
            ->get('/notifications/recent')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.read', true)
            ->assertJsonPath('1.read', false);
    }

    /**
     * unread 接口契约不变：只返回未读
     */
    public function test_unread_excludes_read(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        $user->notify(new EntrepreneurNotification(EntrepreneurNotification::REJECTED, $entrepreneur));
        $user->fresh()->notifications()->first()->markAsRead();

        $this->actingAs($user)
            ->get('/notifications/unread')
            ->assertOk()
            ->assertJsonCount(1);
    }

    /**
     * 通知列表页分页：16 条时第一页 15 条，存在下一页
     */
    public function test_index_paginates_at_15(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        for ($i = 0; $i < 16; $i++) {
            $user->notify(new EntrepreneurNotification(EntrepreneurNotification::APPROVED, $entrepreneur));
        }

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk()->assertSee('page=2');
        $this->assertCount(15, $user->fresh()->notifications()->orderByDesc('created_at')->paginate(15)->items());
    }
}
