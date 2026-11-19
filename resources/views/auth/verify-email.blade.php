@extends('layouts.app')

@section('title', '验证邮箱')

@section('content')
<div class="max-w-md mx-auto px-6 py-20">
  <h1 class="font-display text-display-lg font-black text-ink mb-6 text-center">VERIFY EMAIL</h1>
  <p class="text-sm text-ink-soft leading-relaxed mb-10">注册后请先验证邮箱，验证链接已发送至你的邮箱。</p>

  @if(session('status') === 'verification-link-sent')
    <div class="mb-8 border border-hairline border-l-4 border-l-status-success bg-surface px-5 py-4">
      <p class="text-sm text-status-success">新的验证链接已发送。</p>
    </div>
  @endif

  <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn-outline w-full">重新发送验证邮件</button>
  </form>

  <form method="POST" action="{{ route('logout') }}" class="mt-8 text-center">
    @csrf
    <button type="submit" class="text-xs text-muted hover:text-ink transition-colors">退出登录</button>
  </form>
</div>
@endsection
