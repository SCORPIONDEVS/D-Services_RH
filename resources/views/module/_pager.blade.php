@if ($rows->hasPages())
<div class="pager">
    @if ($rows->onFirstPage()) <span class="btn sm disabled">‹ Anterior</span>
    @else <a class="btn sm" href="{{ $rows->previousPageUrl() }}">‹ Anterior</a> @endif
    <span class="muted">Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }} · {{ $rows->total() }} registro(s)</span>
    @if ($rows->hasMorePages()) <a class="btn sm" href="{{ $rows->nextPageUrl() }}">Próxima ›</a>
    @else <span class="btn sm disabled">Próxima ›</span> @endif
</div>
@else
<div class="pager"><span class="muted">{{ $rows->total() }} registro(s)</span></div>
@endif
