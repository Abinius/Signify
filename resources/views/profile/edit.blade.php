@extends('layouts.app')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/cropper.min.css') }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/cropper.min.js') }}"></script>
@endpush

@section('title', '个人中心')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-16" x-data="profileUpload(@js(old('social_links', $entrepreneur->social_links ?? [])))">
  <header class="pb-8 text-center">
    <h1 class="font-display text-display-lg font-black text-ink">MY PROFILE</h1>
    <p class="mt-3 text-ink-soft leading-relaxed">维护你的公开档案，获「推荐」后进入智库。</p>
  </header>

  @if(!$entrepreneur)
    <form method="POST" action="{{ route('profile.create') }}" class="mt-10 space-y-6">
      @csrf
      <div>
        <label class="label-caption text-muted">姓名</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus class="input-line">
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div class="text-center">
        <button type="submit" class="btn-ink">创建档案</button>
      </div>
    </form>
  @else
    @if($entrepreneur->status === 'pending')
      <p class="mt-8 text-sm text-ink-soft">档案已提交；获「推荐」后进入智库。</p>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          class="bento mt-10">
      @csrf
      @method('PATCH')

      {{-- 基本资料：重要信息，形象照 + 姓名/职务，横跨两列、占两格 --}}
      <section class="bento-card md:col-span-2 space-y-8">
        <h2 class="label-caption text-muted">基本资料</h2>
        <div class="flex flex-col sm:flex-row gap-8 sm:items-start">
          <div class="flex flex-col items-start">
            <label class="label-caption text-muted">形象照</label>
            <button type="button" @click="$refs.avatarInput.click()"
                    class="mt-3 w-24 h-24 border border-hairline overflow-hidden relative grid place-items-center
                           {{ $entrepreneur->avatar ? '' : 'bg-ink/5' }} hover:opacity-80 transition-opacity"
                    aria-label="上传形象照">
              <img :src="avatarPreview || '{{ $entrepreneur->avatar ? asset('storage/'.$entrepreneur->avatar) : '' }}'"
                   x-show="avatarPreview || {{ $entrepreneur->avatar ? 'true' : 'false' }}"
                   class="w-full h-full object-cover" alt="形象照">
              <span x-show="!(avatarPreview || {{ $entrepreneur->avatar ? 'true' : 'false' }})"
                    class="label-caption text-muted">点击上传</span>
              <span x-show="avatarPreview || {{ $entrepreneur->avatar ? 'true' : 'false' }}"
                    class="absolute bottom-0 inset-x-0 bg-ink/60 text-paper text-[10px] text-center py-0.5">点击上传</span>
            </button>
            <input type="file" name="avatar" x-ref="avatarInput" class="hidden"
                   accept="image/jpeg,image/png,image/gif,image/webp" @change="openCrop('avatar', $event.target)">
            <input type="file" name="portrait" x-ref="portraitInput" class="hidden"
                   accept="image/jpeg,image/png,image/gif,image/webp">
            @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="flex-1 w-full space-y-8">
            <div>
              <label class="label-caption text-muted">姓名</label>
              <input type="text" name="name" value="{{ old('name', $entrepreneur->name) }}" class="input-line">
              @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="label-caption text-muted">职务</label>
              <input type="text" name="title" value="{{ old('title', $entrepreneur->title) }}" class="input-line">
              @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
          </div>
        </div>
      </section>

      {{-- 分类：研究领域 / 城市 --}}
      <section class="bento-card md:col-span-2">
        <h2 class="label-caption text-muted">分类</h2>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="label-caption text-muted">研究领域</label>
            <input type="text" name="industry" value="{{ old('industry', $entrepreneur->industry) }}" class="input-line">
          </div>
          <div>
            <label class="label-caption text-muted">城市</label>
            <div class="relative" x-data="cityPicker(@js($cities), @js(old('city', $entrepreneur->city)))"
                 @keydown.escape.window="open = false" @click.outside="open = false">
              <div class="flex items-end gap-2">
                <input type="text" name="city" id="city-input" x-model="query"
                       autocomplete="off" @focus="openPicker()" class="input-line">
              </div>

              {{-- 桌面端：输入框下方下拉面板 --}}
              <div x-show="open" x-cloak
                   class="hidden md:block absolute left-0 right-0 mt-1 z-40 max-h-80 overflow-y-auto border border-hairline bg-surface shadow-float">
                <template x-for="c in filtered" :key="c">
                  <button type="button" @click="select(c)"
                          class="block w-full text-left px-4 py-2 text-sm hover:bg-paper"
                          :class="c === query ? 'text-accent' : 'text-ink-soft'" x-text="c"></button>
                </template>
                <p x-show="filtered.length === 0" class="px-4 py-3 text-xs text-muted">无匹配城市</p>
              </div>

              {{-- 手机端：全屏城市选择 --}}
              <div x-show="open" x-cloak
                   class="md:hidden fixed inset-0 z-[300] bg-surface flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-hairline">
                  <p class="font-display text-display-md font-bold text-ink">选择城市</p>
                  <button type="button" @click="open = false" class="label-caption text-accent">关闭</button>
                </div>
                <div class="px-6 py-4">
                  <input type="search" x-model="query" x-ref="searchInput" placeholder="搜索城市" autofocus class="input-line">
                </div>
                <div class="flex-1 overflow-y-auto px-2 pb-10">
                  <template x-for="c in filtered" :key="c">
                    <button type="button" @click="select(c)"
                            class="block w-full text-left px-4 py-3 text-base border-b border-hairline hover:bg-paper"
                            :class="c === query ? 'text-accent' : 'text-ink'" x-text="c"></button>
                  </template>
                  <p x-show="filtered.length === 0" class="px-4 py-3 text-xs text-muted">无匹配城市</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- 简介 --}}
      <section class="bento-card md:col-span-2">
        <h2 class="label-caption text-muted">简介</h2>
        <textarea name="bio" rows="8" class="input-line resize-none mt-8">{{ old('bio', $entrepreneur->bio) }}</textarea>
      </section>

      {{-- 联系方式 --}}
      <section class="bento-card md:col-span-2">
        <h2 class="label-caption text-muted">联系方式</h2>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="label-caption text-muted">联系电话</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $entrepreneur->contact_phone) }}" class="input-line">
          </div>
          <div>
            <label class="label-caption text-muted">联系邮箱</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $entrepreneur->contact_email) }}" class="input-line">
          </div>
        </div>
      </section>

      {{-- 社交与媒体 --}}
      <section class="bento-card md:col-span-2">
        <h2 class="label-caption text-muted">社交与媒体</h2>

        <div class="mt-8">
          <label class="label-caption text-muted">社交平台主页</label>
          <div class="mt-4 space-y-3">
            <template x-for="(link, i) in socialLinks" :key="i">
              <div class="flex items-center gap-3">
                <img :src="'{{ asset('icons') }}/' + socialIconFromUrl(link)"
                     x-show="(link || '').trim() !== ''"
                     class="w-5 h-5 flex-shrink-0 object-contain" alt="社交平台图标">
                <input type="url" name="social_links[]" x-model="socialLinks[i]"
                       class="input-line flex-1">
                <button type="button" @click="removeSocial(i)"
                        class="label-caption text-muted hover:text-status-danger transition-colors flex-shrink-0"
                        aria-label="删除该社交链接">删除</button>
              </div>
            </template>
          </div>
          <div class="mt-3">
            <button type="button" @click="addSocial()" :disabled="socialLinks.length >= 5"
                    :class="socialLinks.length >= 5 ? 'opacity-40 cursor-not-allowed' : 'hover:opacity-70'"
                    class="inline-flex items-center gap-2 label-caption text-accent transition-opacity">
              <span class="w-4 h-4 border border-current grid place-items-center leading-none">＋</span>
              添加社交主页
            </button>
          </div>
          @error('social_links') <p class="field-error">{{ $message }}</p> @enderror
          @error('social_links.*') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-8">
          <label class="label-caption text-muted">微信二维码</label>
          <div class="mt-3 flex items-start">
            <button type="button" @click="$refs.qrInput.click()"
                    class="w-24 h-24 border border-hairline overflow-hidden relative grid place-items-center
                           {{ $entrepreneur->wechat_qrcode ? '' : 'bg-ink/5' }} hover:opacity-80 transition-opacity"
                    aria-label="上传微信二维码">
              <img :src="qrPreview || '{{ $entrepreneur->wechat_qrcode ? asset('storage/'.$entrepreneur->wechat_qrcode) : '' }}'"
                   x-show="qrPreview || {{ $entrepreneur->wechat_qrcode ? 'true' : 'false' }}"
                   class="w-full h-full object-contain" alt="微信二维码">
              <span x-show="!(qrPreview || {{ $entrepreneur->wechat_qrcode ? 'true' : 'false' }})"
                    class="label-caption text-muted">点击上传</span>
              <span x-show="qrPreview || {{ $entrepreneur->wechat_qrcode ? 'true' : 'false' }}"
                    class="absolute bottom-0 inset-x-0 bg-ink/60 text-paper text-[10px] text-center py-0.5">点击上传</span>
            </button>
          </div>
          <input type="file" name="wechat_qrcode" x-ref="qrInput" class="hidden"
                 accept="image/jpeg,image/png,image/gif,image/webp" @change="openCrop('qr', $event.target)">
          @error('wechat_qrcode') <p class="field-error">{{ $message }}</p> @enderror
        </div>
      </section>

      <div class="md:col-span-2 text-center">
        <button type="submit" class="btn-ink">保存修改</button>
      </div>
    </form>

    {{-- 裁剪弹窗：形象照 4:5 主裁自动派生 1:1；二维码 1:1 --}}
    <div x-show="cropOpen" x-cloak @keydown.escape.window="cropOpen = false"
         class="fixed inset-0 z-[300] bg-ink/60 backdrop-blur-sm grid place-items-center p-4"
         @click.self="cropOpen = false">
      <div class="bg-surface border border-hairline p-5 w-full max-w-2xl shadow-float
                  flex flex-col max-h-[92vh]">
        <p class="label-caption text-muted mb-4" x-text="cropType === 'qr' ? '裁剪二维码（1:1）' : '裁剪形象照（4:5）'"></p>
        <div class="flex-1 overflow-hidden min-h-0">
          <img x-ref="cropImg" class="max-w-full max-h-full w-auto mx-auto" alt="待裁剪图片">
        </div>
        <div class="mt-5 flex items-center justify-end gap-3 flex-shrink-0">
          <button type="button" @click="cropOpen = false" class="label-caption text-muted hover:text-ink">取消</button>
          <button type="button" @click="confirmCrop()" class="btn-ink !py-2 !px-5">确认裁剪</button>
        </div>
      </div>
    </div>
  @endif
</div>
<script>
  // 社交图标映射：与 PHP 端共用 config/social-icons.php（单一来源，避免两端漂移）
  window.SOCIAL_ICONS = @json(config('social-icons.map', []));
  window.SOCIAL_ICONS_DEFAULT = @json(config('social-icons.default', 'google.svg'));

  window.profileUpload = function (socialLinks) {
    return {
      socialLinks: Array.isArray(socialLinks) ? socialLinks.map(String) : [],
      cropType: null,
      cropSrc: null,
      cropOpen: false,
      cropper: null,
      avatarPreview: null,
      qrPreview: null,

      // 新增社交主页（最多 5 条）
      addSocial() {
        if (this.socialLinks.length < 5) this.socialLinks.push('');
      },

      // 删除第 i 条社交主页
      removeSocial(i) {
        this.socialLinks.splice(i, 1);
      },

      // 按网址域名识别社交平台图标（未知域名回退默认 google.svg）
      // 与 PHP 端 socialIconForUrl 一致：先提取 hostname 再匹配（修复：原实现用完整 URL 匹配导致预览恒为默认图标）
      socialIconFromUrl(url) {
        let host = '';
        try {
          const raw = url || '';
          const withScheme = /^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//.test(raw) ? raw : 'https://' + raw;
          host = new URL(withScheme).hostname.toLowerCase();
        } catch (e) {
          host = (url || '').toLowerCase();
        }
        const map = window.SOCIAL_ICONS || {};
        let icon = window.SOCIAL_ICONS_DEFAULT || 'google.svg';
        Object.keys(map).some(function (k) {
          // 边界匹配：主域或子域名，避免仿冒域名误判
          if (host === k || host.endsWith('.' + k)) { icon = map[k]; return true; }
          return false;
        });
        return icon;
      },

      // 打开裁剪弹窗（type: 'avatar' | 'qr'）
      openCrop(type, input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        const self = this;
        reader.onload = function (e) {
          input.value = ''; // 取消裁剪时不影响原图
          self.cropType = type;
          self.cropSrc = e.target.result;
          self.cropOpen = true;
          self.$nextTick(function () { self.initCropper(); });
        };
        reader.readAsDataURL(file);
      },

      // 初始化 Cropper（头像 4:5 主裁；二维码 1:1）
      initCropper() {
        const img = this.$refs.cropImg;
        if (!img) return;
        if (this.cropper) this.cropper.destroy();
        img.onload = () => {
          this.cropper = new Cropper(img, {
            aspectRatio: this.cropType === 'qr' ? 1 : 4 / 5,
            viewMode: 1,
            autoCropArea: 1,
          });
        };
        img.src = this.cropSrc;
      },

      // 确认裁剪：头像类输出 4:5 形象照 + 自动派生 1:1 头像；二维码输出 1:1
      confirmCrop() {
        if (!this.cropper) return;
        const img = this.$refs.cropImg;
        const data = this.cropper.getData();

        if (this.cropType === 'qr') {
          const canvas = this.cropper.getCroppedCanvas({ width: 512, height: 512 });
          this.setFile('qrInput', canvas, 'qrPreview');
        } else {
          // 形象照 4:5 → 800×1000
          const portraitCanvas = this.cropper.getCroppedCanvas({ width: 800, height: 1000 });
          this.setFile('portraitInput', portraitCanvas, null);
          // 头像 1:1 → 取 4:5 框内中心方区缩放 512（与形象照同构图）
          const side = data.width;
          const sqY = data.y + (data.height - side) / 2;
          const avatarCanvas = document.createElement('canvas');
          avatarCanvas.width = avatarCanvas.height = 512;
          avatarCanvas.getContext('2d').drawImage(img, data.x, sqY, side, side, 0, 0, 512, 512);
          this.setFile('avatarInput', avatarCanvas, 'avatarPreview');
        }
        this.cropOpen = false;
      },

      // 裁剪产物写入隐藏 file input（DataTransfer），随表单原子提交
      setFile(refName, canvas, previewKey) {
        const self = this;
        canvas.toBlob(function (blob) {
          const file = new File([blob], 'crop.jpg', { type: 'image/jpeg' });
          const dt = new DataTransfer();
          dt.items.add(file);
          self.$refs[refName].files = dt.files;
          if (previewKey) self[previewKey] = canvas.toDataURL('image/jpeg');
        }, 'image/jpeg', 0.9);
      }
    };
  };

  window.cityPicker = function (cities, initial) {
    return {
      open: false,
      query: (initial || ''),
      cities: cities || [],
      get filtered() {
        var q = this.query.trim();
        if (!q) return this.cities;
        return this.cities.filter(function (c) { return c.indexOf(q) !== -1; });
      },
      openPicker: function () {
        this.open = true;
        var self = this;
        this.$nextTick(function () {
          if (self.$refs.searchInput && window.matchMedia('(max-width: 767px)').matches) {
            self.$refs.searchInput.focus();
          }
        });
      },
      select: function (c) {
        this.query = c;
        this.open = false;
        if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
      }
    };
  };
</script>
@endsection
