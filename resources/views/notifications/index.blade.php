@extends('layouts.app')

@section('title', '通知')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-16" x-data="{ marking: false }">
  <p class="label-caption text-accent mb-4">NOTIFICATIONS</p>
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
</div>
@endsection
