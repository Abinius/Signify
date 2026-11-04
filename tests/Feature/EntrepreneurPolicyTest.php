<?php

namespace Tests\Feature;

use App\Models\Entrepreneur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrepreneurPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 用户可以更新自己的企业家档案
     */
    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patch('/my/profile', [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
        ]);
    }

    /**
     * 用户不能更新他人的企业家档案
     */
    public function test_user_cannot_update_others_profile(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $entrepreneurB = Entrepreneur::factory()->create(['user_id' => $userB->id]);

        $this->actingAs($userA);

        // 尝试直接访问其他用户的档案更新路由
        $response = $this->patch('/my/profile', [
            'name' => 'Hacked Name',
        ]);

        // 验证数据库中该记录未被修改
        $this->assertDatabaseMissing('entrepreneurs', [
            'id' => $entrepreneurB->id,
            'name' => 'Hacked Name',
        ]);
    }

    /**
     * 未登录用户不能访问个人中心
     */
    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get('/my/profile');

        $response->assertRedirect('/login');
    }

    /**
     * 用户可以更新职务字段
     */
    public function test_user_can_update_title(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patch('/my/profile', [
            'title' => '创始人 / CEO',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('entrepreneurs', [
            'id' => $entrepreneur->id,
            'title' => '创始人 / CEO',
        ]);
    }

    /**
     * 企业家库支持搜索功能
     */
    public function test_entrepreneurs_index_search(): void
    {
        $target = Entrepreneur::factory()->create([
            'name' => '张三',
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);
        $other = Entrepreneur::factory()->create([
            'name' => '李四',
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);

        $response = $this->get('/entrepreneurs?search=张三');

        $response->assertSee('张三');
        $response->assertDontSee('李四');
    }

    /**
     * 企业家库支持分页
     */
    public function test_entrepreneurs_index_pagination(): void
    {
        Entrepreneur::factory()->count(15)->create([
            'status' => Entrepreneur::STATUS_APPROVED,
            'is_featured' => true,
        ]);

        $response = $this->get('/entrepreneurs?page=1');

        $response->assertSee('上一页');
        $response->assertSee('下一页');
    }

    /**
     * 用户上传头像
     */
    public function test_user_can_upload_avatar(): void
    {
        $user = User::factory()->create();
        $entrepreneur = Entrepreneur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patch('/my/profile', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertSessionHas('success');
        $this->assertNotNull($entrepreneur->fresh()->avatar);
    }
}
