@extends('layouts.app')

@push('styles')
{{-- 年轻化展示字体 Manrope：现代无衬线，配合现代黑体中文；失败自动回退系统字体 --}}
<link rel="preconnect" href="https://fonts.googleapis.cn" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.cn/css2?family=Manrope:wght@200;400;500;700;800&display=swap">
<style>
  /* ========== 落地页样式（复用于 guest 首页；nav/footer 由系统 layout 提供） ==========
     极简刷新：白色中性底 + 品牌珊瑚主色 + 统一线性 SVG 图标 + 发丝线分隔。
     收敛渐变与暖调底：按钮改纯实色、序号改线性图标；Hero 与受众带用实景底 + 深色渐晕保证白字可读。 */
  html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}
  .landing{
    --l-ink:#1a1a18;          /* 正文/标题墨色 */
    --l-ink-soft:#5c5a54;     /* 次级正文（白底 ≈6.9:1） */
    --l-coral:#c8442e;        /* 主珊瑚 —— 按钮/图标/强调（白字 4.79:1） */
    --l-coral-bright:#e8563a; /* 亮珊瑚 —— 深色底上 hover 强调 */
    --l-coral-deep:#b03a26;   /* 深珊瑚 —— 白底小号强调文字（5.94:1） */
    --l-coral-hover:#a8331f;  /* 按钮 hover */
    --l-hairline:rgba(26,26,24,.1);
    font-family:"Noto Sans SC","PingFang SC","Hiragino Sans GB","Microsoft YaHei",system-ui,sans-serif;
    font-weight:400;color:var(--l-ink);background:transparent;line-height:1.8;letter-spacing:.02em;
  }
  .landing h1,.landing h2,.landing h3{
    font-family:"Manrope","Noto Sans SC","PingFang SC","Hiragino Sans GB","Microsoft YaHei",system-ui,sans-serif;
    font-weight:800;letter-spacing:-.02em;line-height:1.18;color:var(--l-ink);text-wrap:balance;
  }
  .landing section{padding:104px 0}
  .landing .btn:focus-visible{outline:2px solid var(--l-coral);outline-offset:3px;border-radius:9999px}

  .landing .container{max-width:1240px;margin:0 auto;padding:0 32px}
  .landing .eyebrow{
    font-family:"Manrope","Noto Sans SC",system-ui,sans-serif;
    font-size:13px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--l-coral-deep);
  }

  .landing .hero{
    padding:200px 0 140px;text-align:center;position:relative;overflow:hidden;
    background-image:url('{{ \App\Models\Setting::get('banner_image', asset('landingbanner.webp')) }}');
    background-size:cover;background-position:center;background-repeat:no-repeat;
  }
  /* 深色渐晕：保证白色标题/正文在任意横幅画面（含动画帧）上始终可读 */
  .landing .hero::before{
    content:"";position:absolute;inset:0;z-index:0;
    background:linear-gradient(160deg,rgba(168,51,31,.5) 0%,rgba(24,16,12,.42) 45%,rgba(24,16,12,.7) 100%);
  }
  .landing .hero>.container{position:relative;z-index:1}
  .landing .hero__eyebrow{margin-bottom:48px}
  .landing .hero .eyebrow{color:rgba(255,255,255,.94)}
  .landing .hero__title{color:#fff;font-weight:200;font-size:clamp(40px,6.6vw,88px);line-height:1.14;letter-spacing:-.02em;max-width:1100px;margin:0 auto 36px;text-shadow:0 2px 18px rgba(0,0,0,.35)}
  .landing .hero__sub{color:rgba(255,255,255,.96);font-size:clamp(18px,1.6vw,22px);line-height:1.75;max-width:640px;margin:0 auto 64px;text-shadow:0 1px 10px rgba(0,0,0,.3)}
  .landing .hero__cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:48px}
  .landing .hero__cta-note{font-size:13px;font-weight:400;letter-spacing:.08em;color:rgba(255,255,255,.92);margin:-24px 0 32px;text-shadow:0 1px 8px rgba(0,0,0,.35)}
  .landing .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:10px;
    font-size:14px;font-weight:500;letter-spacing:.08em;padding:15px 38px;border-radius:9999px;
    cursor:pointer;text-decoration:none;border:1px solid transparent;
    transition:background-color .2s ease,color .2s ease,border-color .2s ease,transform .2s ease,box-shadow .2s ease;
  }
  .landing .btn--solid{background:var(--l-coral);color:#fff;border-color:transparent}
  .landing .btn--solid:hover{background:var(--l-coral-hover);border-color:transparent;transform:translateY(-1px);box-shadow:0 6px 18px rgba(200,68,46,.3)}
  .landing .btn--ghost{background:transparent;color:var(--l-coral);border-color:var(--l-coral)}
  .landing .btn--ghost:hover{background:var(--l-coral);color:#fff;border-color:var(--l-coral)}
  .landing .btn svg{transition:transform .25s ease}
  .landing .btn:hover svg{transform:translateX(2px)}
  .landing .hero__trust{font-size:12px;font-weight:500;letter-spacing:.28em;color:rgba(255,255,255,.9);text-shadow:0 1px 6px rgba(0,0,0,.35)}
  .landing .hero__trust span{margin:0 14px}
  .landing .hero__trust .dot{color:rgba(255,255,255,.6)}
  .landing .hero__social{margin-top:36px}
  .landing .hero__social-line{font-size:13px;font-weight:400;letter-spacing:.06em;color:rgba(255,255,255,.92);text-shadow:0 1px 6px rgba(0,0,0,.35)}
  .landing .hero__social-line strong{font-weight:700;color:#fff}
  .landing .hero__social-line a{color:#fff;border-bottom:1px solid rgba(255,255,255,.7);transition:color .2s,border-color .2s}
  .landing .hero__social-line a:hover{color:var(--l-coral-bright);border-color:#fff}
  .landing .hero::after{content:"";position:absolute;left:50%;bottom:0;width:1px;height:60px;background:linear-gradient(to bottom,transparent,rgba(255,255,255,.6));transform:translateX(-50%)}

  .landing .section__eyebrow{margin-bottom:24px}
  .landing .section__title{font-weight:200;font-size:clamp(28px,4vw,48px);line-height:1.2;margin-bottom:48px;max-width:760px}
  .landing .section__body{font-size:clamp(16px,1.4vw,18px);color:var(--l-ink-soft);line-height:1.95;max-width:720px}
  .landing .section__body p+p{margin-top:1.4em}
  .landing .section__body strong{font-weight:600;color:var(--l-ink)}

  /* 价值主张：白底两栏编辑布局，上下发丝线，去暖调底与圆角卡片 */
  .landing .value{border-top:1px solid var(--l-hairline);border-bottom:1px solid var(--l-hairline)}
  .landing .value__grid{display:grid;grid-template-columns:minmax(0,1.06fr) minmax(0,.94fr);gap:64px;align-items:center}
  .landing .value__lede{font-size:clamp(20px,2.2vw,28px);line-height:1.55;color:var(--l-ink);font-weight:500}
  .landing .value__body{margin:0}
  @media (max-width:1024px){
    .landing .value__grid{grid-template-columns:1fr;gap:36px}
    .landing .value__lede{font-size:clamp(20px,2.4vw,26px)}
  }

  /* 核心能力：发丝线卡片网格，线性 SVG 图标替代渐变序号 */
  .landing .features__grid{
    display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:64px;
  }
  .landing .feature{
    background:rgba(255,255,255,.6);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.3);border-radius:20px;
    padding:40px 32px;transition:background .2s ease,box-shadow .2s ease,transform .2s ease;
  }
  .landing .feature:hover{background:rgba(255,255,255,.8);box-shadow:0 10px 30px rgba(26,26,24,.08);transform:translateY(-2px)}
  .landing .feature__icon{width:36px;height:36px;color:var(--l-coral);margin-bottom:24px;display:block}
  .landing .feature__title{font-size:22px;margin-bottom:16px;line-height:1.25}
  .landing .feature__desc{font-size:16px;font-weight:400;color:var(--l-ink-soft);line-height:1.8}

  /* 受众带：抽象漩涡实景底 + 深色渐晕（保证白字可读），文案只留大标题极细 */
  .landing .audience{position:relative;color:#fff;text-align:center;min-height:60vh;display:flex;align-items:center;justify-content:center;padding:0;overflow:hidden;
    background-image:url('{{ asset('audience-bg.jpg') }}');background-size:cover;background-position:center;background-repeat:no-repeat}
  .landing .audience::before{content:"";position:absolute;inset:0;z-index:0;
    background:linear-gradient(180deg,rgba(24,16,12,.42) 0%,rgba(24,16,12,.5) 100%)}
  .landing .audience .container{position:relative;z-index:1;width:100%}
  .landing .audience__title{
    font-family:"Manrope","Noto Sans SC","PingFang SC","Hiragino Sans GB","Microsoft YaHei",system-ui,sans-serif;
    font-weight:200;font-size:clamp(32px,5vw,60px);line-height:1.15;letter-spacing:.01em;
    margin:0 auto;max-width:900px;color:#fff;text-shadow:0 2px 18px rgba(0,0,0,.4)}

  .landing .final{padding:136px 0;text-align:center}
  .landing .final__title{font-weight:200;font-size:clamp(36px,6vw,72px);line-height:1.15;margin-bottom:28px}
  .landing .final__sub{font-size:clamp(15px,1.4vw,18px);color:var(--l-ink-soft);letter-spacing:.08em;margin-bottom:56px}
  .landing .final__cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}

  .landing .reveal{opacity:0;transform:translateY(28px);transition:opacity .9s cubic-bezier(.22,1,.36,1),transform .9s cubic-bezier(.22,1,.36,1);will-change:transform,opacity}
  .landing .reveal.is-visible{opacity:1;transform:translateY(0)}
  .landing .reveal.delay-1{transition-delay:.08s}
  .landing .reveal.delay-2{transition-delay:.16s}
  .landing .reveal.delay-3{transition-delay:.24s}

  @media (min-width:1024px){
    /* 桌面端视差：背景固定，内容滚动覆盖。移动端（尤其 iOS）background-attachment:fixed 有错位 bug，故保持 scroll */
    .landing .hero,.landing .audience{background-attachment:fixed}
  }
  @media (max-width:1024px){
    .landing .features__grid{grid-template-columns:repeat(2,1fr)}
  }
  @media (max-width:640px){
    .landing .container{padding:0 24px}
    .landing .hero{padding:140px 0 100px}
    .landing section{padding:80px 0}
    .landing .features__grid{grid-template-columns:repeat(2,1fr)}
    .landing .feature{padding:32px 24px}
    .landing .final{padding:80px 0}
    .landing .hero__trust{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:6px 12px;font-size:12px;letter-spacing:.14em}
    .landing .hero__trust span{margin:0}
    .landing .hero__trust .dot{display:none}
    .landing .hero__cta-note{margin:-20px 0 28px}
    .landing .hero__social{margin-top:28px}
    .landing .hero__social-line{font-size:12px;letter-spacing:.02em}
  }
  @media (prefers-reduced-motion:reduce){
    .landing .reveal{opacity:1;transform:none;transition:none}
    .landing html{scroll-behavior:auto}
  }
</style>
@endpush

@section('content')
@php($siteName = \App\Models\Setting::get('site_name', 'SIGNIFY'))
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }}",
  "alternateName": "{{ \App\Models\Setting::get('share_title', 'SIGNIFY — 每一份引领行业的商业远见，都值得被更广泛地看见') }}",
  "url": "{{ url('/') }}",
  "description": "{{ \App\Models\Setting::get('site_description', '') }}",
  "inLanguage": "zh-CN"
}
</script>

<div class="landing">
  {{-- Hero --}}
  <header class="hero">
    <div class="container">
      <div class="hero__eyebrow eyebrow reveal">企业家形象资产数字化系统</div>
      <h1 class="hero__title reveal delay-1">每一份引领行业的商业远见都值得被看见</h1>
      <p class="hero__sub reveal delay-2">{{ $siteName }} · 把你的个人价值，放大成看得见的核心竞争力。</p>
      <div class="hero__cta reveal delay-3">
        <a href="{{ route('entrepreneurs.index') }}" class="btn btn--solid">更多浏览<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      </div>
      <p class="hero__cta-note reveal delay-3">30 秒提交申请 · 管理员 48 小时内审核</p>
      <div class="hero__trust reveal delay-3">
        <span>收录制</span><span class="dot">·</span>
        <span>推荐准入</span><span class="dot">·</span>
        <span>不卖流量</span><span class="dot">·</span>
        <span>不卖数据</span>
      </div>
      <div class="hero__social reveal delay-3">
        <p class="hero__social-line">已收录 <strong>9 位</strong> 行业标杆 · <a href="{{ route('entrepreneurs.index') }}">黄建南</a>、<a href="{{ route('entrepreneurs.index') }}">何家英</a>、<a href="{{ route('entrepreneurs.index') }}">冯长江</a>…</p>
      </div>
    </div>
  </header>

  {{-- 价值主张 --}}
  <section class="value" id="why">
    <div class="container">
      <div class="value__grid">
        <div class="value__intro">
          <div class="section__eyebrow eyebrow reveal">为什么是 {{ $siteName }}</div>
          <p class="value__lede reveal delay-1">
            企业家的个人价值，长期被两头夹击——一头是公司品牌，把你的远见归功于组织；一头是流量算法，把你的形象裹挟进数据游戏。
          </p>
        </div>
        <div class="value__body section__body reveal delay-2">
          <p>个人形象、行业远见、价值判断——这些最难量化的东西，一直没有一个体面的承载方式。</p>
          <p><strong>{{ $siteName }} 把它数字化。</strong></p>
          <p>不卖流量，不卖数据，不参与分发博弈。只做一件事——把企业家的形象资产，变成可被推荐的、看得见的核心竞争力。</p>
        </div>
      </div>
    </div>
  </section>

  {{-- 核心能力 --}}
  <section id="features">
    <div class="container">
      <div class="section__eyebrow eyebrow reveal">形象资产 · 一站式数字化</div>
      <h2 class="section__title reveal delay-1">把企业家的全部入口，收进一张名片。</h2>
      <div class="features__grid">
        <div class="feature reveal">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="1.6"/><path d="M6 16c.5-1.4 1.6-2 2.5-2s2 .6 2.5 2"/><path d="M14 9h4M14 12.5h4M14 16h2.5"/></svg>
          <h3 class="feature__title">个人名片</h3>
          <p class="feature__desc">登录即达。形象照、社交链接、二维码、身份标签——一张图承载一个企业家的全部入口。</p>
        </div>
        <div class="feature reveal delay-1">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <h3 class="feature__title">企业家库</h3>
          <p class="feature__desc">收录制而非注册制。仅展示已获推荐的认证档案，保证库内每一张名片，都值得被看见。</p>
        </div>
        <div class="feature reveal delay-2">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 3 14.9 8.9 21.4 9.9 16.7 14.5 17.8 21 12 18 6.2 21 7.3 14.5 2.6 9.9 9.1 8.9 12 3"/></svg>
          <h3 class="feature__title">推荐准入</h3>
          <p class="feature__desc">企业家自助申请，必填推荐理由，管理员审核。被推荐，意味着被同行认可——而不是被算法选中。</p>
        </div>
        <div class="feature reveal">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="2.4"/><circle cx="6" cy="12" r="2.4"/><circle cx="18" cy="19" r="2.4"/><path d="M8.2 10.9l7.6-4.4M8.2 13.1l7.6 4.4"/></svg>
          <h3 class="feature__title">品牌化分享</h3>
          <p class="feature__desc">微信、朋友圈、社交平台，分享即品牌。OG 卡片自动生成，链接自带企业家形象预览。</p>
        </div>
        <div class="feature reveal delay-1">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.7"/><path d="M21 16l-5-5-9 9"/></svg>
          <h3 class="feature__title">形象资产</h3>
          <p class="feature__desc">4:5 形象照自动派生 1:1，社交链接按域名自动识别平台图标。一个网址，全套品牌识别。</p>
        </div>
        <div class="feature reveal delay-2">
          <svg class="feature__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5.5c0 4.4-3 7.6-7 9.5-4-1.9-7-5.1-7-9.5V6z"/><path d="M9.5 12l1.8 1.8 3.2-3.6"/></svg>
          <h3 class="feature__title">数据主权</h3>
          <p class="feature__desc">档案归属本人，每一项操作都有权限保护。你不是被收录的数据，你是数据的主人。</p>
        </div>
      </div>
    </div>
  </section>

  {{-- 谁应该加入 --}}
  <section class="audience">
    <div class="container">
      <h2 class="audience__title reveal">如果你相信，个人价值应该被系统化放大。</h2>
    </div>
  </section>

  {{-- 底部 CTA --}}
  <section class="final">
    <div class="container">
      <h2 class="final__title reveal">让远见，被看见</h2>
      <p class="final__sub reveal delay-1">{{ $siteName }} · 企业家形象资产数字化系统</p>
      <div class="final__cta reveal delay-2">
        <a href="{{ route('register') }}" class="btn btn--solid">申请推荐<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        <a href="{{ route('login') }}" class="btn btn--ghost">登录账户</a>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var els = document.querySelectorAll('.landing .reveal');
    if (prefersReduced) {
      els.forEach(function(el){ el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); io.unobserve(entry.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    els.forEach(function(el){ io.observe(el); });
  })();
</script>
@endpush
