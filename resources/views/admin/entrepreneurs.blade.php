@extends('layouts.app')

@section('title', '全部企业家')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-12"
     x-data="{ ids: [], get allIds() { return @js($entrepreneurs->pluck('id')->all()) } }">

  <p class="label-caption text-accent mb-4 text-center">ADMIN · ENTREPRENEURS</p>
  <h1 class="font-display text-display-lg font-black text-ink mb-10 text-center">全部企业家</h1>

  <form method="GET" action="{{ route('admin.entrepreneurs') }}"
        class="border-b border-hairline pb-6 mb-8 flex flex-col md:flex-row gap-5 md:items-end">
    <div class="flex-1">
      <label class="label-caption text-muted">搜索</label>
      <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="搜索姓名..." class="input-line">
    </div>
    <div class="w-full md:w-44">
      <label class="label-caption text-muted">状态</label>
      <select name="status" class="input-line">
        <option value="">全部</option>
        @foreach(['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已拒绝'] as $val => $label)
          <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn-ink flex-shrink-0">筛选</button>
  </form>

  {{-- 批量操作表单 --}}
  <form method="POST" action="{{ route('admin.batch-approve') }}" id="batch-approve-form" class="hidden">@csrf
    <template x-for="id in ids"><input type="hidden" name="ids[]" :value="id"></template>
  </form>
  <form method="POST" action="{{ route('admin.batch-reject') }}" id="batch-reject-form" class="hidden">@csrf
    <template x-for="id in ids"><input type="hidden" name="ids[]" :value="id"></template>
  </form>

  <div class="mb-6 flex items-center gap-6 flex-wrap">
    <label class="flex items-center gap-2 text-sm text-ink-soft cursor-pointer">
      <input type="checkbox" @change="ids = $event.target.checked ? allIds : []" class="h-4 w-4 border-hairline">
      全选
    </label>
    <div class="flex items-center gap-3">
      <button type="button" class="btn-outline !py-2 !px-5" @click="ids.length && document.getElementById('batch-approve-form').submit()">批量通过</button>
      <button type="button" class="btn-outline !py-2 !px-5" @click="ids.length && document.getElementById('batch-reject-form').submit()">批量拒绝</button>
    </div>
    <span class="text-xs text-muted" x-show="ids.length" x-text="'已选 ' + ids.length + ' 项'"></span>
  </div>

  <div class="border border-hairline bg-surface">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-hairline">
            <th class="px-4 py-3 w-10"><span class="sr-only">选择</span></th>
            <th class="px-4 py-3 label-caption text-muted">姓名</th>
            <th class="px-4 py-3 label-caption text-muted">行业 · 城市</th>
            <th class="px-4 py-3 label-caption text-muted">状态</th>
            <th class="px-4 py-3 label-caption text-muted">浏览量</th>
            <th class="px-4 py-3 label-caption text-muted text-right">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-hairline">
          @foreach($entrepreneurs as $entrepreneur)
            <tr>
              <td class="px-4 py-4">
                <input type="checkbox" :value="{{ $entrepreneur->id }}" x-model="ids" class="h-4 w-4 border-hairline">
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  @if($entrepreneur->avatar)
                    <img src="{{ asset('storage/'.$entrepreneur->avatar) }}" alt="" class="w-9 h-9 rounded-full object-cover border border-hairline flex-shrink-0">
                  @else
                    <div class="w-9 h-9 rounded-full bg-ink/5 border border-hairline flex items-center justify-center text-sm flex-shrink-0">
                      {{ mb_substr($entrepreneur->name, 0, 1) }}
                    </div>
                  @endif
                  <a href="{{ $entrepreneur->url }}" target="_blank" rel="noopener"
                     class="font-display font-bold text-ink hover:text-accent transition-colors">{{ $entrepreneur->name }}</a>
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-ink-soft">{{ $entrepreneur->industry }} · {{ $entrepreneur->city }}</td>
              <td class="px-4 py-4 whitespace-nowrap">
                @if($entrepreneur->status === 'approved')
                  <span class="text-xs text-status-success">已通过</span>
                @elseif($entrepreneur->status === 'pending')
                  <span class="text-xs text-status-warning">待审核</span>
                @else
                  <span class="text-xs text-status-danger">已拒绝</span>
                @endif
                @if($entrepreneur->is_featured)
                  <span class="bg-ink text-paper text-[10px] uppercase tracking-widest px-2 py-0.5 ml-2">推荐</span>
                @endif
              </td>
              <td class="px-4 py-4 text-sm tabular-nums text-ink-soft">{{ number_format($entrepreneur->view_count ?? 0) }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                  <form method="POST" action="{{ route('admin.toggle-featured', $entrepreneur) }}">
                    @csrf
                    <button class="label-caption text-accent hover:opacity-70 transition-opacity">{{ $entrepreneur->is_featured ? '取消推荐' : '设为推荐' }}</button>
                  </form>
                  @if($entrepreneur->status === 'pending')
                    <form method="POST" action="{{ route('admin.approve', $entrepreneur) }}">
                      @csrf
                      <button class="label-caption text-status-success hover:opacity-70 transition-opacity">通过</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reject', $entrepreneur) }}">
                      @csrf
                      <button class="label-caption text-status-danger hover:opacity-70 transition-opacity">拒绝</button>
                    </form>
                  @endif
                  <form method="POST" action="{{ route('admin.destroy', $entrepreneur) }}"
                        onsubmit="return confirm('确认删除「{{ $entrepreneur->name }}」？此操作不可撤销。')">
                    @csrf
                    @method('DELETE')
                    <button class="label-caption text-status-danger hover:opacity-70 transition-opacity">删除</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="px-6 py-4 border-t border-hairline">
      <x-pagination :paginator="$entrepreneurs" />
    </div>
  </div>
</div>
@endsection
