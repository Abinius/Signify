<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', '管理后台') · {{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}</title>
  @include('partials.favicons')
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @stack('styles')
  <link rel="preconnect" href="https://fonts.googleapis.cn">
  <link rel="preconnect" href="https://fonts.gstatic.cn" crossorigin>
  @include('partials.font-loader')
  <script defer src="{{ asset('js/alpine.min.js') }}"></script>
</head>
<body class="min-h-screen flex flex-col bg-paper" x-data="{ menuOpen: false }"
      :class="menuOpen ? 'overflow-hidden' : ''">

  {{-- 右上角固定悬浮汉堡按钮 --}}
  <button type="button" @click="menuOpen = true" aria-label="打开菜单"
          class="fixed top-5 right-5 z-[200] w-11 h-11 flex items-center justify-center
                 bg-paper/85 backdrop-blur-md border border-hairline text-ink
                 hover:bg-ink hover:text-paper transition-colors duration-200">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-5 h-5">
      <line x1="3" y1="7" x2="21" y2="7"></line>
      <line x1="3" y1="12" x2="21" y2="12"></line>
      <line x1="3" y1="17" x2="21" y2="17"></line>
    </svg>
  </button>

  {{-- 全屏菜单弹窗 --}}
  <div x-show="menuOpen" x-cloak
       @keydown.escape.window="menuOpen = false"
       class="fixed inset-0 z-[200] bg-paper flex flex-col">
    <div class="flex items-center justify-between px-6 py-5 border-b border-hairline">
      <span class="label-caption text-muted">{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }} · ADMIN</span>
      <button type="button" @click="menuOpen = false" aria-label="关闭菜单"
              class="w-11 h-11 flex items-center justify-center text-ink hover:opacity-60 transition-opacity">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-6 h-6">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto">
      <div class="max-w-5xl mx-auto w-full px-6 py-10">
        <div class="space-y-2">
          <a href="{{ route('admin.dashboard') }}" @click="menuOpen = false"
             class="group block py-4 border-b border-hairline">
            <span class="font-display text-display-md font-bold text-ink group-hover:text-accent transition-colors">待审核</span>
            <span class="block label-caption text-muted mt-1">认证与推荐申请</span>
          </a>
          <a href="{{ route('admin.entrepreneurs') }}" @click="menuOpen = false"
             class="group block py-4 border-b border-hairline">
            <span class="font-display text-display-md font-bold text-ink group-hover:text-accent transition-colors">全部企业家</span>
            <span class="block label-caption text-muted mt-1">检索与管理档案</span>
          </a>
          <a href="{{ route('admin.settings') }}" @click="menuOpen = false"
             class="group block py-4 border-b border-hairline">
            <span class="font-display text-display-md font-bold text-ink group-hover:text-accent transition-colors">系统设置</span>
            <span class="block label-caption text-muted mt-1">站点与分享配置</span>
          </a>
          <a href="{{ route('home') }}" @click="menuOpen = false"
             class="group block py-4 border-b border-hairline">
            <span class="font-display text-display-md font-bold text-ink group-hover:text-accent transition-colors">返回首页</span>
          </a>
          <form method="POST" action="{{ route('logout') }}"
                onsubmit="return confirm('确认退出当前账户？')"
                class="group block py-4 border-b border-hairline">
            @csrf
            <button type="submit"
                    class="font-display text-display-md font-bold text-status-danger text-left w-full">退出账户</button>
          </form>
        </div>
      </div>
    </nav>
  </div>

  <main class="flex-1">
    @include('components.flash')
    @yield('content')
  </main>

  @stack('scripts')

</body>
</html>
