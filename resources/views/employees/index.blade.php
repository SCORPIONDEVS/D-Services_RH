@extends('layouts.app')
@section('title', 'Funcionários')
@section('heading', '👥 Funcionários')
@section('actions')
    <a class="btn" target="_blank" href="{{ route('employees.labels', ['status' => $status, 'team' => request('team')]) }}">🏷 Etiquetas</a>
    <a class="btn" href="{{ route('employees.export', ['status' => $status]) }}">⬇ Exportar CSV</a>
    <a class="btn primary" href="{{ route('employees.create') }}">+ Novo funcionário</a>
@endsection

@section('content')
<div class="tabs">
    <a href="{{ route('employees.index', array_merge(request()->except('page'), ['status' => 'ativo'])) }}" class="{{ $status === 'ativo' ? 'on' : '' }}">Ativos <b>{{ $counts['ativo'] }}</b></a>
    <a href="{{ route('employees.index', array_merge(request()->except('page'), ['status' => 'inativo'])) }}" class="{{ $status === 'inativo' ? 'on' : '' }}">Inativos <b>{{ $counts['inativo'] }}</b></a>
    <a href="{{ route('employees.index', array_merge(request()->except('page'), ['status' => 'todos'])) }}" class="{{ $status === 'todos' ? 'on' : '' }}">Todos</a>
</div>

<form method="get" class="filters card">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Nome, CPF ou líder…" class="grow">
    <select name="team"><option value="">Equipe: todas</option>@foreach ($teams as $t)<option @selected(request('team') === $t)>{{ $t }}</option>@endforeach</select>
    <select name="company"><option value="">Empresa: todas</option>@foreach ($companies as $t)<option @selected(request('company') === $t)>{{ $t }}</option>@endforeach</select>
    <select name="job_title"><option value="">Cargo: todos</option>@foreach ($jobTitles as $t)<option @selected(request('job_title') === $t)>{{ $t }}</option>@endforeach</select>
    <button class="btn">Filtrar</button>
</form>

<div class="vac-legend">
    <span><i class="dot vac-on"></i> Em férias</span>
    <span><i class="dot vac-due"></i> Férias vencendo (prazo de gozo em até {{ $vacationAlertDays }} dias) ou vencidas</span>
</div>

<div class="card table-wrap">
    <table class="grid">
        <thead><tr><th class="id">ID</th><th>Nome</th><th>Equipe</th><th>Cargo</th><th>Admissão</th><th>Fim 45d</th><th>Fim 90d</th><th>Venc. ASO</th><th>Status</th><th class="r"></th></tr></thead>
        <tbody>
        @forelse ($employees as $e)
            @php
                $today = today();
                $vac = $e->vacationStatus($today, $vacationAlertDays);
                $vacClass = match ($vac['status'] ?? null) { 'em_ferias' => 'vac-on', 'vencendo', 'vencida' => 'vac-due', default => '' };
            @endphp
            <tr class="{{ $vacClass }}">
                <td class="id">{{ $e->id }}</td>
                <td><a href="{{ route('employees.show', $e) }}"><b>{{ $e->name }}</b></a><div class="sub">{{ $e->company }}</div>
                    @if ($vac)
                        <div class="vac-tag">
                            @if ($vac['status'] === 'em_ferias') 🌴 Em férias até {{ \App\Support\Fmt::date($vac['date']) }}
                            @elseif ($vac['status'] === 'vencendo') ⏳ Férias vencem em {{ $vac['days'] }} {{ $vac['days'] === 1 ? 'dia' : 'dias' }} ({{ \App\Support\Fmt::date($vac['date']) }})
                            @else ⚠ Férias vencidas desde {{ \App\Support\Fmt::date($vac['date']) }}
                            @endif
                        </div>
                    @endif
                </td>
                <td>{{ $e->team }}</td>
                <td>{{ $e->job_title }}</td>
                <td>{{ \App\Support\Fmt::date($e->admission_date) }}</td>
                <td class="{{ $e->status === 'ATIVO' && $e->trial45End && $e->trial45End->between($today, $today->copy()->addDays(15)) ? 'hot' : '' }}">{{ \App\Support\Fmt::date($e->trial45End) }}</td>
                <td class="{{ $e->status === 'ATIVO' && $e->trial90End && $e->trial90End->between($today, $today->copy()->addDays(15)) ? 'hot' : '' }}">{{ \App\Support\Fmt::date($e->trial90End) }}</td>
                <td class="{{ $e->status === 'ATIVO' && $e->asoAdmissionExpiry && $e->asoAdmissionExpiry->lte($today->copy()->addDays(30)) ? 'hot' : '' }}">{{ \App\Support\Fmt::date($e->asoAdmissionExpiry) }}</td>
                <td>{{ \App\Support\Fmt::cell($e->status, 'badge') }}</td>
                <td class="r nowrap">
                    <a class="btn sm" href="{{ route('employees.edit', $e) }}">Editar</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="empty">Nenhum funcionário encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('module._pager', ['rows' => $employees])
@endsection
