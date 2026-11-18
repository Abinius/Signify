<script>
document.addEventListener('alpine:init', () => {
  Alpine.store('ui', { unread: 0, pulse: false });
});
</script>

@auth
<div x-data='settingsModal({
      summaryUrl: @json(route('profile.summary')),
      unreadUrl:  @json(route('notifications.unread')),
      recentUrl:  @json(route('notifications.recent')),
      readUrl:    @json(route('notifications.read', '__ID__')),
      readAllUrl: @json(route('notifications.read-all')),
      passwordUrl: @json(route('password.update')),
      csrf:       @json(csrf_token()),
    })' x-init="boot()"
     @open-settings.window="openFromMenu($event.detail && $event.detail.tab)">

  {{-- 全屏面板 --}}
  <div x-show="open" x-cloak
       role="dialog" aria-modal="true" aria-label="设置"
       @keydown.escape.window="open = false"
       class="fixed inset-0 z-[200] bg-paper flex flex-col">

    {{-- 顶部栏 --}}
    <div class="flex items-center justify-between px-6 py-5">
      <img src="{{ asset('icons/logo.svg') }}" alt="{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}" class="h-6 w-auto">
      <button type="button" @click="open = false" aria-label="关闭设置"
              class="w-11 h-11 flex items-center justify-center text-ink hover:opacity-60 transition-opacity">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-6 h-6">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    {{-- Tab 栏 --}}
    <div class="px-6 border-b border-hairline flex items-center justify-between gap-3">
      <div class="flex items-center gap-4">
        <button type="button" @click="tab='mine'"
                :class="tab==='mine' ? 'text-ink font-bold' : 'text-muted hover:text-ink'"
                class="label-caption transition-colors">设置</button>
        <button type="button" @click="tab='notif'"
                :class="tab==='notif' ? 'text-ink font-bold' : 'text-muted hover:text-ink'"
                class="label-caption transition-colors flex items-center gap-1">
          通知
          <span x-show="unread > 0" x-cloak
                class="inline-flex items-center justify-center min-w-[16px] h-[16px] px-1 rounded-full
                       bg-status-danger text-paper text-[9px] leading-[16px] font-bold"
                x-text="unread > 99 ? '99+' : unread"></span>
        </button>
      </div>
      <div class="flex items-center gap-3">
        @if(auth()->user()->is_admin && !request()->routeIs('admin.*'))
          <a href="{{ route('admin.dashboard') }}" class="label-caption text-accent hover:opacity-70 transition-opacity">后台 →</a>
        @endif
        <button type="button" x-show="tab==='notif' && unread > 0" x-cloak
                @click="markAllRead()"
                class="label-caption text-muted hover:text-accent transition-colors">全部已读</button>
      </div>
    </div>

    {{-- 内容区 --}}
    <div class="flex-1 overflow-y-auto overscroll-contain">
      <div class="max-w-2xl mx-auto px-6 py-10">

        {{-- 设置 --}}
        <div x-show="tab==='mine'" class="space-y-3">
          <template x-if="loading">
            <div class="px-4 py-6 space-y-2">
              <div class="h-4 w-2/3 bg-surface"></div>
              <div class="h-8 w-full bg-surface"></div>
            </div>
          </template>

          <template x-if="!loading && !s.has_entrepreneur">
            <div class="px-4 py-6 text-center">
              <p class="text-sm text-muted mb-3">尚未创建企业家档案</p>
              <a href="@json(route('profile.show'))" class="label-caption text-accent hover:opacity-70 transition-opacity">前往创建 →</a>
            </div>
          </template>

          <template x-if="!loading && s.has_entrepreneur">
            <div class="space-y-3">
              {{-- 编辑资料 --}}
              <div class="border border-hairline p-3 text-center">
                <a href="@json(route('profile.show'))"
                   class="inline-flex items-center gap-1.5 label-caption text-accent hover:opacity-70 transition-opacity">
                  编辑资料 →
                </a>
              </div>

              {{-- 认证 --}}
              <div class="border border-hairline p-3">
                <p class="label-caption text-muted mb-1.5">认证</p>
                <span class="inline-flex items-center gap-1.5 label-caption"
                      :class="statusColor(s.status)"
                      x-text="s.status_label"></span>
              </div>

              {{-- 推荐 --}}
              <div class="border border-hairline p-3">
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
                      <div class="flex items-center gap-3 justify-end">
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
              <div class="border border-hairline p-3">
                <p class="label-caption text-muted mb-1.5">专属链接</p>
                <div class="flex items-stretch border border-hairline">
                  <span class="px-2 flex items-center text-xs text-muted bg-surface border-r border-hairline">/u/</span>
                  <input type="text" x-model="draft" @input.debounce.400ms="checkSlug()" @blur="onSlugBlur()"
                         placeholder="your-slug" :disabled="saving"
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
              <div class="border border-hairline p-3 text-center">
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

          {{-- 修改密码（所有登录用户均可） --}}
          <div class="border border-hairline p-3">
            <button type="button" @click="pwOpen = !pwOpen" :aria-expanded="pwOpen" aria-controls="pw-panel"
                    class="w-full flex items-center justify-between">
              <span class="label-caption text-muted">修改密码</span>
              <span class="label-caption text-muted transition-transform" :class="pwOpen ? 'rotate-180' : ''">▾</span>
            </button>
            <div x-show="pwOpen" x-cloak id="pw-panel" class="mt-3 space-y-2">
              <input type="password" x-model="pwCurrent" placeholder="当前密码"
                     autocomplete="current-password" class="input-line text-sm w-full">
              <input type="password" x-model="pwNew" placeholder="新密码（至少 6 位）"
                     autocomplete="new-password" class="input-line text-sm w-full">
              <input type="password" x-model="pwConfirm" placeholder="确认新密码"
                     autocomplete="new-password" class="input-line text-sm w-full">
              <p x-show="pwError" x-cloak class="field-error" x-text="pwError"></p>
              <p x-show="pwOk" x-cloak class="text-[11px] text-status-success">密码已更新 ✓</p>
              <button type="button" @click="submitPassword()" :disabled="pwBusy"
                      class="btn-ink !py-1.5 !px-4 text-sm w-full disabled:opacity-60">
                更新密码
              </button>
            </div>
          </div>

          {{-- 退出账户 --}}
          <div class="border border-hairline p-3 text-center">
            <form method="POST" action="{{ route('logout') }}"
                  onsubmit="return confirm('确认退出当前账户？')">
              @csrf
              <button type="submit" class="label-caption text-status-danger hover:opacity-70 transition-opacity">
                退出账户
              </button>
            </form>
          </div>
        </div>

        {{-- 通知 --}}
        <div x-show="tab==='notif'" class="divide-y divide-hairline">
          <template x-for="n in items" :key="n.id">
            <a :href="n.url || '#'" @click.prevent="onItemClick(n)"
               class="block px-4 py-3 hover:bg-surface transition-colors relative"
               :class="n.read ? 'opacity-60' : ''">
              <span x-show="!n.read" class="absolute left-0 top-0 bottom-0 w-[3px]"
                    :class="kindColor(n.kind)"></span>
              <div class="flex items-start gap-2">
                <span class="mt-0.5 shrink-0" x-html="kindIcon(n.kind)"></span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-bold text-ink truncate" x-text="n.title"></p>
                  <p class="text-xs text-muted mt-1 line-clamp-2" x-text="n.body"></p>
                  <p class="text-[10px] text-muted mt-1" x-text="n.created_at"></p>
                </div>
              </div>
            </a>
          </template>

          <div x-show="items.length === 0" x-cloak class="px-4 py-10 text-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"
                 class="w-8 h-8 mx-auto text-muted/60 mb-2">
              <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <p class="label-caption text-muted">暂无通知</p>
          </div>

          <div class="px-4 py-3 text-center">
            <a href="@json(route('notifications.index'))"
               class="label-caption text-accent hover:opacity-70 transition-opacity">查看全部通知 →</a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function settingsModal(config) {
  const ICONS = {
    approved:            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-status-success"><path d="M5 13l4 4L19 7"/></svg>',
    rejected:            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-status-danger"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    featured_submitted:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4 text-status-warning"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>',
    featured_approved:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4 text-status-success"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>',
    featured_rejected:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4 text-status-danger"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>',
  };
  const BARS = {
    approved: 'bg-status-success',
    rejected: 'bg-status-danger',
    featured_submitted: 'bg-status-warning',
    featured_approved: 'bg-status-success',
    featured_rejected: 'bg-status-danger',
  };
  const STATUS_COLOR = {
    pending:   'text-status-warning',
    approved:  'text-status-success',
    rejected:  'text-status-danger',
  };
  return {
    open: false,
    tab: 'mine',
    loading: false,
    s: { has_entrepreneur: false },
    items: [],
    unread: 0,
    lastUnread: 0,
    pulse: false,
    applying: false,
    reason: '',
    featuredError: '',
    draft: '',
    slugMsg: '',
    slugOk: false,
    saving: false,
    saved: false,
    busy: {},
    pwCurrent: '',
    pwNew: '',
    pwConfirm: '',
    pwError: '',
    pwOk: false,
    pwBusy: false,
    pwOpen: false,
    copied: false,
    copyFailed: false,
    ...config,
    kindIcon(k) { return ICONS[k] || ICONS.featured_submitted; },
    kindColor(k) { return BARS[k] || 'bg-muted'; },
    statusColor(st) { return STATUS_COLOR[st] || 'text-muted'; },
    featuredColor(s) {
      if (s.is_featured) return 'text-status-success';
      if (s.featured_request_status === 'pending') return 'text-status-warning';
      if (s.featured_request_status === 'rejected') return 'text-status-danger';
      return 'text-muted';
    },
    async openFromMenu(tab) {
      this.open = true;
      this.pwOpen = false;
      this.pwError = '';
      this.loadSummary();
      await this.loadNotifications();
      this.tab = tab === 'notif' ? 'notif' : 'mine';
    },
    async loadSummary() {
      this.loading = true;
      try {
        const res = await fetch(this.summaryUrl, { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
          this.s = await res.json();
          this.draft = this.s.slug || '';
        }
      } catch (e) {}
      this.loading = false;
    },
    async loadNotifications() {
      try {
        const [recentRes, unreadRes] = await Promise.all([
          fetch(this.recentUrl, { headers: { 'Accept': 'application/json' } }),
          fetch(this.unreadUrl, { headers: { 'Accept': 'application/json' } }),
        ]);
        if (recentRes.ok) this.items = await recentRes.json();
        if (unreadRes.ok) {
          const unreadItems = await unreadRes.json();
          this.unread = unreadItems.length;
          if (this.unread > this.lastUnread && this.lastUnread !== 0) this.pulse = true;
          this.lastUnread = this.unread;
          Alpine.store('ui').unread = this.unread;
          Alpine.store('ui').pulse = this.pulse;
          setTimeout(() => { this.pulse = false; Alpine.store('ui').pulse = false; }, 1500);
        }
      } catch (e) {}
    },
    async onItemClick(n) {
      if (n.read) {
        if (n.url) window.location.href = n.url;
        else this.open = false;
        return;
      }
      if (this.busy[n.id]) return;
      this.busy[n.id] = true;
      try {
        await fetch(this.readUrl.replace('__ID__', n.id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
        });
        n.read = true;
        this.unread = Math.max(0, this.unread - 1);
        Alpine.store('ui').unread = this.unread;
      } catch (e) {
        this.busy[n.id] = false;
      }
      if (n.url) window.location.href = n.url;
      else this.open = false;
    },
    async markAllRead() {
      try {
        await fetch(this.readAllUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
        });
      } catch (e) {}
      await this.loadNotifications();
    },
    async checkSlug() {
      this.slugMsg = '';
      this.slugOk = false;
      const v = this.draft.trim();
      if (!v) return;
      if (v.length < 3) { this.slugMsg = '至少 3 个字符'; return; }
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(v)) { this.slugMsg = '仅限小写字母、数字、短横线'; return; }
      if (v === this.s.slug) { this.slugMsg = '当前即此值'; this.slugOk = true; return; }
      try {
        const res = await fetch(this.s.check_slug_url + '?slug=' + encodeURIComponent(v), {
          headers: { 'Accept': 'application/json' },
        });
        if (res.ok) {
          const data = await res.json();
          if (this.draft.trim() === v) {
            this.slugOk = !!data.available;
            this.slugMsg = data.available ? '可用 ✓' : '已被占用';
          }
        }
      } catch (e) {}
    },
    async onSlugBlur() {
      const v = this.draft.trim();
      if (!v) {
        this.draft = this.s.slug || '';
        this.slugMsg = '';
        this.slugOk = false;
        return;
      }
      await this.checkSlug();
      if (!this.slugOk || v === this.s.slug) return;
      await this.saveSlug();
    },
    async saveSlug() {
      const v = this.draft.trim();
      if (v && (!this.slugOk && v !== this.s.slug)) {
        this.slugMsg = '请先通过校验';
        return;
      }
      this.saving = true;
      this.saved = false;
      try {
        const res = await fetch(this.s.update_url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ _method: 'PATCH', share_slug: v }),
        });
        if (res.ok) {
          const data = await res.json();
          this.s.slug = data.slug || v;
          this.draft = this.s.slug || '';
          this.saved = true;
          this.slugMsg = '';
          setTimeout(() => this.saved = false, 2000);
        } else if (res.status === 422) {
          const data = await res.json();
          this.slugMsg = data.errors?.share_slug?.[0] || '校验失败';
          this.slugOk = false;
        } else {
          this.slugMsg = '保存失败';
        }
      } catch (e) {
        this.slugMsg = '网络错误';
      }
      this.saving = false;
    },
    async applyFeatured() {
      this.featuredError = '';
      if (!this.reason.trim()) {
        this.featuredError = '请填写申请理由';
        return;
      }
      try {
        const res = await fetch(this.s.featured_url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ reason: this.reason }),
        });
        if (res.ok) {
          this.applying = false;
          this.reason = '';
          this.loadSummary();
          return;
        }
        if (res.status === 422) {
          const data = await res.json();
          this.featuredError = data.errors?.featured?.[0] || data.message || '提交失败';
          return;
        }
        this.featuredError = '提交失败，请稍后再试';
      } catch (e) {
        this.featuredError = '网络错误';
      }
    },
    async copyCard() {
      const url = this.s.card_url;
      if (!url) return;
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(url);
        } else {
          const ta = document.createElement('textarea');
          ta.value = url;
          ta.style.position = 'fixed';
          ta.style.opacity = '0';
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          ta.remove();
        }
        this.copied = true;
        this.copyFailed = false;
        setTimeout(() => { this.copied = false; }, 2000);
      } catch (e) {
        this.copyFailed = true;
        this.copied = false;
        setTimeout(() => { this.copyFailed = false; }, 2000);
      }
    },
    async submitPassword() {
      this.pwError = '';
      this.pwOk = false;
      if (!this.pwCurrent || !this.pwNew) {
        this.pwError = '请填写完整';
        return;
      }
      if (this.pwNew !== this.pwConfirm) {
        this.pwError = '两次新密码不一致';
        return;
      }
      this.pwBusy = true;
      try {
        const res = await fetch(this.passwordUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            _method: 'PUT',
            current_password: this.pwCurrent,
            password: this.pwNew,
            password_confirmation: this.pwConfirm,
          }),
        });
        if (res.ok) {
          this.pwOk = true;
          this.pwCurrent = this.pwNew = this.pwConfirm = '';
          setTimeout(() => { this.pwOk = false; this.pwOpen = false; }, 3000);
        } else if (res.status === 422) {
          const data = await res.json();
          this.pwError = data.errors?.current_password?.[0]
            || data.errors?.password?.[0]
            || '校验失败';
        } else {
          this.pwError = '更新失败';
        }
      } catch (e) {
        this.pwError = '网络错误';
      }
      this.pwBusy = false;
    },
    boot() {
      this.loadSummary();
      this.loadNotifications();
      setInterval(() => { if (!document.hidden) this.loadNotifications(); }, 60000);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden && this.open) {
          this.loadNotifications();
          if (this.tab === 'mine') this.loadSummary();
        }
      });
    },
  };
}
</script>
@endauth
