@extends('layouts.app')

@section('title', $entrepreneur->name.' — '.\App\Models\Setting::get('site_name', 'SIGNIFY'))

{{-- 分享卡片：图片用该企业家上传的头像；无头像时不覆盖 og-image，回退到系统设置的全局分享图 --}}
{{-- 转义说明：@section('name', $val) 内部已自动 e()，无需再显式转义（重复 e() 会造成 &amp;amp; 双重实体） --}}
@section('og-title', trim($entrepreneur->name.($entrepreneur->title ? ' '.$entrepreneur->title : '')))
@section('og-description', \Illuminate\Support\Str::limit(trim($entrepreneur->bio ?: $entrepreneur->industry ?: \App\Models\Setting::get('site_description', '每一份引领行业的商业远见，都值得被更广泛地看见')), 80))
@section('og-url', route('entrepreneurs.show', $entrepreneur->share_slug ?? $entrepreneur->id))
@if($entrepreneur->portrait || $entrepreneur->avatar)
  @section('og-image', url('storage/'.($entrepreneur->portrait ?? $entrepreneur->avatar)))
@endif

@section('content')

<div class="max-w-7xl mx-auto px-6 py-16">
  <article class="mt-10 grid grid-cols-1 md:grid-cols-5 gap-12">
    <div class="md:col-span-2">
      @if($entrepreneur->portrait || $entrepreneur->avatar)
        <img src="{{ asset('storage/'.($entrepreneur->portrait ?? $entrepreneur->avatar)) }}" alt="{{ $entrepreneur->name }}"
             class="w-full aspect-[4/5] object-cover border border-hairline">
      @else
        <div class="w-full aspect-[4/5] bg-ink/5 border border-hairline flex items-center justify-center">
          <span class="font-display text-8xl text-ink/20">{{ mb_substr($entrepreneur->name, 0, 1) }}</span>
        </div>
      @endif
    </div>

    <div class="md:col-span-3">
      <div class="mt-4 flex items-center justify-center gap-3">
        <h1 class="font-display text-display-lg font-black text-ink">{{ $entrepreneur->name }}</h1>
        @if($entrepreneur->is_featured)
          <img src="{{ asset('icons/recommend.svg') }}" alt="推荐"
               class="w-6 h-6 object-contain flex-shrink-0">
        @endif
      </div>
      @if($entrepreneur->title)
        <p class="mt-2 text-lg text-ink-soft text-center">{{ $entrepreneur->title }}</p>
      @endif
      <p class="mt-3 label-caption text-accent text-center">{{ $entrepreneur->industry ?? '—' }} · {{ $entrepreneur->city ?? '—' }}</p>
      @if(($entrepreneur->view_count ?? 0) > 10)
        <p class="mt-2 label-caption text-muted text-center">{{ number_format($entrepreneur->view_count) }} 人浏览过</p>
      @endif

      @if($entrepreneur->bio)
        <p class="mt-8 text-lg text-ink-soft leading-relaxed whitespace-pre-line">{{ $entrepreneur->bio }}</p>
      @endif

      @if($entrepreneur->wechat_qrcode || $entrepreneur->contact_phone || $entrepreneur->contact_email || !empty($entrepreneur->social_links))
        <div class="mt-10 pt-8 border-t border-hairline flex items-center justify-center gap-8" x-data="{ showQr: false }">
          @if($entrepreneur->wechat_qrcode)
            <button type="button" @click="showQr = true"
                    class="text-ink hover:opacity-60 transition-opacity" aria-label="微信" title="微信二维码">
              <svg viewBox="0 0 1024 1024" fill="currentColor" class="w-6 h-6" aria-hidden="true">
                <path d="M693.12 347.264c11.776 0 23.36 0.896 35.008 2.176-31.36-146.048-187.456-254.528-365.696-254.528C163.2 94.912 0 230.656 0 403.136c0 99.52 54.272 181.248 145.024 244.736L108.8 756.864l126.72-63.488c45.312 8.896 81.664 18.112 126.912 18.112 11.392 0 22.656-0.512 33.792-1.344-7.04-24.256-11.2-49.6-11.2-76.032C385.088 475.776 521.024 347.264 693.12 347.264zM498.304 249.024c27.392 0 45.376 17.984 45.376 45.248 0 27.136-17.984 45.312-45.376 45.312-27.072 0-54.336-18.176-54.336-45.312C443.968 266.944 471.168 249.024 498.304 249.024zM244.672 339.584c-27.2 0-54.592-18.176-54.592-45.312 0-27.264 27.392-45.248 54.592-45.248S289.92 266.944 289.92 294.272C289.92 321.408 271.872 339.584 244.672 339.584zM1024 629.76c0-144.896-145.024-262.976-307.904-262.976-172.48 0-308.224 118.144-308.224 262.976 0 145.28 135.808 262.976 308.224 262.976 36.096 0 72.512-9.024 108.736-18.112l99.392 54.528-27.264-90.624C969.728 783.872 1024 711.488 1024 629.76zM616.128 584.384c-17.984 0-36.224-17.92-36.224-36.224 0-18.048 18.24-36.224 36.224-36.224 27.52 0 45.376 18.176 45.376 36.224C661.504 566.464 643.648 584.384 616.128 584.384zM815.488 584.384c-17.856 0-36.032-17.92-36.032-36.224 0-18.048 18.112-36.224 36.032-36.224 27.264 0 45.376 18.176 45.376 36.224C860.864 566.464 842.752 584.384 815.488 584.384z"></path>
              </svg>
            </button>
          @endif
          @if($entrepreneur->contact_phone)
            <a href="tel:{{ $entrepreneur->contact_phone }}" class="text-ink hover:opacity-60 transition-opacity"
               aria-label="电话" title="{{ $entrepreneur->contact_phone }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
            </a>
          @endif
          @if($entrepreneur->contact_email)
            <a href="mailto:{{ $entrepreneur->contact_email }}" class="text-ink hover:opacity-60 transition-opacity"
               aria-label="邮箱" title="{{ $entrepreneur->contact_email }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </a>
          @endif
          @foreach(($entrepreneur->social_links ?? []) as $socialUrl)
            @if($socialUrl && \Illuminate\Support\Str::startsWith($socialUrl, ['http://', 'https://']))
              <a href="{{ e($socialUrl) }}" target="_blank" rel="noopener"
                 class="text-ink hover:opacity-60 transition-opacity" title="{{ parse_url($socialUrl, PHP_URL_HOST) }}">
                <img src="{{ asset('icons/'.\App\Models\Entrepreneur::socialIconForUrl($socialUrl)) }}"
                     class="w-6 h-6 object-contain" alt="{{ parse_url($socialUrl, PHP_URL_HOST) }}">
              </a>
            @endif
          @endforeach

          <!-- 微信二维码弹窗 -->
          <div x-show="showQr" x-cloak @keydown.escape.window="showQr = false"
               class="fixed inset-0 z-[300] bg-ink/60 backdrop-blur-sm grid place-items-center p-6"
               @click.self="showQr = false">
            <div class="bg-surface border border-hairline p-8 max-w-xs w-full shadow-float">
              <p class="label-caption text-muted text-center mb-4">微信二维码</p>
              <img src="{{ asset('storage/'.$entrepreneur->wechat_qrcode) }}" alt="{{ $entrepreneur->name }} 的微信二维码"
                   class="w-full h-auto">
              <button @click="showQr = false" class="btn-ink w-full mt-6">关闭</button>
            </div>
          </div>
        </div>
      @endif
    </div>
  </article>
</div>

@endsection
