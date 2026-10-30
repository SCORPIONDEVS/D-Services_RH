@extends('layouts.app')
@section('title', 'Histórico de benefícios')
@section('heading', '📜 Histórico de benefícios VT/VR')

@section('content')
<form method="get" class="filters card">
    <select name="block" onchange="this.form.submit()">
        @foreach ($blocks as $b)<option value="{{ $b }}" @selected($b === $block)>{{ $b }}</option>@endforeach
    </select>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Colaborador…" class="grow">
    <button class="btn">Filtrar</button>
    <a class="btn ghost" href="{{ route('benefits.index') }}">Ir para a quinzena atual →</a>
</form>

<div class="card table-wrap">
@if ($byEmployee->isEmpty())
    <p class="empty">Sem dados neste bloco.</p>
@else
    <table class="grid compact">
        <thead><tr><th>Colaborador</th>@foreach ($fields as $f)<th>{{ $f }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($byEmployee as $name => $items)
            @php $map = $items->keyBy('field'); @endphp
            <tr>
                <td class="nowrap"><b>{{ $name }}</b></td>
                @foreach ($fields as $f)
                    @php $x = $map->get($f); @endphp
                    <td class="{{ $x && $x->number !== null ? 'r' : '' }}">{{ $x ? $x->value : '' }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th>Soma</th>@foreach ($fields as $f)<th class="r">{{ $sums[$f] !== null ? number_format($sums[$f], 2, ',', '.') : '' }}</th>@endforeach</tr></tfoot>
    </table>
@endif
</div>
<p class="muted">{{ $byEmployee->count() }} colaborador(es) · valores exatamente como estavam na planilha.</p>
@endsection
