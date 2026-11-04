@extends('layouts.admin')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/cropper.min.css') }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/cropper.min.js') }}"></script>
@endpush

@section('title', '系统设置')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-12">
  <p class="label-caption text-accent mb-4 text-center">ADMIN · SETTINGS</p>
  <h1 class="font-display text-display-lg font-black text-ink mb-10 text-center">系统设置</h1>

  <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
        class="space-y-8" x-data="settingsCrop()">
    @csrf

    {{-- 基础信息 --}}
    <div class="border border-hairline bg-surface p-10 space-y-6">
      <p class="label-caption text-muted">基础信息</p>
      <div>
        <label class="label-caption text-muted">站点名称</label>
        <input type="text" name="site_name" value="{{ old('site_name', $values['site_name']) }}" required
               class="input-line">
        @error('site_name') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="label-caption text-muted">站点描述</label>
        <textarea name="site_description" rows="3" class="input-line resize-none">{{ old('site_description', $values['site_description']) }}</textarea>
        @error('site_description') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="label-caption text-muted">首页 Banner 图片路径</label>
        <input type="text" name="banner_image" value="{{ old('banner_image', $values['banner_image']) }}"
               placeholder="如：/landingbanner.webp 或 https://…" class="input-line">
        <p class="text-xs text-muted mt-2">落地页 Hero 背景图路径或完整 URL；留空回退默认 banner。</p>
        @error('banner_image') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </div>

    {{-- 分享卡片 --}}
    <div class="border border-hairline bg-surface p-10 space-y-6">
      <div>
        <p class="label-caption text-muted">微信 / 社交媒体分享卡片</p>
        <p class="text-xs text-muted mt-2">用于微信等平台分享时的默认标题、描述与图片（企业家名片页优先使用其自身头像与简介）。</p>
      </div>
      <div>
        <label class="label-caption text-muted">分享卡片标题</label>
        <input type="text" name="share_title" value="{{ old('share_title', $values['share_title']) }}" class="input-line">
        @error('share_title') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="label-caption text-muted">分享卡片描述</label>
        <textarea name="share_description" rows="3" class="input-line resize-none">{{ old('share_description', $values['share_description']) }}</textarea>
        @error('share_description') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="label-caption text-muted">分享卡片图片</label>
        <div class="mt-3 flex items-center gap-6">
          <button type="button" @click="$refs.shareImageInput.click()"
                  class="w-24 h-24 border border-hairline overflow-hidden relative grid place-items-center
                         {{ $values['share_image'] ? '' : 'bg-ink/5' }} hover:opacity-80 transition-opacity"
                  aria-label="上传分享卡片图片">
            <img :src="sharePreview || '{{ $values['share_image'] ?? '' }}'"
                 x-show="sharePreview || {{ $values['share_image'] ? 'true' : 'false' }}"
                 class="w-full h-full object-contain" alt="分享卡片图片">
            <span x-show="!(sharePreview || {{ $values['share_image'] ? 'true' : 'false' }})"
                  class="label-caption text-muted">点击上传</span>
            <span x-show="sharePreview || {{ $values['share_image'] ? 'true' : 'false' }}"
                  class="absolute bottom-0 inset-x-0 bg-ink/60 text-paper text-[10px] text-center py-0.5">点击上传</span>
          </button>
          
        </div>
        <input type="file" name="share_image_file" x-ref="shareImageInput" class="hidden"
               accept="image/jpeg,image/png,image/gif,image/webp" @change="openShareCrop($event.target)">
        @error('share_image_file') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      {{-- 分享卡片图片裁剪弹窗 --}}
      <div x-show="cropOpen" x-cloak @keydown.escape.window="cropOpen = false"
           class="fixed inset-0 z-[300] bg-ink/60 backdrop-blur-sm grid place-items-center p-4"
           @click.self="cropOpen = false">
        <div class="bg-surface border border-hairline p-5 w-full max-w-2xl shadow-float flex flex-col max-h-[92vh]">
          <p class="label-caption text-muted mb-4">裁剪分享卡片图片（1.91:1）</p>
          <div class="flex-1 overflow-hidden min-h-0">
            <img x-ref="shareCropImg" class="max-w-full max-h-full w-auto mx-auto" alt="待裁剪图片">
          </div>
          <div class="mt-5 flex items-center justify-end gap-3 flex-shrink-0">
            <button type="button" @click="cropOpen = false" class="label-caption text-muted hover:text-ink">取消</button>
            <button type="button" @click="confirmShareCrop()" class="btn-ink !py-2 !px-5">确认裁剪</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Footer --}}
    <div class="border border-hairline bg-surface p-10 space-y-6">
      <p class="label-caption text-muted">Footer</p>
      <div>
        <label class="label-caption text-muted">版权信息</label>
        <input type="text" name="footer_copyright" value="{{ old('footer_copyright', $values['footer_copyright']) }}"
               placeholder="如：© 2026 SIGNIFY" class="input-line">
        @error('footer_copyright') <p class="field-error">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="label-caption text-muted">ICP 备案号</label>
        <input type="text" name="icp_number" value="{{ old('icp_number', $values['icp_number']) }}"
               placeholder="如：京ICP备12345678号（留空则不展示）" class="input-line">
        @error('icp_number') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </div>

    <button type="submit" class="btn-ink">保存设置</button>
  </form>
</div>
<script>
  window.settingsCrop = function () {
    return {
      cropOpen: false,
      cropSrc: null,
      cropper: null,
      sharePreview: null,

      // 选择图片 → 打开裁剪弹窗（1.91:1 分享卡片比例）
      openShareCrop(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        const self = this;
        reader.onload = function (e) {
          input.value = ''; // 取消裁剪时不影响原图
          self.cropSrc = e.target.result;
          self.cropOpen = true;
          self.$nextTick(function () { self.initCropper(); });
        };
        reader.readAsDataURL(file);
      },

      initCropper() {
        const img = this.$refs.shareCropImg;
        if (!img) return;
        if (this.cropper) this.cropper.destroy();
        img.onload = () => {
          this.cropper = new Cropper(img, {
            aspectRatio: 1200 / 630,
            viewMode: 1,
            autoCropArea: 1,
          });
        };
        img.src = this.cropSrc;
      },

      // 确认裁剪 → 输出 1200×630，写入隐藏 file input，随表单原子提交
      confirmShareCrop() {
        if (!this.cropper) return;
        const canvas = this.cropper.getCroppedCanvas({ width: 1200, height: 630 });
        const self = this;
        canvas.toBlob(function (blob) {
          const file = new File([blob], 'share.jpg', { type: 'image/jpeg' });
          const dt = new DataTransfer();
          dt.items.add(file);
          self.$refs.shareImageInput.files = dt.files;
          self.sharePreview = canvas.toDataURL('image/jpeg');
        }, 'image/jpeg', 0.9);
        this.cropOpen = false;
      }
    };
  };
</script>
@endsection
