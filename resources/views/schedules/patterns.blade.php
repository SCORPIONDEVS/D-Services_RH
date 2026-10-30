@extends('layouts.app')
@section('title', 'Padrão mensal de escala')
@section('heading', '🔄 Padrão mensal de escala')
@section('actions')
    <a class="btn" href="{{ route('m.index', 'shift-patterns') }}">Gerenciar registros</a>
    <a class="btn" href="{{ route('m.index', 'pattern-assignments') }}">Postos ímpar/par</a>
    <form method="post" action="{{ route('schedules.patterns.postos') }}" style="display:inline" onsubmit="return confirm('Substituir a lista de postos pelos colaboradores ativos do padrão mensal?')">@csrf @if(isset($month))<input type="hidden" name="month" value="{{ $month }}">@endif<button class="btn">⚡ Preencher postos automaticamente</button></form>
@endsection

@section('content')
<div class="tabs">
    <a href="{{ route('schedules.index') }}">Grade do Terminal</a>
    <a class="on" href="{{ route('schedules.patterns') }}">Padrão mensal (12x36)</a>
</div>

<form method="get" class="filters card">
    <select name="month" onchange="this.form.submit()">
        @foreach ($months as $m)<option value="{{ $m }}" @selected($m === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('F/Y') }}</option>@endforeach
    </select>
    <select name="status" onchange="this.form.submit()">
        <option value="ativos" @selected($status === 'ativos')>Somente ativos</option>
        <option value="desligados" @selected($status === 'desligados')>Desligados</option>
        <option value="todos" @selected($status === 'todos')>Todos</option>
    </select>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Colaborador…" class="grow">
    <button class="btn">Filtrar</button>
    <span class="muted">“ÍMPAR 8/7” = trabalha dias ímpares: 8 dias na 1ª quinzena e 7 na 2ª (base do cálculo de VT/VR).</span>
</form>

<div class="cols">
    <div class="card table-wrap" style="grid-column:span 2">
        <table class="grid compact">
            <thead><tr><th>Colaborador</th><th>Equipe</th><th>Horário</th><th>Padrão em {{ $month }}</th></tr></thead>
            <tbody>
            @forelse ($rows as $p)
                <tr><td><b>{{ $p->employee_name }}</b></td><td>{{ $p->team }}</td><td>{{ $p->display_schedule ?? $p->schedule }}</td>
                    <td><input class="js-pattern" data-url="{{ route('schedules.patterns.update', $p) }}" value="{{ $p->pattern }}" maxlength="40"></td></tr>
            @empty <tr><td colspan="4" class="empty">Sem padrões neste mês.</td></tr> @endforelse
            </tbody>
        </table>
    </div>

    <section class="card"><h3>Postos 12x36: quem cobre dias ímpares e pares</h3>
        <table class="grid compact"><thead><tr><th>Posto</th><th>Dias ímpares</th><th>Dias pares</th></tr></thead><tbody>
        @foreach ($assignments as $a)<tr><td>{{ $a->position }}</td><td>{{ $a->odd_person }}</td><td>{{ $a->even_person }}</td></tr>@endforeach
        </tbody></table>
    </section>
</div>
@endsection
