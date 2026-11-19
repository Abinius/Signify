<script>
document.addEventListener('alpine:init', () => {
  Alpine.store('ui', { unread: 0, pulse: false });
});
</script>

@auth
{{-- 未读角标轮询提供者：挂在布局上，驱动汉堡按钮的未读数。设置/通知已是独立页，弹窗本体已移除。 --}}
<div x-data='settingsModal({ mode: "badge", unreadUrl: @json(route('notifications.unread')) })'
     x-init="boot()" class="hidden"></div>

<script>
function settingsModal(config) {
  const STATUS_COLOR = {
    pending:   'text-status-warning',
    approved:  'text-status-success',
    rejected:  'text-status-danger',
  };
  return {
    mode: 'page',            // page = 独立设置页；badge = 布局未读角标轮询
    loading: false,
    s: { has_entrepreneur: false },
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
    statusColor(st) { return STATUS_COLOR[st] || 'text-muted'; },
    featuredColor(s) {
      if (s.is_featured) return 'text-status-success';
      if (s.featured_request_status === 'pending') return 'text-status-warning';
      if (s.featured_request_status === 'rejected') return 'text-status-danger';
      return 'text-muted';
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
        const res = await fetch(this.unreadUrl, { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
          const unreadItems = await res.json();
          this.unread = unreadItems.length;
          if (this.unread > this.lastUnread && this.lastUnread !== 0) this.pulse = true;
          this.lastUnread = this.unread;
          Alpine.store('ui').unread = this.unread;
          Alpine.store('ui').pulse = this.pulse;
          setTimeout(() => { this.pulse = false; Alpine.store('ui').pulse = false; }, 1500);
        }
      } catch (e) {}
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
      if (this.mode === 'page') {
        this.loadSummary();
        return;
      }
      this.loadNotifications();
      setInterval(() => { if (!document.hidden) this.loadNotifications(); }, 60000);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) this.loadNotifications();
      });
    },
  };
}
</script>
@endauth
