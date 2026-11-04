<?php

namespace App\Notifications;

use App\Models\Entrepreneur;
use Illuminate\Notifications\Notification;

class EntrepreneurNotification extends Notification
{
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const FEATURED_SUBMITTED = 'featured_submitted';
    public const FEATURED_APPROVED = 'featured_approved';
    public const FEATURED_REJECTED = 'featured_rejected';

    public function __construct(
        public string $type,
        public Entrepreneur $entrepreneur,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $name = $this->entrepreneur->name;
        $show = route('entrepreneurs.show', $this->entrepreneur->share_slug ?? $this->entrepreneur->id);

        return match ($this->type) {
            self::APPROVED => [
                'kind' => 'approved',
                'title' => '认证已通过',
                'body' => "您的企业家档案「{$name}」认证申请已通过。",
                'url' => $show,
            ],
            self::REJECTED => [
                'kind' => 'rejected',
                'title' => '认证未通过',
                'body' => "您的企业家档案「{$name}」认证申请未通过。",
                'url' => $show,
            ],
            self::FEATURED_SUBMITTED => [
                'kind' => 'featured_submitted',
                'title' => '新的推荐申请',
                'body' => "{$name} 提交了推荐申请，等待审核。",
                'url' => route('admin.dashboard'),
            ],
            self::FEATURED_APPROVED => [
                'kind' => 'featured_approved',
                'title' => '推荐申请已通过',
                'body' => "您「{$name}」的推荐申请已通过，已进入智库推荐。",
                'url' => $show,
            ],
            self::FEATURED_REJECTED => [
                'kind' => 'featured_rejected',
                'title' => '推荐申请未通过',
                'body' => "您「{$name}」的推荐申请未通过，15 天后可再次申请。",
                'url' => route('profile.show'),
            ],
        };
    }
}
