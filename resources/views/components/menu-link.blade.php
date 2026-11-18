{{-- 侧边栏菜单项。href→链接，submit→退出表单。compact→底部常驻竖排磁贴（icon 上 / 文案下）。 --}}
@props([
  'href' => null,
  'submit' => false,
  'confirm' => null,
  'icon' => null,
  'label' => null,
  'unread' => false,
  'danger' => false,
  'active' => false,
  'compact' => false,
])
@php
  // 磁贴式：未选中 = 纸色地块 + 文字降一档；选中 = 品牌色高亮 + 白字；危险 = 警告色
  $row = $compact
    ? 'group flex flex-col items-center justify-center gap-1.5 w-full text-center px-2 py-2.5 rounded-lg border transition-colors duration-150'
    : 'group flex items-center gap-3 w-full text-left px-3.5 py-2.5 rounded-lg border transition-colors duration-150';
  if ($active) {
    $row .= ' bg-accent border-accent text-paper shadow-float';
  } elseif ($danger) {
    $row .= ' bg-paper border-hairline text-status-danger hover:bg-status-danger/10 hover:border-status-danger/30';
  } else {
    $row .= ' bg-paper border-hairline text-ink-soft hover:text-ink hover:border-hairline-strong';
  }
  $title = $compact ? 'text-xs font-medium leading-none' : 'text-[15px] font-medium';
  $ic = ($compact ? 'w-5 h-5' : 'w-5 h-5 flex-shrink-0') . ' transition-colors'
    . ($active ? ' text-paper/90' : ($danger ? ' text-status-danger' : ' text-muted group-hover:text-current'));
@endphp

@if($submit)
<form method="POST" action="{{ $href }}" @if($confirm) onsubmit="return confirm('{{ $confirm }}')" @endif class="block">
  @csrf
  <button type="submit" class="{{ $row }}">
@else
<a href="{{ $href }}" @click="menuOpen = false" class="{{ $row }}">
@endif

  @if($icon)
    @if($compact)
      <span class="relative">
        <x-icon :name="$icon" :class="$ic"/>
        @if($unread)
          <span x-show="$store.ui.unread > 0" x-cloak
                class="absolute -top-1.5 -right-1.5 min-w-[15px] h-[15px] px-0.5 rounded-full
                       bg-status-danger text-paper text-[9px] leading-[15px] font-bold
                       flex items-center justify-center"
                x-text="$store.ui.unread > 99 ? '99+' : $store.ui.unread"></span>
        @endif
      </span>
    @else
      <x-icon :name="$icon" :class="$ic"/>
    @endif
  @endif

  <span class="{{ $title }}">
    {{ $label }}
    @if($unread && !$compact)
      <span x-show="$store.ui.unread > 0" x-cloak
            class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full
                   bg-status-danger text-paper text-[10px] leading-[18px] font-bold ml-1 align-middle"
            x-text="$store.ui.unread > 99 ? '99+' : $store.ui.unread"></span>
    @endif
  </span>

@if($submit)
  </button>
</form>
@else
</a>
@endif
