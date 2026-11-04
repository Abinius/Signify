@extends('layouts.app')

@push('styles')
<style>
  /* ========== 落地页样式（复用于 guest 首页；nav/footer 由系统 layout 提供） ========== */
  html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}
  .landing{
    font-family:"Noto Sans SC","PingFang SC","Hiragino Sans GB","Microsoft YaHei",system-ui,sans-serif;
    font-weight:400;color:#0a0a0a;background:#ffffff;line-height:1.8;letter-spacing:.02em;
  }
  .landing h1,.landing h2,.landing h3{font-weight:400;letter-spacing:.01em;line-height:1.2}
  .landing section{padding:120px 0}
  .landing .btn:focus-visible{outline:2px solid #0a0a0a;outline-offset:3px;border-radius:1px}

  .landing .container{max-width:1240px;margin:0 auto;padding:0 32px}
  .landing .eyebrow{font-size:13px;font-weight:300;letter-spacing:.32em;text-transform:uppercase;color:#999}

  .landing .hero{
    padding:200px 0 140px;text-align:center;position:relative;overflow:hidden;
    background-image:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.82)),url('/landingbanner.webp');
    background-size:cover;background-position:center;background-repeat:no-repeat;
  }
  .landing .hero__eyebrow{margin-bottom:48px}
  .landing .hero__title{font-size:clamp(40px,6.6vw,88px);line-height:1.18;letter-spacing:.005em;max-width:1100px;margin:0 auto 36px}
  .landing .hero__sub{font-size:clamp(17px,1.6vw,22px);color:#555;line-height:1.75;max-width:640px;margin:0 auto 64px}
  .landing .hero__cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:48px}
  .landing .hero__cta-note{font-size:12px;font-weight:300;letter-spacing:.08em;color:#999;margin:-24px 0 32px}
  .landing .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:10px;
    font-size:14px;font-weight:300;letter-spacing:.1em;padding:15px 38px;border-radius:1px;
    cursor:pointer;text-decoration:none;border:1px solid transparent;
    transition:background-color .25s ease,color .25s ease,border-color .25s ease,transform .25s ease;
  }
  .landing .btn--solid{background:#0a0a0a;color:#fff;border-color:#0a0a0a}
  .landing .btn--solid:hover{background:#000;border-color:#000;transform:translateY(-1px)}
  .landing .btn--ghost{background:transparent;color:#0a0a0a;border-color:#0a0a0a}
  .landing .btn--ghost:hover{background:#0a0a0a;color:#fff;border-color:#0a0a0a}
  .landing .btn svg{transition:transform .25s ease}
  .landing .btn:hover svg{transform:translateX(2px)}
  .landing .hero__trust{font-size:12px;font-weight:300;letter-spacing:.28em;color:#999}
  .landing .hero__trust span{margin:0 14px}
  .landing .hero__trust .dot{color:#ccc}
  .landing .hero__social{margin-top:36px}
  .landing .hero__social-line{font-size:13px;font-weight:300;letter-spacing:.06em;color:#777}
  .landing .hero__social-line strong{font-weight:400;color:#0a0a0a}
  .landing .hero__social-line a{color:#555;border-bottom:1px solid #ccc;transition:color .2s,border-color .2s}
  .landing .hero__social-line a:hover{color:#000;border-color:#000}
  .landing .hero::after{content:"";position:absolute;left:50%;bottom:0;width:1px;height:60px;background:linear-gradient(to bottom,transparent,#ccc);transform:translateX(-50%)}

  .landing .section__eyebrow{margin-bottom:24px}
  .landing .section__title{font-size:clamp(28px,4vw,48px);line-height:1.25;margin-bottom:48px;max-width:760px}
  .landing .section__body{font-size:clamp(16px,1.4vw,18px);color:#444;line-height:1.95;max-width:720px}
  .landing .section__body p+p{margin-top:1.4em}
  .landing .section__body strong{font-weight:300;color:#0a0a0a}

  .landing .value{background:#fafafa;border-top:1px solid #eee;border-bottom:1px solid #eee}
  .landing .value__lede{font-size:clamp(22px,2.6vw,30px);line-height:1.6;color:#0a0a0a;margin-bottom:40px;max-width:880px}
  .landing .value__body{margin-top:56px}

  .landing .features__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#eee;margin-top:64px;border:1px solid #eee}
  .landing .feature{background:#fff;padding:48px 40px;transition:background .3s ease}
  .landing .feature:hover{background:#fafafa}
  .landing .feature__no{font-size:12px;font-weight:300;letter-spacing:.24em;color:#bbb;margin-bottom:24px}
  .landing .feature__title{font-size:22px;margin-bottom:18px;line-height:1.3}
  .landing .feature__desc{font-size:15px;font-weight:300;color:#555;line-height:1.85}

  .landing .audience{background:#0a0a0a;color:#fff;text-align:center;padding:140px 0}
  .landing .audience .section__eyebrow{color:#666}
  .landing .audience__title{font-size:clamp(32px,5vw,60px);line-height:1.2;margin:0 auto 64px;max-width:900px}
  .landing .audience__list{max-width:760px;margin:0 auto 56px;text-align:left;display:grid;gap:20px}
  .landing .audience__list li{font-size:clamp(16px,1.5vw,19px);color:#bbb;line-height:1.7;padding-left:24px;position:relative}
  .landing .audience__list li::before{content:"—";position:absolute;left:0;color:#555}
  .landing .audience__cta{font-size:clamp(17px,1.8vw,22px);color:#fff;line-height:1.6;max-width:680px;margin:0 auto}
  .landing .audience__cta em{font-style:normal;color:#888;display:block;margin-top:16px;font-size:15px}

  .landing .final{padding:160px 0;text-align:center}
  .landing .final__title{font-size:clamp(36px,6vw,72px);line-height:1.2;margin-bottom:28px}
  .landing .final__sub{font-size:clamp(15px,1.4vw,18px);color:#666;letter-spacing:.08em;margin-bottom:56px}
  .landing .final__cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}

  .landing .reveal{opacity:0;transform:translateY(28px);transition:opacity .9s cubic-bezier(.22,1,.36,1),transform .9s cubic-bezier(.22,1,.36,1);will-change:transform,opacity}
  .landing .reveal.is-visible{opacity:1;transform:translateY(0)}
  .landing .reveal.delay-1{transition-delay:.08s}
  .landing .reveal.delay-2{transition-delay:.16s}
  .landing .reveal.delay-3{transition-delay:.24s}

  @media (max-width:960px){
    .landing .features__grid{grid-template-columns:repeat(2,1fr)}
  }
  @media (max-width:640px){
    .landing .container{padding:0 24px}
    .landing .hero{padding:140px 0 100px}
    .landing section{padding:88px 0}
    .landing .features__grid{grid-template-columns:1fr}
    .landing .feature{padding:36px 28px}
    .landing .audience,.landing .final{padding:104px 0}
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
      <div class="section__eyebrow eyebrow reveal">为什么是 {{ $siteName }}</div>
      <p class="value__lede reveal delay-1">
        企业家的个人价值，长期被两头夹击——<br>
        一头是公司品牌，把你的远见归功于组织；<br>
        一头是流量算法，把你的形象裹挟进数据游戏。
      </p>
      <div class="value__body section__body reveal delay-2">
        <p>个人形象、行业远见、价值判断——这些最难量化的东西，一直没有一个体面的承载方式。</p>
        <p><strong>{{ $siteName }} 把它数字化。</strong></p>
        <p>不卖流量，不卖数据，不参与分发博弈。只做一件事——把企业家的形象资产，变成可被推荐的、看得见的核心竞争力。</p>
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
          <div class="feature__no">01</div>
          <h3 class="feature__title">个人名片</h3>
          <p class="feature__desc">登录即达。形象照、社交链接、二维码、身份标签——一张图承载一个企业家的全部入口。</p>
        </div>
        <div class="feature reveal delay-1">
          <div class="feature__no">02</div>
          <h3 class="feature__title">企业家库</h3>
          <p class="feature__desc">收录制而非注册制。仅展示已获推荐的认证档案，保证库内每一张名片，都值得被看见。</p>
        </div>
        <div class="feature reveal delay-2">
          <div class="feature__no">03</div>
          <h3 class="feature__title">推荐准入</h3>
          <p class="feature__desc">企业家自助申请，必填推荐理由，管理员审核。被推荐，意味着被同行认可——而不是被算法选中。</p>
        </div>
        <div class="feature reveal">
          <div class="feature__no">04</div>
          <h3 class="feature__title">品牌化分享</h3>
          <p class="feature__desc">微信、朋友圈、社交平台，分享即品牌。OG 卡片自动生成，链接自带企业家形象预览。</p>
        </div>
        <div class="feature reveal delay-1">
          <div class="feature__no">05</div>
          <h3 class="feature__title">形象资产</h3>
          <p class="feature__desc">4:5 形象照自动派生 1:1，社交链接按域名自动识别平台图标。一个网址，全套品牌识别。</p>
        </div>
        <div class="feature reveal delay-2">
          <div class="feature__no">06</div>
          <h3 class="feature__title">数据主权</h3>
          <p class="feature__desc">档案归属本人，每一项操作都有权限保护。你不是被收录的数据，你是数据的主人。</p>
        </div>
      </div>
    </div>
  </section>

  {{-- 谁应该加入 --}}
  <section class="audience">
    <div class="container">
      <div class="section__eyebrow eyebrow reveal">{{ $siteName }} 不服务所有人</div>
      <h2 class="audience__title reveal delay-1">如果你相信，个人价值应该被系统化放大。</h2>
      <ul class="audience__list reveal delay-2">
        <li>在行业里有判断、有远见，但个人形象长期被公司品牌覆盖的企业家</li>
        <li>想把自己从"某某公司创始人"还原成"有独立判断的某个人"的创业者</li>
        <li>需要一个体面方式被品牌方、媒体、投资人、猎头看见的行业领袖</li>
        <li>相信个人价值应该被系统化放大的独立商业人</li>
      </ul>
      <p class="audience__cta reveal delay-3">
        {{ $siteName }} 是你的。
        <em>如果你更需要的是流量和曝光——{{ $siteName }} 不是答案。</em>
      </p>
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
