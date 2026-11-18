<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', \App\Models\Setting::get('site_name', 'SIGNIFY'))</title>
  <meta name="description" content="{{ \App\Models\Setting::get('site_description', '不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。') }}">
  <meta name="theme-color" content="#FAFAF7">

  {{-- 浏览器图标 --}}
  @include('partials.favicons')

  <!-- 微信 / 社交媒体分享（各页可用 og-title / og-description / og-url / og-image 区块覆盖） -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}">
  <meta property="og:title" content="@yield('og-title', \App\Models\Setting::get('share_title', 'SIGNIFY — 每一份引领行业的商业远见，都值得被更广泛地看见'))">
  <meta property="og:description" content="@yield('og-description', \App\Models\Setting::get('share_description', '不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。'))">
  <meta property="og:url" content="@yield('og-url', url('/'))">
  <meta property="og:image" content="@yield('og-image', \App\Models\Setting::get('share_image', asset('android-chrome-512x512.png')))">
  <meta property="og:locale" content="zh_CN">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="@yield('og-title', \App\Models\Setting::get('share_title', 'SIGNIFY — 每一份引领行业的商业远见，都值得被更广泛地看见'))">
  <meta name="twitter:description" content="@yield('og-description', \App\Models\Setting::get('share_description', '不用复杂定义，只用数字化技术，把企业家的个人价值放大成看得见的核心竞争力。'))">
  <meta name="twitter:image" content="@yield('og-image', \App\Models\Setting::get('share_image', asset('android-chrome-512x512.png')))">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  @stack('styles')
  <link rel="preconnect" href="https://fonts.googleapis.cn">
  <link rel="preconnect" href="https://fonts.gstatic.cn" crossorigin>
  {{-- 拉丁字体（Playfair/Inter）：国内节点优先，失败自动切谷歌；中文用系统字体 --}}
  @include('partials.font-loader')
  <script defer src="{{ asset('js/alpine.min.js') }}"></script>
</head>
<body class="min-h-screen flex flex-col" x-data="{ menuOpen: false }"
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
    <span x-show="$store.ui.unread > 0" x-cloak
          :class="{ 'animate-pulse': $store.ui.pulse }"
          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full
                 bg-status-danger text-paper text-[10px] leading-[18px] text-center font-bold
                 flex items-center justify-center">
      <span x-text="$store.ui.unread > 99 ? '99+' : $store.ui.unread"></span>
    </span>
  </button>

  <x-settings.modal />

  {{-- 全屏菜单弹窗 --}}
  <div x-show="menuOpen" x-cloak
       @keydown.escape.window="menuOpen = false"
       class="fixed inset-0 z-[200] bg-paper flex flex-col">
    <div class="flex items-center justify-between px-6 py-5">
      <img src="{{ asset('icons/logo.svg') }}" alt="{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}" class="h-6 w-auto">
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
          <x-menu-link href="{{ route('home') }}" icon="home" label="首页" />
          <x-menu-link href="{{ route('entrepreneurs.index') }}" icon="book" label="智库" />

          @auth
            <x-menu-link href="{{ route('profile.show') }}" icon="user" label="个人中心" />
            <x-menu-link icon="bell" label="通知" unread="1" tab="notif" />
            <x-menu-link icon="settings" label="设置" tab="mine" />
            <x-menu-link href="{{ route('logout') }}" icon="log-out" label="退出账户"
                         danger="1" submit="1" confirm="确认退出当前账户？" />
          @else
            <x-menu-link href="{{ route('login') }}" icon="log-in" label="登录" />
            <x-menu-link href="{{ route('register') }}" icon="user-plus" label="加入" />
          @endauth
        </div>
      </div>
    </nav>
  </div>

  <main class="flex-1">
    @include('components.flash')
    @yield('content')
  </main>

  <footer class="border-t border-hairline">
    <div class="max-w-7xl mx-auto px-6 py-12 text-center space-y-2">
      <p class="label-caption text-muted">{{ \App\Models\Setting::get('footer_copyright', '© SIGNIFY') }}</p>
      @if(\App\Models\Setting::get('icp_number'))
        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener"
           class="label-caption text-muted hover:text-ink transition-colors inline-block">
          {{ \App\Models\Setting::get('icp_number') }}
        </a>
      @endif
    </div>
  </footer>

  @stack('scripts')

</body>
</html>
