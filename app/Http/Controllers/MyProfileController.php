<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileCreateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Entrepreneur;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MyProfileController extends Controller
{
    /**
     * 个人中心 - 展示编辑页面
     */
    public function show()
    {
        $entrepreneur = Auth::user()->entrepreneur;

        return view('profile.edit', [
            'entrepreneur' => $entrepreneur,
            'cities' => config('cities', []),
        ]);
    }

    /**
     * 更新个人档案
     * 核心：Policy 验证 user_id 匹配
     */
    public function update(ProfileUpdateRequest $request, AvatarService $avatarService)
    {
        $entrepreneur = Auth::user()->entrepreneur;

        if (!$entrepreneur) {
            return redirect()->back()->with('error', '您尚未创建企业家档案');
        }

        // Policy 自动验证：仅 user_id 匹配才可更新
        $this->authorize('update', $entrepreneur);

        $data = $request->validated();

        // share_slug 的唯一性：排除本人已有 slug（用户可修改自己的 slug）
        if (!empty($data['share_slug'])) {
            // 拒绝纯数字 slug
            if (ctype_digit($data['share_slug'])) {
                return redirect()->back()->withInput()->withErrors([
                    'share_slug' => '专属链接不能为纯数字',
                ]);
            }
            $taken = Entrepreneur::where('share_slug', $data['share_slug'])
                ->where('user_id', '!=', $entrepreneur->user_id)
                ->exists();
            if ($taken) {
                return redirect()->back()->withInput()->withErrors([
                    'share_slug' => '该专属链接已被使用，请换一个',
                ]);
            }
        }

        // 文件字段由下方单独处理（避免空文件输入把已有图片清空）
        unset($data['avatar'], $data['wechat_qrcode'], $data['portrait']);

        $newFiles = [];

        try {
            // 全部图片先校验并存储，任一失败则回滚已存文件（保证原子性）
            if ($request->hasFile('avatar')) {
                $newFiles['avatar'] = $avatarService->store($request->file('avatar'), 'avatars', 'avatar');
            }
            if ($request->hasFile('wechat_qrcode')) {
                $newFiles['wechat_qrcode'] = $avatarService->store($request->file('wechat_qrcode'), 'qrcodes', 'wechat_qrcode');
            }
            if ($request->hasFile('portrait')) {
                $newFiles['portrait'] = $avatarService->store($request->file('portrait'), 'portraits', 'portrait');
            }
        } catch (ValidationException $e) {
            // 回滚本次已存的新文件，避免残留
            foreach ($newFiles as $path) {
                $avatarService->delete($path);
            }
            throw $e;
        }

        // 全部成功后：记录旧图 → 落库 → 删旧图（失败不误删）
        $oldFiles = [
            'avatar' => $entrepreneur->avatar,
            'wechat_qrcode' => $entrepreneur->wechat_qrcode,
            'portrait' => $entrepreneur->portrait,
        ];

        // 直接 update：文本字段为空时（null）即清空生效
        $entrepreneur->update(array_merge($data, $newFiles));

        foreach ($newFiles as $field => $path) {
            if (!empty($oldFiles[$field])) {
                $avatarService->delete($oldFiles[$field]);
            }
        }

        return redirect()->back()->with('success', '信息更新成功！');
    }

    /**
     * 创建企业家档案
     */
    public function create(ProfileCreateRequest $request)
    {
        if (Auth::user()->entrepreneur) {
            return redirect()->route('profile.show');
        }

        $entrepreneur = Entrepreneur::create([
            'user_id' => Auth::id(),
            'name' => $request->validated('name'),
            'status' => Entrepreneur::STATUS_APPROVED,
        ]);

        return redirect()->route('profile.show')->with('success', '档案创建成功！');
    }

    /**
     * 短链可用性实时校验（AJAX）。
     * 返回 {"available": bool}，slug 被本人占用时视为可用。
     */
    public function checkSlug(Request $request): array
    {
        $request->validate([
            'slug' => 'required|string|min:3|max:40|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ]);

        $slug   = $request->input('slug');

        // 拒绝纯数字 slug：避免与 /entrepreneurs/{id} 旧路由解析冲突
        if (ctype_digit($slug)) {
            return ['available' => false];
        }
        $userId = Auth::id();

        $taken = Entrepreneur::where('share_slug', $slug)
            ->when($userId, fn ($q) => $q->where('user_id', '!=', $userId))
            ->exists();

        return ['available' => !$taken];
    }

    /**
     * 发起推荐申请
     * 需填写申请理由；被拒后 15 天冷却期内不可再申请
     */
    public function requestFeatured(Request $request)
    {
        $entrepreneur = Auth::user()->entrepreneur;

        if (!$entrepreneur) {
            return redirect()->back()->with('error', '您尚未创建企业家档案');
        }

        $this->authorize('requestFeatured', $entrepreneur);

        // 认证门槛：仅已通过认证的档案可申请推荐
        if ($entrepreneur->status !== Entrepreneur::STATUS_APPROVED) {
            return redirect()->back()->with('error', '档案通过认证后方可申请推荐');
        }
        if ($entrepreneur->is_featured) {
            return redirect()->back()->with('error', '您已是推荐企业家');
        }
        if ($entrepreneur->featured_request_status === Entrepreneur::FEATURED_STATUS_PENDING) {
            return redirect()->back()->with('error', '您的推荐申请正在审核中');
        }

        // 冷却期：被拒后 15 天内不可再申请
        $cooldownUntil = $entrepreneur->featured_rejected_at?->addDays(Entrepreneur::FEATURED_COOLDOWN_DAYS);
        if ($cooldownUntil && $cooldownUntil->isFuture()) {
            $days = $cooldownUntil->diffInDays(now()) + 1;
            return redirect()->back()->with('error', "推荐申请被拒后需等待 {$days} 天后再次申请");
        }

        // 申请理由必填
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $entrepreneur->update([
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_PENDING,
            'featured_requested_at'   => now(),
            'featured_reason'         => $data['reason'],
            'featured_rejected_at'    => null,
        ]);

        return redirect()->back()->with('success', '推荐申请已提交，请等待管理员审核');
    }
}
