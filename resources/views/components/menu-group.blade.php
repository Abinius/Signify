{{-- 侧边栏分组：小标题 + 磁贴行。组间距由父级 space-y 控制（明显大于行间距）。 --}}
@props(['title' => null])
<div class="space-y-2">
  @if($title)
    <p class="px-3.5 mb-1 text-xs font-semibold tracking-widest text-muted uppercase">{{ $title }}</p>
  @endif
  {{ $slot }}
</div>
