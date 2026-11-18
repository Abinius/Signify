@extends('layouts.app')

@section('title', '通知')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-16" x-data="{ marking: false, async openItem(el) {
  const url = el.dataset.url;
  if (el.dataset.read === '0') {
    await fetch(el.dataset.readUrl, {
      method: 'POST',
      headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
    });
  }
  if (url && url !== '#') { window.location.href = url; }
  else { window.location.reload(); }
} }">
  <x-icon name="bell" class="block w-5 h-5 text-accent mb-4" />
  <div class="flex items-center justify-between mb-10">
    <h1 class="font-display text-display-lg font-black text-ink">通知</h1>
    @if($unreadCount > 0)
      <button type="button" :disabled="marking"
              @click="marking = true; fetch('{{ route('notifications.read-all') }}', {method:'POST', headers:{'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content}}).then(() => window.location.reload())"
              class="label-caption text-accent hover:opacity-70 transition-opacity">
        全部标为已读 ({{ $unreadCount }})
      </button>
    @endif
  </div>

  @forelse($notifications as $n)
    <a href="{{ $n->data['url'] ?? '#' }}"
       data-url="{{ $n->data['url'] ?? '#' }}"
       data-read="{{ $n->read_at ? '1' : '0' }}"
       data-read-url="{{ route('notifications.read', $n->id) }}"
       @click.prevent="openItem($el)"
       class="block border-b border-hairline py-5 {{ $n->read_at ? 'opacity-60' : '' }}">
      <p class="font-display font-bold text-ink">{{ $n->data['title'] ?? '' }}</p>
      @if(!empty($n->data['body']))
        <p class="text-sm text-muted mt-1">{{ $n->data['body'] }}</p>
      @endif
      <p class="label-caption text-muted mt-2">{{ $n->created_at->diffForHumans() }}</p>
    </a>
  @empty
    <p class="text-muted text-center py-20">暂无通知</p>
  @endforelse

  <div class="pt-8">
    {{ $notifications->links('pagination.simple') }}
  </div>
</div>
@endsection
