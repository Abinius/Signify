<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BatchActionRequest;
use App\Models\Entrepreneur;
use App\Models\Setting;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * 管理员后台首页 - 待审核列表（认证申请 / 推荐申请）
     */
    public function dashboard()
    {
        $pending = Entrepreneur::where('status', Entrepreneur::STATUS_PENDING)
            ->with('user')
            ->latest()
            ->paginate(10, ['*'], 'page_cert')
            ->withQueryString();

        $featuredPending = Entrepreneur::featuredPending()
            ->with('user')
            ->latest('featured_requested_at')
            ->paginate(10, ['*'], 'page_featured')
            ->withQueryString();

        $featuredPendingCount = Entrepreneur::featuredPending()->count();

        // P1优化：合并4次count为一次子查询
        $statsRaw = Entrepreneur::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = '" . Entrepreneur::STATUS_PENDING . "' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = '" . Entrepreneur::STATUS_APPROVED . "' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = '" . Entrepreneur::STATUS_REJECTED . "' THEN 1 ELSE 0 END) as rejected
        ")->first();

        $stats = [
            'total' => (int) $statsRaw->total,
            'pending' => (int) $statsRaw->pending,
            'approved' => (int) $statsRaw->approved,
            'rejected' => (int) $statsRaw->rejected,
            'featuredPending' => $featuredPendingCount,
        ];

        return view('admin.dashboard', [
            'pending' => $pending,
            'featuredPending' => $featuredPending,
            'stats' => $stats,
        ]);
    }

    /**
     * 所有企业家列表
     */
    public function entrepreneurs(Request $request)
    {
        $entrepreneurs = Entrepreneur::with('user')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->search($request->get('search'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.entrepreneurs', [
            'entrepreneurs' => $entrepreneurs,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * 审批通过（Policy验证）
     */
    public function approve(Entrepreneur $entrepreneur)
    {
        $this->authorize('approve', $entrepreneur);

        $entrepreneur->update(['status' => Entrepreneur::STATUS_APPROVED]);

        return redirect()->back()->with('success', "已通过 {$entrepreneur->name} 的认证申请");
    }

    /**
     * 审批拒绝（Policy验证）
     */
    public function reject(Entrepreneur $entrepreneur)
    {
        $this->authorize('reject', $entrepreneur);

        $entrepreneur->update(['status' => Entrepreneur::STATUS_REJECTED]);

        return redirect()->back()->with('success', "已拒绝 {$entrepreneur->name} 的认证申请");
    }

    /**
     * 设为推荐 / 取消推荐（Policy验证）
     * 同步推荐申请状态：设为推荐 → approved；取消推荐 → 重置为 null（允许重新申请）
     */
    public function toggleFeatured(Entrepreneur $entrepreneur)
    {
        $this->authorize('toggleFeatured', $entrepreneur);

        $next = !$entrepreneur->is_featured;
        $entrepreneur->update([
            'is_featured' => $next,
            'featured_request_status' => $next ? Entrepreneur::FEATURED_STATUS_APPROVED : null,
            'featured_rejected_at' => null,
        ]);

        $status = $next ? '设为推荐' : '取消推荐';
        return redirect()->back()->with('success', "已将 {$entrepreneur->name} {$status}");
    }

    /**
     * 通过推荐申请 → 设为推荐（进入智库）（Policy验证）
     */
    public function approveFeatured(Entrepreneur $entrepreneur)
    {
        $this->authorize('reviewFeatured', $entrepreneur);

        $entrepreneur->update([
            'is_featured' => true,
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_APPROVED,
            'featured_rejected_at' => null,
        ]);

        return redirect()->back()->with('success', "已通过 {$entrepreneur->name} 的推荐申请");
    }

    /**
     * 拒绝推荐申请（写入拒绝时间，作为 15 天冷却期起点）（Policy验证）
     */
    public function rejectFeatured(Entrepreneur $entrepreneur)
    {
        $this->authorize('reviewFeatured', $entrepreneur);

        $entrepreneur->update([
            'featured_request_status' => Entrepreneur::FEATURED_STATUS_REJECTED,
            'featured_rejected_at' => now(),
        ]);

        return redirect()->back()->with('success', "已拒绝 {$entrepreneur->name} 的推荐申请");
    }

    /**
     * 删除企业家档案（Policy验证）
     */
    public function destroy(Entrepreneur $entrepreneur)
    {
        $this->authorize('delete', $entrepreneur);

        $name = $entrepreneur->name;
        $entrepreneur->delete();

        return redirect()->route('admin.entrepreneurs')->with('success', "已删除 {$name}");
    }

    /**
     * 批量审批通过
     */
    public function batchApprove(BatchActionRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        $entrepreneurs = Entrepreneur::whereIn('id', $validated['ids'])->get();
        $approvedCount = 0;

        DB::transaction(function () use ($entrepreneurs, $user, &$approvedCount) {
            foreach ($entrepreneurs as $entrepreneur) {
                if ($user->can('approve', $entrepreneur)) {
                    $entrepreneur->update(['status' => Entrepreneur::STATUS_APPROVED]);
                    $approvedCount++;
                }
            }
        });

        return redirect()->back()->with('success', "已批量通过 {$approvedCount} 条申请");
    }

    /**
     * 批量拒绝
     */
    public function batchReject(BatchActionRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        $entrepreneurs = Entrepreneur::whereIn('id', $validated['ids'])->get();
        $rejectedCount = 0;

        DB::transaction(function () use ($entrepreneurs, $user, &$rejectedCount) {
            foreach ($entrepreneurs as $entrepreneur) {
                if ($user->can('reject', $entrepreneur)) {
                    $entrepreneur->update(['status' => Entrepreneur::STATUS_REJECTED]);
                    $rejectedCount++;
                }
            }
        });

        return redirect()->back()->with('success', "已批量拒绝 {$rejectedCount} 条申请");
    }

    /**
     * 系统设置页
     */
    public function settings()
    {
        $values = Setting::get(); // 全量
        $defaults = $this->settingDefaults();

        return view('admin.settings', [
            'values' => array_merge($defaults, $values ?: []),
        ]);
    }

    /**
     * 保存系统设置
     */
    public function updateSettings(Request $request, AvatarService $avatarService)
    {
        $data = $request->validate([
            'site_name'         => 'required|string|max:100',
            'site_description'  => 'nullable|string|max:500',
            'share_title'       => 'nullable|string|max:200',
            'share_description' => 'nullable|string|max:500',
            'share_image'       => 'nullable|url|max:500',
            'share_image_file'  => 'nullable|file|max:2048',
            'footer_copyright'  => 'nullable|string|max:200',
            'icp_number'        => 'nullable|string|max:100',
        ]);

        unset($data['share_image_file']);

        // 上传优先：选择了图片文件则覆盖 share_image（复用 AvatarService，兼容缺 fileinfo）
        if ($request->hasFile('share_image_file')) {
            try {
                $data['share_image'] = url('storage/' . $avatarService->store($request->file('share_image_file'), 'settings', 'share_image_file'));
            } catch (ValidationException $e) {
                // 取 store() 抛出的具体错误消息，挂到 share_image_file 字段
                throw ValidationException::withMessages([
                    'share_image_file' => collect($e->errors())->flatten()->first() ?? '图片格式不支持',
                ]);
            }
        }

        foreach ($data as $key => $value) {
            // 空字符串存为 null，使 Setting::get 的 ?? 兜底默认值生效（如清空分享图/备案号）
            Setting::updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
        }
        Setting::flush();

        return redirect()->route('admin.settings')->with('success', '系统设置已保存');
    }

    /**
     * 设置项默认值
     */
    private function settingDefaults(): array
    {
        return [
            'site_name'         => 'SIGNIFY',
            'site_description'  => '不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。',
            'share_title'       => 'SIGNIFY — 每一份引领行业的商业远见，都值得被更广泛地看见',
            'share_description' => '不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。',
            'share_image'       => asset('android-chrome-512x512.png'),
            'footer_copyright'  => '© SIGNIFY',
            'icp_number'        => '',
        ];
    }
}