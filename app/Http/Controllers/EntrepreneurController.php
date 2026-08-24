<?php

namespace App\Http\Controllers;

use App\Models\Entrepreneur;
use App\Models\EntrepreneurView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntrepreneurController extends Controller
{
    /**
     * 首页：登录墙。登录后跳个人详情；无档案则引导创建。
     */
    public function home()
    {
        $entrepreneur = Auth::user()->entrepreneur;

        return $entrepreneur
            ? redirect()->route('entrepreneurs.show', $entrepreneur->share_slug ?? $entrepreneur->id)
            : redirect()->route('profile.show');
    }

    /**
     * 企业家库列表
     */
    public function index(Request $request)
    {
        $entrepreneurs = Entrepreneur::approved()
            ->featured()
            ->search($request->get('search'))
            ->when($request->get('industry'), function ($query, $industry) {
                $query->where('industry', $industry);
            })
            ->when($request->get('city'), function ($query, $city) {
                $query->where('city', $city);
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $industries = Entrepreneur::approved()->featured()->pluck('industry')->filter()->unique()->sort()->values();
        $cities = Entrepreneur::approved()->featured()->pluck('city')->filter()->unique()->sort()->values();

        return view('entrepreneurs.index', [
            'entrepreneurs' => $entrepreneurs,
            'industries' => $industries,
            'cities' => $cities,
            'filters' => $request->only(['search', 'industry', 'city']),
        ]);
    }

    /**
     * 企业家详情。
     *
     * 双入口：
     * - `/u/{slug}` → 短链入口（named route: entrepreneurs.show），按 share_slug 查找
     * - `/entrepreneurs/{id}` → 旧数字链接兼容入口（无名路由），按 id 查找
     *
     * 按路由名区分查询字段，避免 slug 与 id 混淆（如 /u/123 不会误落到 id=123）。
     * 访客仅可见已认证档案；本人可见自己的档案（含待审核/已拒绝）。
     */
    public function show(Request $request, string $idOrSlug)
    {
        $entrepreneur = Entrepreneur::when(
            $request->routeIs('entrepreneurs.show'),
            fn ($q) => $q->where('share_slug', $idOrSlug),
            fn ($q) => $q->where('id', (int) $idOrSlug),
        )
            ->where(function ($q) {
                $q->where('status', Entrepreneur::STATUS_APPROVED)
                    ->orWhere('user_id', Auth::id());
            })
            ->firstOrFail();

        $this->trackView($request, $entrepreneur);

        return view('entrepreneurs.show', [
            'entrepreneur' => $entrepreneur->load('user'),
        ]);
    }

    /**
     * 访客浏览计数：同一会话 24 小时内只计 1 次（刷新/爬虫不重复计入）。
     * 档案本人浏览自己的页面不计入访客数。
     */
    private function trackView(Request $request, Entrepreneur $entrepreneur): void
    {
        // 本人浏览自己的档案不计入（避免虚增浏览量）
        if (Auth::id() === $entrepreneur->user_id) {
            return;
        }

        $sessionKey = 'viewed_entrepreneur_' . $entrepreneur->id;
        $lastView = $request->session()->get($sessionKey);

        if ($lastView && $lastView->gt(now()->subHours(24))) {
            return;
        }

        $request->session()->put($sessionKey, now());
        $entrepreneur->increment('view_count');
        EntrepreneurView::create([
            'entrepreneur_id' => $entrepreneur->id,
            'session_key' => $request->session()->getId(),
        ]);
    }
}
