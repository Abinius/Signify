@extends('layouts.app')

@section('title', '确认密码')

@section('content')
<div class="max-w-md mx-auto px-6 py-20">
  <h1 class="font-display text-display-lg font-black text-ink mb-6 text-center">CONFIRM PASSWORD</h1>
  <p class="text-sm text-ink-soft leading-relaxed mb-10">这是一项安全操作，请输入密码以继续。</p>

  <form method="POST" action="{{ route('password.confirm') }}" class="space-y-8">
    @csrf
    <div>
      <label class="label-caption text-muted">密码</label>
      <input type="password" name="password" required autocomplete="current-password" class="input-line">
      @error('password') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn-ink w-full">确认</button>
  </form>
</div>
@endsection
