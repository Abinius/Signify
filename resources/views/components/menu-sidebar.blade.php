{{-- 全局右侧边栏菜单外壳：背板（点击关闭）+ 从右滑入面板 + 头部。分组放默认 slot，底部常驻放 $footer。 --}}
@props(['showFooter' => false])
<div class="fixed inset-0 z-[200]" :class="menuOpen ? '' : 'pointer-events-none'"
     @keydown.escape.window="menuOpen = false">
  <div x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms
       @click="menuOpen = false" class="absolute inset-0 bg-ink/25"></div>

  <aside x-show="menuOpen" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="absolute top-0 right-0 h-full w-80 max-w-[85vw] bg-surface border-l border-hairline shadow-float flex flex-col">

    <div class="flex items-center justify-between px-5 py-4 border-b border-hairline">
      <img src="{{ asset('icons/logo.svg') }}" alt="{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}" class="h-6 w-auto">
      <button type="button" @click="menuOpen = false" aria-label="关闭菜单"
              class="w-9 h-9 flex items-center justify-center text-muted hover:text-ink transition-colors">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-5 h-5">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-8">{{ $slot }}</nav>

    @if($showFooter)
      <div class="px-4 py-4 border-t border-hairline">{{ $footer }}</div>
    @endif
  </aside>
</div>
