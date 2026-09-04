<?php

namespace App\Policies;

use App\Models\Entrepreneur;
use App\Models\User;

class EntrepreneurPolicy
{
    /**
     * 用户是否可以更新自己的企业家档案
     * 核心：user_id 匹配验证
     */
    public function update(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->id === $entrepreneur->user_id;
    }

    /**
     * 用户是否可以查看某个企业家档案
     * 原则：仅可查看已认证的企业家
     *
     * 注意：当前通过 Controller 层 scopeApproved 显式过滤实现，
     * 此方法保留用于未来 API 路由或直接模型查询时的授权检查。
     */
    public function view(User $user, Entrepreneur $entrepreneur): bool
    {
        return $entrepreneur->status === Entrepreneur::STATUS_APPROVED;
    }

    /**
     * 用户是否可以删除企业家档案
     * 原则：仅用户本人或管理员可删除
     */
    public function delete(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->id === $entrepreneur->user_id || $user->is_admin;
    }

    /**
     * 用户是否可以审批企业家档案
     * 原则：仅管理员可操作
     */
    public function approve(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->is_admin;
    }

    /**
     * 用户是否可以拒绝企业家档案
     * 原则：仅管理员可操作
     */
    public function reject(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->is_admin;
    }

    /**
     * 用户是否可以设置推荐企业家
     * 原则：仅管理员可操作
     */
    public function toggleFeatured(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->is_admin;
    }

    /**
     * 用户是否可以发起自己的推荐申请
     * 原则：仅本人可申请
     */
    public function requestFeatured(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->id === $entrepreneur->user_id;
    }

    /**
     * 用户是否可以审核推荐申请
     * 原则：仅管理员可操作
     */
    public function reviewFeatured(User $user, Entrepreneur $entrepreneur): bool
    {
        return $user->is_admin;
    }
}
