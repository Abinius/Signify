@extends('layouts.app')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/cropper.min.css') }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/cropper.min.js') }}"></script>
@endpush

@section('title', '个人中心')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-16" x-data="profileUpload({{ $errors->has('reason') ? 'true' : 'false' }}, @js(old('social_links', $entrepreneur->social_links ?? [])))">
  <p class="label-caption text-accent mb-4 text-center">MY PROFILE</p>
  <h1 class="font-display text-display-lg font-black text-ink mb-10 text-center">个人中心</h1>

  @if(!$entrepreneur)
    <div class="border border-hairline bg-surface p-10">
      <h2 class="font-display text-display-md font-bold text-ink mb-2">创建企业家档案</h2>
      <p class="text-sm text-muted mb-8">创建后自动通过，获「推荐」后进入智库。</p>
      <form method="POST" action="{{ route('profile.create') }}" class="max-w-md space-y-6">
        @csrf
        <div>
          <label class="label-caption text-muted">姓名</label>
          <input type="text" name="name" value="{{ old('name') }}" required autofocus class="input-line">
          @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-ink">创建档案</button>
      </form>
    </div>
  @else
    <p class="mb-4 text-sm text-ink-soft">
      @if($entrepreneur->status === 'pending')
        档案已提交；获「推荐」后进入智库。
      @else
        完善资料后可生成个人名片，方便分享。
      @endif
    </p>

    {{-- 申请理由弹窗 --}}
    <div x-show="applyModal" x-cloak @keydown.escape.window="applyModal = false"
         class="fixed inset-0 z-[300] bg-ink/60 backdrop-blur-sm grid place-items-center p-6"
         @click.self="applyModal = false">
      <form method="POST" action="{{ route('profile.featured-request') }}"
            class="bg-surface border border-hairline p-8 w-full max-w-md shadow-float">
        @csrf
        <p class="label-caption text-muted mb-4">推荐申请</p>
        <label class="label-caption text-muted">申请理由</label>
        <textarea name="reason" rows="4" required placeholder="请简要说明申请推荐的理由" class="input-line resize-none"></textarea>
        @error('reason') <p class="field-error">{{ $message }}</p> @enderror
        <div class="mt-6 flex items-center gap-3 justify-end">
          <button type="button" @click="applyModal = false" class="label-caption text-muted hover:text-ink">取消</button>
          <button type="submit" class="btn-ink !py-2 !px-5">提交申请</button>
        </div>
      </form>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          class="border border-hairline bg-surface p-10 space-y-8">
      @csrf
      @method('PATCH')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <label class="label-caption text-muted">形象照</label>
          <div class="mt-3">
            <button type="button" @click="$refs.avatarInput.click()"
                    class="w-24 h-24 border border-hairline overflow-hidden relative grid place-items-center
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
          </div>
          <input type="file" name="avatar" x-ref="avatarInput" class="hidden"
                 accept="image/jpeg,image/png,image/gif,image/webp" @change="openCrop('avatar', $event.target)">
          <input type="file" name="portrait" x-ref="portraitInput" class="hidden"
                 accept="image/jpeg,image/png,image/gif,image/webp">
          @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="label-caption text-muted">姓名</label>
          <input type="text" name="name" value="{{ old('name', $entrepreneur->name) }}" class="input-line">
          @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
      </div>

      {{-- 名片 / 状态 / 推荐：并列文本（上下线条） --}}
      <div class="border-y border-hairline py-4 flex items-center justify-between gap-6 flex-wrap">
        @if($entrepreneur->status === 'pending')
          <span class="label-caption text-status-warning">待审核</span>
        @elseif($entrepreneur->status === 'approved')
          <span class="label-caption text-status-success">已收录</span>
        @elseif($entrepreneur->status === 'rejected')
          <span class="label-caption text-status-danger">已拒绝</span>
        @endif
        @if($entrepreneur->status === 'approved')
          @php
            $cooldownUntil = $entrepreneur->featured_rejected_at?->addDays(\App\Models\Entrepreneur::FEATURED_COOLDOWN_DAYS);
            $cooldownDays = $cooldownUntil && $cooldownUntil->isFuture()
                ? (int) ceil($cooldownUntil->diffInSeconds(now()) / 86400)
                : 0;
            $canApply = !$entrepreneur->is_featured
                && $entrepreneur->featured_request_status !== 'pending'
                && (!$cooldownUntil || $cooldownUntil->isPast());
          @endphp
          @if($entrepreneur->is_featured)
            <span class="label-caption text-status-success">已推荐</span>
          @elseif($entrepreneur->featured_request_status === 'pending')
            <span class="label-caption text-status-warning">推荐待审核</span>
          @elseif($entrepreneur->featured_request_status === 'rejected' && !$canApply)
            <span class="label-caption text-status-danger">未通过 · {{ $cooldownDays }}天后</span>
          @else
            <button type="button" @click="applyModal = true"
                    class="label-caption text-accent hover:opacity-70 transition-opacity">申请推荐 →</button>
          @endif
        @endif
        <a href="{{ route('entrepreneurs.show', $entrepreneur->share_slug ?? $entrepreneur->id) }}"
           class="label-caption text-accent hover:opacity-70 transition-opacity">查看我的名片</a>
      </div>

      {{-- 专属链接：vour.cn/u/{slug}，用户自己填 --}}
      <div x-data="slugForm(@js(old('share_slug', $entrepreneur->share_slug ?? '')))">
        <label class="label-caption text-muted">专属链接</label>
        <p class="mt-1 text-xs text-muted">分享名片时显示的短链地址</p>
        <div class="mt-2 flex items-center gap-2">
          <span class="label-caption text-muted whitespace-nowrap">vour.cn/u/</span>
          <input type="text" name="share_slug" x-model="slug"
                 @input.debounce.300ms="check"
                 placeholder="zhangsan" class="input-line flex-1"
                 value="{{ old('share_slug', $entrepreneur->share_slug) }}">
          <span x-show="status" class="label-caption flex-shrink-0"
                :class="statusClass" x-text="statusText"></span>
        </div>
        <p class="mt-1 text-xs text-muted">至少含一个字母，仅支持小写字母、数字和连字符</p>
        @error('share_slug') <p class="field-error">{{ $message }}</p> @enderror
      </div>

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

      <div>
        <label class="label-caption text-muted">职务</label>
        <input type="text" name="title" value="{{ old('title', $entrepreneur->title) }}" placeholder="如：创始人 / CEO" class="input-line">
        @error('title') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
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
                     autocomplete="off" placeholder="选择或输入城市" @focus="openPicker()" class="input-line">
              <button type="button" id="locate-btn"
                      class="label-caption text-accent hover:opacity-70 transition-opacity flex-shrink-0 pb-2.5 inline-flex items-center gap-1" title="定位到当前城市">
                <svg viewBox="0 0 1024 1024" fill="currentColor" aria-hidden="true" class="w-3.5 h-3.5">
                  <path d="M905.54112 557.370751l-48.570034 83.462918q-2.638505 3.715446-5.384704 6.838574L581.974233 989.708666q-22.131135 28.05431-57.508642 33.277473-35.323661 5.169316-64.616453-15.400255l-0.053847-0.053847-3.769293-2.746199-0.107694-0.107694q-8.346292-6.623186-14.915631-14.969478l-269.719843-342.144117-0.161541-0.215388q-2.692352-3.015434-5.007775-6.353951l-0.107694-0.107694-2.153882-3.230823-1.992341-3.230822-4.523151-6.300105Q89.381476 530.931852 86.635277 413.545297L86.527583 404.283605q0.753859-168.325859 125.517459-286.466273Q336.431714 0 512.350006 0q175.702904 0 300.089575 118.086567Q937.310876 236.711605 937.310876 405.145158q-0.107694 10.230938-0.700012 20.192641l-0.53847 7.538586q-5.061622 65.908782-30.477427 124.494366z m-130.41754 25.038875l3.82314-5.923175 0.215388-0.269235q56.539396-76.624343 57.616337-171.664376 0-125.625153-94.609256-214.634316-95.040032-89.386093-229.819183-89.386093-135.048386 0-230.142266 89.278399-94.555409 88.847622-95.147726 214.795857 0 91.970751 57.777878 173.925952l0.376929-0.269235 2.530811 4.038528 1.184635 1.346176 262.558186 333.15166 262.612033-333.205507 1.076941-1.184635z m-129.771376-37.531389q55.139373-55.19322 55.139373-133.217587 0-78.078214-55.19322-133.271433-55.19322-55.19322-133.271433-55.19322-78.078214 0-133.271434 55.19322-55.19322 55.19322-55.19322 133.271433 0 78.078214 55.19322 133.217587 55.247067 55.247067 133.271434 55.247067 78.078214 0 133.271433-55.247067zM449.833588 473.853986q-25.792734-25.738887-25.792734-62.139489 0-36.454449 25.738887-62.193335 25.792734-25.792734 62.193335-25.792734 36.454449 0 62.193336 25.792734 25.792734 25.738887 25.792734 62.193335 0 36.400602-25.792734 62.139489-25.738887 25.792734-62.193336 25.792734-36.400602 0-62.193335-25.792734z"/>
                </svg>
                <span id="locate-label">定位</span>
              </button>
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

      <div>
        <label class="label-caption text-muted">简介</label>
        <textarea name="bio" rows="8" class="input-line resize-none">{{ old('bio', $entrepreneur->bio) }}</textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <label class="label-caption text-muted">联系电话</label>
          <input type="text" name="contact_phone" value="{{ old('contact_phone', $entrepreneur->contact_phone) }}" class="input-line">
        </div>
        <div>
          <label class="label-caption text-muted">联系邮箱</label>
          <input type="email" name="contact_email" value="{{ old('contact_email', $entrepreneur->contact_email) }}" class="input-line">
        </div>
      </div>

      <div>
        <label class="label-caption text-muted">社交平台主页</label>
        <div class="space-y-3">
          <template x-for="(link, i) in socialLinks" :key="i">
            <div class="flex items-center gap-3">
              <img :src="'{{ asset('icons') }}/' + socialIconFromUrl(link)"
                   x-show="(link || '').trim() !== ''"
                   class="w-5 h-5 flex-shrink-0 object-contain" alt="社交平台图标">
              <input type="url" name="social_links[]" x-model="socialLinks[i]"
                     :placeholder="'https://… 社交主页链接 ' + (i + 1)"
                     class="input-line flex-1">
              <button type="button" @click="removeSocial(i)"
                      class="label-caption text-muted hover:text-status-danger transition-colors flex-shrink-0"
                      aria-label="删除该社交链接">删除</button>
            </div>
          </template>
        </div>
        <button type="button" @click="addSocial()" x-show="socialLinks.length < 5"
                class="mt-3 inline-flex items-center gap-2 label-caption text-accent hover:opacity-70 transition-opacity">
          <span class="w-4 h-4 border border-current grid place-items-center leading-none">＋</span>
          添加社交主页
        </button>
        <p class="mt-1.5 text-xs text-muted" x-show="socialLinks.length >= 5">最多添加 5 个社交主页</p>
        @error('social_links') <p class="field-error">{{ $message }}</p> @enderror
        @error('social_links.*') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="label-caption text-muted">微信二维码</label>
        <div class="mt-3 flex items-center gap-6">
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

      <div class="text-center">
        <button type="submit" class="btn-ink">保存修改</button>
      </div>
    </form>
  @endif
</div>
<script>
  // 社交图标映射：与 PHP 端共用 config/social-icons.php（单一来源，避免两端漂移）
  window.SOCIAL_ICONS = @json(config('social-icons.map', []));
  window.SOCIAL_ICONS_DEFAULT = @json(config('social-icons.default', 'google.svg'));

  window.profileUpload = function (applyModal, socialLinks) {
    return {
      applyModal: !!applyModal,
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

  window.slugForm = function (initial) {
    return {
      slug: (initial || ''),
      status: '',
      checking: false,

      check() {
        this.slug = this.slug.toLowerCase().replace(/[^a-z0-9-]/g, '');
        if (this.slug.length < 3) {
          this.status = '';
          return;
        }
        this.checking = true;
        this.status = '校验中…';
        var token = document.querySelector('meta[name="csrf-token"]').content;
        var self = this;
        fetch('/my/profile/check-slug?slug=' + encodeURIComponent(this.slug), {
          headers: { 'X-CSRF-TOKEN': token }
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.checking = false;
            self.status = data.available ? '可用 ✓' : '已被使用';
          })
          .catch(function () {
            self.checking = false;
            self.status = '';
          });
      },

      get statusClass() {
        if (this.checking) return 'text-muted';
        if (this.status === '可用 ✓') return 'text-status-success';
        return 'text-status-danger';
      },

      get statusText() {
        return this.status;
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
