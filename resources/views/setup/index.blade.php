@extends('layouts.app')

@section('title', '系统安装')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-16">
  <p class="label-caption text-accent mb-4 text-center">SETUP</p>
  <h1 class="font-display text-display-lg font-black text-ink mb-10 text-center">系统安装</h1>

  @if($installed)
    <div class="border border-hairline bg-surface p-10 text-center">
      <p class="font-display text-display-md font-bold text-ink">系统已安装</p>
      <p class="text-sm text-muted mt-3">如需重新安装，请先清除数据库数据。</p>
      <a href="{{ route('home') }}" class="btn-ink mt-8">返回首页</a>
    </div>
  @else
    <div x-data="setup()" class="space-y-10">

      <section class="border border-hairline bg-surface p-10">
        <h2 class="font-display text-lg font-bold text-ink mb-6">1 · 数据库连接</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div><label class="label-caption text-muted">主机</label><input x-model="form.host" type="text" class="input-line"></div>
          <div><label class="label-caption text-muted">端口</label><input x-model="form.port" type="number" class="input-line"></div>
          <div><label class="label-caption text-muted">数据库名</label><input x-model="form.database" type="text" class="input-line"></div>
          <div><label class="label-caption text-muted">用户名</label><input x-model="form.username" type="text" class="input-line"></div>
          <div class="md:col-span-2"><label class="label-caption text-muted">密码</label><input x-model="form.password" type="password" class="input-line"></div>
        </div>
        <button class="btn-ink mt-8" @click="testDb" :disabled="testing">测试连接</button>
        <p class="mt-4 text-sm" x-show="dbMsg" x-text="dbMsg" :class="dbOk ? 'text-status-success' : 'text-status-danger'"></p>
      </section>

      <section class="border border-hairline bg-surface p-10" x-show="dbOk" x-cloak>
        <h2 class="font-display text-lg font-bold text-ink mb-6">2 · 安装配置</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div><label class="label-caption text-muted">应用名</label><input x-model="form.app_name" type="text" class="input-line"></div>
          <div><label class="label-caption text-muted">应用 URL</label><input x-model="form.app_url" type="text" class="input-line"></div>
          <div><label class="label-caption text-muted">管理员邮箱</label><input x-model="form.admin_email" type="email" class="input-line"></div>
          <div><label class="label-caption text-muted">管理员密码</label><input x-model="form.admin_password" type="password" class="input-line"></div>
          <div class="md:col-span-2"><label class="label-caption text-muted">确认密码</label><input x-model="form.admin_password_confirmation" type="password" class="input-line"></div>
        </div>
        <button class="btn-ink mt-8" @click="install" :disabled="installing">安装</button>
        <p class="mt-4 text-sm" x-show="installMsg" x-text="installMsg" :class="installOk ? 'text-status-success' : 'text-status-danger'"></p>
      </section>

    </div>

    <style>[x-cloak]{display:none !important}</style>
    <script>
      function setup() {
        return {
          form: {
            host: '127.0.0.1', port: 3306, database: '', username: 'root', password: '',
            app_name: @json(env('APP_NAME', 'Signify')), app_url: window.location.origin,
            admin_email: '', admin_password: '', admin_password_confirmation: '',
          },
          dbOk: false, testing: false, dbMsg: '',
          installOk: false, installing: false, installMsg: '',
          csrf: function () { return document.querySelector('meta[name=csrf-token]').content; },
          async testDb() {
            this.testing = true; this.dbMsg = '';
            try {
              const res = await fetch('{{ route('setup.test-db') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf(), 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
              });
              const data = await res.json();
              this.dbOk = !!data.success; this.dbMsg = data.message || '连接失败';
            } catch (e) { this.dbMsg = '连接失败'; }
            this.testing = false;
          },
          async install() {
            this.installing = true; this.installMsg = '';
            try {
              const res = await fetch('{{ route('setup.install') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf(), 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
              });
              const data = await res.json();
              this.installOk = !!data.success; this.installMsg = data.message || '安装失败';
              if (data.success) { setTimeout(function () { window.location.href = '{{ route('home') }}'; }, 1500); }
            } catch (e) { this.installMsg = '安装失败'; }
            this.installing = false;
          }
        };
      }
    </script>
  @endif
</div>
@endsection
