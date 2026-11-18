{{-- 菜单行：SVG 图标 + 短标签（+ 可选未读角标）。href→链接，tab→打开设置弹窗，submit→退出表单。 --}}
@props([
  'href' => null,
  'tab' => null,
  'submit' => false,
  'confirm' => null,
  'icon' => null,
  'label' => null,
  'unread' => false,
  'danger' => false,
])
@php
  $row = 'group flex items-center gap-4 w-full text-left py-4 border-b border-hairline';
  $title = 'font-display text-display-md font-bold transition-colors'
    . ($danger ? ' text-status-danger' : ' text-ink group-hover:text-accent');
  $ic = 'w-5 h-5 flex-shrink-0 transition-colors'
    . ($danger ? ' text-status-danger' : ' text-muted group-hover:text-accent');
  // 在 PHP 内构建 Alpine 表达式（避免 Blade 组件属性对 $dispatch 做 PHP 插值）
  $click = 'menuOpen = false' . ($tab ? "; \$dispatch('open-settings', { tab: '{$tab}' })" : '');
@endphp

@if($submit)
<form method="POST" action="{{ $href }}" @if($confirm) onsubmit="return confirm('{{ $confirm }}')" @endif class="block">
  @csrf
  <button type="submit" class="{{ $row }}">
@elseif($tab)
<button type="button" @click="{{ $click }}" class="{{ $row }}">
@else
<a href="{{ $href }}" @click="menuOpen = false" class="{{ $row }}">
@endif

  @if($icon)<x-icon :name="$icon" :class="$ic"/>@endif
  <span class="{{ $title }}">
    {{ $label }}
    @if($unread)
      <span x-show="$store.ui.unread > 0" x-cloak
            class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full
                   bg-status-danger text-paper text-[10px] leading-[18px] font-bold ml-1 align-middle"
            x-text="$store.ui.unread > 99 ? '99+' : $store.ui.unread"></span>
    @endif
  </span>

@if($submit)
  </button>
</form>
@elseif($tab)
</button>
@else
</a>
@endif
