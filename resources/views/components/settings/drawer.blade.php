@auth
<div x-data='settingsDrawer({
      summaryUrl: @json(route('profile.summary')),
      unreadUrl:  @json(route('notifications.unread')),
      readUrl:    @json(route('notifications.read', '__ID__')),
      readAllUrl: @json(route('notifications.read-all')),
      csrf:       @json(csrf_token()),
    })' x-init="boot()"
     class="fixed top-5 right-20 z-[200]">

  {{-- 齿轮按钮 --}}
  <button type="button" @click="toggle()" aria-label="设置"
          :aria-expanded="open"
          class="relative w-11 h-11 flex items-center justify-center
                 bg-paper/85 backdrop-blur-md border border-hairline text-ink
                 hover:bg-ink hover:text-paper transition-colors duration-200">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
         stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
      <circle cx="12" cy="12" r="3"/>
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
    </svg>
    <span x-show="unread > 0" x-cloak
          :class="{ 'animate-pulse': pulse }"
          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full
                 bg-status-danger text-paper text-[10px] leading-[18px] text-center font-bold
                 flex items-center justify-center">
      <span x-text="unread > 99 ? '99+' : unread"></span>
    </span>
  </button>

  {{-- 面板 --}}
  <div x-show="open" x-cloak
       x-transition:enter="transition ease-out duration-150"
       x-transition:enter-start="opacity-0 translate-y-1"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-100"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @click.outside="open = false"
       @keydown.escape.window="open = false"
       class="absolute right-0 mt-2 w-[20rem] max-w-[calc(100vw-6rem)]
              bg-paper border border-hairline shadow-xl">

    {{-- Tab 头 --}}
    <div class="px-4 py-3 border-b border-hairline flex items-center justify-between gap-3">
      <div class="flex items-center gap-4">
        <button type="button" x-show="s.has_entrepreneur" x-cloak
                @click="tab='mine'"
                :class="tab==='mine' ? 'text-ink font-bold' : 'text-muted hover:text-ink'"
                class="label-caption transition-colors">我的</button>
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
      <button type="button" x-show="tab==='notif' && unread > 0" x-cloak
              @click="markAllRead()"
              class="label-caption text-muted hover:text-accent transition-colors">全部已读</button>
    </div>

    {{-- 我的 --}}
    <div x-show="tab==='mine'" class="divide-y divide-hairline max-h-96 overflow-y-auto">
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
        <div class="px-4 py-4 space-y-3">
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
              <input type="text" x-model="draft" @input.debounce.400ms="checkSlug()"
                     placeholder="your-slug"
                     pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                     class="flex-1 px-2 py-2 text-sm bg-paper text-ink outline-none min-w-0">
            </div>
            <p x-show="slugMsg" x-cloak class="text-[11px] mt-1"
               :class="slugOk ? 'text-status-success' : 'text-status-danger'"
               x-text="slugMsg"></p>
            <div class="flex items-center gap-3 justify-end mt-2">
              <span x-show="saved" x-cloak class="text-[11px] text-status-success">已保存 ✓</span>
              <button type="button" @click="saveSlug()" :disabled="saving"
                      class="btn-ink !py-1.5 !px-4 text-sm disabled:opacity-50">保存</button>
            </div>
          </div>

          {{-- 名片 --}}
          <div class="border border-hairline p-3 text-center">
            <a :href="s.card_url"
               class="inline-flex items-center gap-1.5 label-caption text-accent hover:opacity-70 transition-opacity">
              查看我的名片 →
            </a>
          </div>
        </div>
      </template>
    </div>

    {{-- 通知 --}}
    <div x-show="tab==='notif'" class="max-h-80 overflow-y-auto divide-y divide-hairline">
      <template x-for="n in items" :key="n.id">
        <a :href="n.url || '#'" @click.prevent="onItemClick(n)"
           class="block px-4 py-3 hover:bg-surface transition-colors relative">
          <span class="absolute left-0 top-0 bottom-0 w-[3px]"
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
        <p class="label-caption text-muted">暂无未读通知</p>
      </div>
    </div>
  </div>
</div>

<script>
function settingsDrawer(config) {
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
    tab: 'notif',
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
    toggle() {
      this.open = !this.open;
      if (this.open) {
        this.loadSummary();
        this.loadUnread();
      }
    },
    async loadSummary() {
      this.loading = true;
      try {
        const res = await fetch(this.summaryUrl, { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
          this.s = await res.json();
          this.draft = this.s.slug || '';
          if (this.s.has_entrepreneur) this.tab = this.tab === 'notif' && this.open ? this.tab : 'mine';
          if (this.s.has_entrepreneur && !this.open) this.tab = 'mine';
        }
      } catch (e) {}
      this.loading = false;
    },
    async loadUnread() {
      try {
        const res = await fetch(this.unreadUrl, { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
          this.items = await res.json();
          this.unread = this.items.length;
          if (this.unread > this.lastUnread && this.lastUnread !== 0) this.pulse = true;
          this.lastUnread = this.unread;
          setTimeout(() => this.pulse = false, 1500);
        }
      } catch (e) {}
    },
    async onItemClick(n) {
      if (this.busy[n.id]) return;
      this.busy[n.id] = true;
      try {
        await fetch(this.readUrl.replace('__ID__', n.id), {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
        });
        this.loadUnread();
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
      this.loadUnread();
    },
    async checkSlug() {
      this.slugMsg = '';
      this.slugOk = false;
      const v = this.draft.trim();
      if (!v) { this.slugMsg = '留空则清除专属链接'; return; }
      if (v.length < 3) { this.slugMsg = '至少 3 个字符'; return; }
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(v)) { this.slugMsg = '仅限小写字母、数字、短横线'; return; }
      if (v === this.s.slug) { this.slugMsg = '当前即此值'; this.slugOk = true; return; }
      try {
        const res = await fetch(this.s.check_slug_url + '?slug=' + encodeURIComponent(v), {
          headers: { 'Accept': 'application/json' },
        });
        if (res.ok) {
          const data = await res.json();
          this.slugOk = !!data.available;
          this.slugMsg = data.available ? '可用 ✓' : '已被占用';
        }
      } catch (e) {}
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
    boot() {
      this.loadSummary();
      this.loadUnread();
      setInterval(() => { if (!document.hidden) this.loadUnread(); }, 60000);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden && this.open) {
          this.loadUnread();
          if (this.tab === 'mine') this.loadSummary();
        }
      });
    },
  };
}
</script>
@endauth
