@extends('layouts.app')

@section('title', '忘记密码')

@section('content')
<div class="max-w-md mx-auto px-6 py-20">
  <h1 class="font-display text-display-lg font-black text-ink mb-6 text-center">FORGOT PASSWORD</h1>
  <p class="text-sm text-ink-soft leading-relaxed mb-10">输入注册邮箱，我们将发送重置链接。</p>

  @if(session('status'))
    <div class="mb-8 border border-hairline border-l-4 border-l-status-success bg-surface px-5 py-4">
      <p class="text-sm text-status-success">{{ session('status') }}</p>
    </div>
  @endif

  <form method="POST" action="{{ route('password.email') }}" class="space-y-8">
    @csrf
    <div>
      <label class="label-caption text-muted">邮箱</label>
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-line">
      @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn-ink w-full">发送重置链接</button>
  </form>

  <p class="mt-10 text-sm text-muted text-center">
    <a href="{{ route('login') }}" class="text-ink hover:text-accent transition-colors">← 返回登录</a>
  </p>
</div>
@endsection
