<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正确当前密码 → 改密成功，DB 中密码已更新
     */
    public function test_user_can_update_password_with_correct_current(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    /**
     * AJAX 请求成功 → 返回 JSON，供设置抽屉使用
     */
    public function test_ajax_update_returns_json(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->putJson('/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    /**
     * 错误当前密码 → 被拒，密码不变
     */
    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
