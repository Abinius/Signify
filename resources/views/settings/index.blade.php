@extends('layouts.app')

@section('title', '设置')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-16"
     x-data='settingsModal({
       mode: "page",
       summaryUrl: @json(route('profile.summary')),
       passwordUrl: @json(route('password.update')),
       csrf: @json(csrf_token()),
     })' x-init="boot()">
  <div class="text-center mb-10">
    <h1 class="font-display text-display-lg font-black text-ink">SETTINGS</h1>
  </div>

  <div class="bento">
    <template x-if="loading">
      <div class="bento-card md:col-span-2 space-y-2">
        <div class="h-4 w-2/3 bg-surface"></div>
        <div class="h-8 w-full bg-surface"></div>
      </div>
    </template>

    <template x-if="!loading && !s.has_entrepreneur">
      <div class="bento-card md:col-span-2 text-center py-10">
        <p class="text-sm text-muted mb-3">尚未创建企业家档案</p>
        <a href="@json(route('profile.show'))" class="label-caption text-accent hover:opacity-70 transition-opacity">前往创建 →</a>
      </div>
    </template>

    <template x-if="!loading && s.has_entrepreneur">
      <div class="contents">
        {{-- 认证：重要信息，横跨两列、占两格 --}}
        <div class="bento-card md:col-span-2 md:row-span-2 flex flex-col items-center justify-center text-center gap-2">
          <p class="label-caption text-muted">认证</p>
          <span class="font-display text-display-md font-bold"
                :class="statusColor(s.status)"
                x-text="s.status_label"></span>
        </div>

        {{-- 推荐：横跨两列（申请理由输入较宽） --}}
        <div class="bento-card md:col-span-2">
          <p class="label-caption text-muted mb-1.5">推荐</p>
          <template x-if="s.featured_label">
            <span class="inline-flex items-center gap-1.5 label-caption"
                  :class="featuredColor(s)"
                  x-text="s.featured_label"></span>
          </template>
          <template x-if="s.can_apply_featured">
            <div class="mt-2">
              <button type="button" x-show="!applying" @click="applying = true"
                      class="label-caption text-accent hover:opacity-70 transition-opacity">申请推荐 →</button>
              <div x-show="applying" x-cloak class="space-y-2">
                <textarea x-model="reason" rows="3" placeholder="请简要说明申请理由"
                          class="input-line resize-none text-sm w-full"></textarea>
                <p x-show="featuredError" x-cloak class="field-error" x-text="featuredError"></p>
                <div class="flex items-center gap-3 justify-center">
                  <button type="button" @click="applying = false; reason = ''; featuredError = ''"
                          class="label-caption text-muted hover:text-ink">取消</button>
                  <button type="button" @click="applyFeatured()"
                          class="btn-ink !py-1.5 !px-4 text-sm">提交</button>
                </div>
              </div>
            </div>
          </template>
        </div>

        {{-- 专属链接 --}}
        <div class="bento-card">
          <p class="label-caption text-muted mb-1.5">专属链接</p>
          <div class="flex items-stretch border border-hairline">
            <span class="px-2 flex items-center text-xs text-muted bg-surface border-r border-hairline">/u/</span>
            <input type="text" x-model="draft" @input.debounce.400ms="checkSlug()" @blur="onSlugBlur()"
                   :disabled="saving"
                   pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                   class="flex-1 px-2 py-2 text-sm bg-paper text-ink outline-none min-w-0 disabled:opacity-60">
          </div>
          <div class="flex items-center justify-between mt-1.5 min-h-[16px]">
            <p x-show="slugMsg" x-cloak class="text-[11px]"
               :class="slugOk ? 'text-status-success' : 'text-status-danger'"
               x-text="slugMsg"></p>
            <span x-show="saved" x-cloak class="text-[11px] text-status-success">已保存 ✓</span>
          </div>
        </div>

        {{-- 名片 --}}
        <div class="bento-card text-center">
          <a :href="s.card_url"
             class="inline-flex items-center gap-1.5 label-caption text-accent hover:opacity-70 transition-opacity">
            查看我的名片 →
          </a>
          <div class="mt-2">
            <button type="button" @click="copyCard()"
                    class="label-caption text-muted hover:text-ink transition-colors">
              <span x-show="!copied && !copyFailed">复制链接</span>
              <span x-show="copied" x-cloak class="text-status-success">已复制 ✓</span>
              <span x-show="copyFailed" x-cloak class="text-status-danger">复制失败</span>
            </button>
          </div>
        </div>
      </div>
    </template>

    {{-- 修改密码（所有登录用户均可）：横跨两列 --}}
    <div class="bento-card md:col-span-2">
      <button type="button" @click="pwOpen = !pwOpen" :aria-expanded="pwOpen" aria-controls="pw-panel"
              class="w-full flex items-center justify-between">
        <span class="label-caption text-muted">修改密码</span>
        <span class="label-caption text-muted transition-transform" :class="pwOpen ? 'rotate-180' : ''">▾</span>
      </button>
      <div x-show="pwOpen" x-cloak id="pw-panel" class="mt-3 space-y-2">
        <label class="block label-caption text-muted">当前密码</label>
        <input type="password" x-model="pwCurrent"
               autocomplete="current-password" class="input-line text-sm w-full">
        <label class="block label-caption text-muted">新密码</label>
        <input type="password" x-model="pwNew"
               autocomplete="new-password" minlength="6" class="input-line text-sm w-full">
        <label class="block label-caption text-muted">确认新密码</label>
        <input type="password" x-model="pwConfirm"
               autocomplete="new-password" class="input-line text-sm w-full">
        <p x-show="pwError" x-cloak class="field-error" x-text="pwError"></p>
        <p x-show="pwOk" x-cloak class="text-[11px] text-status-success">密码已更新 ✓</p>
        <button type="button" @click="submitPassword()" :disabled="pwBusy"
                class="btn-ink !py-1.5 !px-4 text-sm w-full disabled:opacity-60">
          更新密码
        </button>
      </div>
    </div>
  </div>
</div>
@endsection
