@if ($paginator->hasPages())
  <nav role="navigation" aria-label="分页" class="flex items-center justify-center gap-4 label-caption">
    @if ($paginator->onFirstPage())
      <span class="text-muted opacity-50">上一页</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
         class="text-accent hover:opacity-70 transition-opacity">上一页</a>
    @endif

    <span class="text-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next"
         class="text-accent hover:opacity-70 transition-opacity">下一页</a>
    @else
      <span class="text-muted opacity-50">下一页</span>
    @endif
  </nav>
@endif
