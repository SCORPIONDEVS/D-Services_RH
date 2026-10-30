@extends('layouts.app')
@section('title', 'Benefícios VT/VR')
@section('heading', '🚌 Benefícios VT/VR')
@section('actions')
    <a class="btn" href="{{ route('benefits.export', ['period' => $period]) }}">⬇ Exportar CSV</a>
@endsection

@section('content')
<form method="get" class="filters card">
    <label>Período
        <select name="period" onchange="this.form.submit()">
            @foreach ($options as $value => $label)<option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>@endforeach
        </select>
    </label>
</form>

<form method="post" action="{{ route('benefits.generate') }}" class="card inline-bar">
    @csrf <input type="hidden" name="period" value="{{ $period }}">
    <span>Gera o período selecionado para os ativos com VT ou VR. Os dias vêm do padrão mensal de escala (ex.: ÍMPAR 8/7) quando cadastrado; ajuste abaixo.</span>
    <button class="btn primary">Gerar lançamentos do período</button>
</form>

<div class="kpis">
    <div class="kpi"><small>Total VT</small><b id="tot-vt">{{ \App\Support\Fmt::money($totals['vt']) }}</b></div>
    <div class="kpi"><small>Total VR</small><b id="tot-vr">{{ \App\Support\Fmt::money($totals['vr']) }}</b></div>
    <div class="kpi"><small>Total a pedir</small><b id="tot-total">{{ \App\Support\Fmt::money($totals['total']) }}</b></div>
</div>

<div class="card table-wrap">
    <table class="grid compact">
        <thead><tr><th>Colaborador</th><th>Equipe</th><th>VT/dia</th><th>Dias VT</th><th>VT</th><th>VR/dia</th><th>Dias VR</th><th>VR</th><th>Ajuste +</th><th>Ajuste −</th><th>Outros ajustes/correções</th><th>Motivo</th><th>Total</th><th></th></tr></thead>
        <tbody>
        @forelse ($entries as $x)
            <tr data-url="{{ route('benefits.update', $x) }}">
                <td><b>{{ $x->employee->name }}</b></td>
                <td>{{ $x->employee->team }}</td>
                <td>{{ \App\Support\Fmt::money($x->vt_rate) }}</td>
                <td><input class="num js-inline" type="number" step="0.5" min="0" max="31" name="vt_days" value="{{ $x->vt_days + 0 }}"></td>
                <td data-out="vt">{{ \App\Support\Fmt::money($x->vt_value) }}</td>
                <td>{{ \App\Support\Fmt::money($x->vr_rate) }}</td>
                <td><input class="num js-inline" type="number" step="0.5" min="0" max="31" name="vr_days" value="{{ $x->vr_days + 0 }}"></td>
                <td data-out="vr">{{ \App\Support\Fmt::money($x->vr_value) }}</td>
                <td><input class="num js-inline" type="number" step="0.01" min="0" name="adjustment_plus" value="{{ $x->adjustment_plus + 0 }}"></td>
                <td><input class="num js-inline" type="number" step="0.01" min="0" name="adjustment_minus" value="{{ $x->adjustment_minus + 0 }}"></td>
                @php $other = $x->adjustment_vt + $x->adjustment_vr + $x->correction_vt + $x->correction_vr; @endphp
                <td class="r" title="{{ $x->correction_reason }}">{{ $other != 0 ? \App\Support\Fmt::money($other) : '' }}</td>
                <td><input class="js-inline" type="text" name="reason" value="{{ $x->reason }}" maxlength="255" title="{{ $x->correction_reason }}"></td>
                <td data-out="total"><b>{{ \App\Support\Fmt::money($x->total) }}</b></td>
                <td class="nowrap"><a class="btn sm" target="_blank" href="{{ route('benefits.receipt', $x) }}">Recibo</a></td>
            </tr>
        @empty
            <tr><td colspan="14" class="empty">Nenhum lançamento neste período. Clique em “Gerar lançamentos”.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
