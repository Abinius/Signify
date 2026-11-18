@extends('layouts.app')

@section('title', '智库')

@section('content')

<section class="max-w-7xl mx-auto px-6 pt-16 pb-10 text-center">
  <h1 class="font-display text-display-lg font-black text-ink mb-4">{{ \App\Models\Setting::get('site_name', 'SIGNIFY') }} · FIGURES</h1>
  <p class="mt-4 text-ink-soft max-w-2xl leading-relaxed mx-auto">每一份引领行业的远见，都值得被看见。</p>
</section>

<section class="max-w-7xl mx-auto px-6 pb-10">
  <form method="GET" action="{{ route('entrepreneurs.index') }}"
        class="flex flex-col sm:flex-row gap-3 sm:items-center">
    <div class="relative flex-1">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
           class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none">
        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
      </svg>
      <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="搜索姓名、行业..."
             aria-label="搜索" class="search-input pl-10">
    </div>
    <div class="sm:w-44">
      <select name="industry" class="search-input" aria-label="行业">
        <option value="">全部行业</option>
        @foreach($industries as $industry)
          <option value="{{ $industry }}" @selected(($filters['industry'] ?? '') === $industry)>{{ $industry }}</option>
        @endforeach
      </select>
    </div>
    <div class="sm:w-44">
      <select name="city" class="search-input" aria-label="城市">
        <option value="">全部城市</option>
        @foreach($cities as $city)
          <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn-ink flex-shrink-0">筛选</button>
  </form>
  <p class="mt-6 text-sm text-muted">共 {{ $entrepreneurs->total() }} 位名家</p>
</section>

<section class="max-w-7xl mx-auto px-6 pb-6">
  @if($entrepreneurs->isEmpty())
    <div class="py-24 text-center">
      <p class="font-display text-display-md font-bold text-ink">暂无符合条件的记录</p>
      <p class="mt-3 text-muted">试试调整筛选条件</p>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-14">
      @foreach($entrepreneurs as $entrepreneur)
        <a href="{{ $entrepreneur->url }}" class="group block border border-hairline bg-surface">
          <div class="aspect-square overflow-hidden border-b border-hairline">
            @if($entrepreneur->avatar)
              <img src="{{ asset('storage/'.$entrepreneur->avatar) }}" alt="{{ $entrepreneur->name }}"
                   class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-500">
            @else
              <div class="w-full h-full bg-ink/5 flex items-center justify-center">
                <span class="font-display text-6xl text-ink/20">{{ mb_substr($entrepreneur->name, 0, 1) }}</span>
              </div>
            @endif
          </div>
          <div class="p-6">
            <div class="flex items-center justify-center gap-2">
              <h3 class="font-display text-xl font-bold text-ink group-hover:text-accent transition-colors duration-200">{{ $entrepreneur->name }}</h3>
              @if($entrepreneur->is_featured)
                <img src="{{ asset('icons/recommend.svg') }}" alt="推荐"
                     class="w-5 h-5 object-contain flex-shrink-0">
              @endif
            </div>
            <p class="label-caption text-muted mt-2 text-center">{{ $entrepreneur->industry }} · {{ $entrepreneur->city }}</p>
            @if($entrepreneur->bio)
              <p class="mt-3 text-sm text-ink-soft line-clamp-2 leading-relaxed">{{ $entrepreneur->bio }}</p>
            @endif
          </div>
        </a>
      @endforeach
    </div>
  @endif
</section>

<div class="max-w-7xl mx-auto px-6 pb-16">
  <x-pagination :paginator="$entrepreneurs" />
</div>

@push('scripts')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": @json(\App\Models\Setting::get('site_name', 'SIGNIFY')), "item": @json(url('/')) },
        { "@type": "ListItem", "position": 2, "name": "Figures", "item": @json(url('/entrepreneurs')) }
      ]
    },
    {
      "@type": "ItemList",
      "itemListElement": [
        @foreach($entrepreneurs as $e)
        { "@type": "ListItem", "position": {{ $loop->index + 1 }}, "name": @json($e->name), "url": @json(url($e->url)) }{{ $loop->last ? '' : ',' }}
        @endforeach
      ]
    }
  ]
}
</script>
@endpush

@endsection
