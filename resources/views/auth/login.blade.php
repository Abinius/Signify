@extends('layouts.app')

@section('title', '登录')

@section('content')
<div class="max-w-md mx-auto px-6 py-20">
  <h1 class="font-display text-display-lg font-black text-ink mb-10 text-center">SIGN IN</h1>

  @if(session('status'))
    <div class="mb-8 border border-hairline border-l-4 border-l-status-success bg-surface px-5 py-4">
      <p class="text-sm text-status-success">{{ session('status') }}</p>
    </div>
  @endif

  <form method="POST" action="{{ route('login') }}" class="space-y-8">
    @csrf

    <div>
      <label class="label-caption text-muted">邮箱</label>
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-line">
      @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
      <div class="flex items-center justify-between">
        <label class="label-caption text-muted">密码</label>
        <a href="{{ route('password.request') }}" class="text-xs text-muted hover:text-ink transition-colors">忘记密码？</a>
      </div>
      <input type="password" name="password" required autocomplete="current-password" class="input-line">
      @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 text-sm text-ink-soft">
      <input type="checkbox" name="remember" class="h-4 w-4 border-hairline">
      记住我
    </label>

    <button type="submit" class="btn-ink w-full">SIGN IN</button>
  </form>

  <p class="mt-10 text-sm text-muted text-center">
    还没有账号？<a href="{{ route('register') }}" class="text-ink hover:text-accent transition-colors">立即加入</a>
  </p>
</div>
@endsection
