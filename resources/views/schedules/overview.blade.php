@extends('layouts.app')
@section('title', 'Escala colorida')
@section('heading', '🎨 Escala colorida')
@section('actions')
    <a class="btn" href="{{ route('schedules.patterns') }}">Editar padrões</a>
@endsection

@php
    // Cor do horário pela faixa (início–fim). [fundo, texto]
    $shiftColors = [
        '06:00-18:00' => ['#8fc1e8', '#10304a'], '07:00-17:00' => ['#a9d6f0', '#10304a'], '07:00-19:00' => ['#7fbfe6', '#10304a'],
        '08:00-17:00' => ['#b8dff3', '#10304a'], '08:00-20:00' => ['#3f9bd6', '#ffffff'], '09:00-21:00' => ['#2f86c4', '#ffffff'],
        '10:00-22:00' => ['#1f6fae', '#ffffff'], '18:00-06:00' => ['#1d4e6b', '#ffffff'], '19:00-07:00' => ['#17384f', '#ffffff'],
    ];
    $shiftOf = function ($txt) use ($shiftColors) {
        if (!$txt || !preg_match('/(\d{1,2}):(\d{2})\s*(?:às|a|-)\s*(\d{1,2}):(\d{2})/u', $txt, $mm)) return [null, null, '#e5e7eb', '#374151'];
        $key = sprintf('%02d:%s-%02d:%s', $mm[1], $mm[2], $mm[3], $mm[4]);
        [$bg, $fg] = $shiftColors[$key] ?? ['#d1d5db', '#111827'];
        return [$key, sprintf('%02d:%s às %02d:%s', $mm[1], $mm[2], $mm[3], $mm[4]), $bg, $fg];
    };
    // [fundo, texto] — texto sempre escuro para ler bem sobre o fundo claro
    $teamColors = ['LIMPEZA' => ['#cfe2f3', '#0b3d63'], 'SEGURANÇA' => ['#d9ead3', '#1e4d12'], 'TERMINAL' => ['#fff2cc', '#7f4f00'], 'SERVIÇOS' => ['#ead1dc', '#5c1a3a']];
@endphp

@section('content')
<style>
    .esc{border-collapse:collapse;font-size:13px;width:100%}
    .esc th{background:#111;color:#fff;padding:6px 10px;text-align:left;position:sticky;top:0;z-index:2;white-space:nowrap}
    .esc td{border:1px solid #d4d4d8;padding:5px 10px;white-space:nowrap}
    .esc td{background:#fff;color:#1f2937}
    .esc td.tm{text-align:center;font-weight:700;font-size:12px;letter-spacing:.02em}
    .esc td.nm{font-weight:500;min-width:260px}
    .esc td.nm.lead{font-weight:800}
    .esc td.nm.off{color:#dc2626}
    .esc td.nm.vago{color:#dc2626;font-style:italic;font-weight:700}
    .esc td.hr{text-align:center;font-weight:600}
    .esc td.p{text-align:center;min-width:92px}
    .esc td.p.imp{background:#fff2cc;color:#7f6000}
    .esc td.p.cur{box-shadow:inset 0 0 0 2px #f59e0b}
    .esc tr.sep td{border-top:2px solid #111}
    .leg{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:10px 0 0}
    .leg span.chip{padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600}
</style>

<form method="get" class="filters card">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Colaborador…" class="grow">
    <select name="status" onchange="this.form.submit()">
        <option value="ativos" @selected($status === 'ativos')>Somente ativos</option>
        <option value="desligados" @selected($status === 'desligados')>Desligados</option>
        <option value="todos" @selected($status === 'todos')>Todos</option>
    </select>
    <select name="team" onchange="this.form.submit()">
        <option value="">Todas as equipes</option>
        @foreach ($teams as $t)<option value="{{ $t }}" @selected(request('team') === $t)>{{ $t }}</option>@endforeach
    </select>
    <select name="de" onchange="this.form.submit()" title="Primeiro mês exibido">
        @foreach ($allMonths as $m)<option value="{{ $m }}" @selected($m === $from)>A partir de {{ \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('M/y') }}</option>@endforeach
    </select>
    <select name="qtd" onchange="this.form.submit()">
        @foreach ([3, 4, 6, 12] as $n)<option value="{{ $n }}" @selected($qtd === $n)>{{ $n }} meses</option>@endforeach
    </select>
    <button class="btn">Filtrar</button>
    <button type="button" class="btn" onclick="window.print()">🖨 Imprimir</button>
</form>

<div class="card table-wrap">
    <table class="esc">
        <thead><tr>
            <th>Nome</th><th>Equipe</th><th>Horário</th>
            @foreach ($months as $m)<th>{{ mb_strtolower(\Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('M/y')) }}</th>@endforeach
        </tr></thead>
        <tbody>
        @php $prevTeam = null; $nowM = now()->format('Y-m'); @endphp
        @forelse ($rows as $row)
            @php
                [$key, $label, $bg, $fg] = $shiftOf($row['schedule']);
                $emp = $row['emp'];
                $off = $emp && $emp->termination_date;
                $vago = !$emp;
                $lead = $emp && str_contains(mb_strtoupper((string) $emp->job_title), 'LIDER');
            @endphp
            <tr class="{{ $prevTeam !== null && $prevTeam !== $row['team'] ? 'sep' : '' }}">
                <td class="nm {{ $vago ? 'vago' : ($off ? 'off' : '') }} {{ $lead ? 'lead' : '' }}">{{ $row['name'] }}</td>
                <td class="tm" style="background:{{ ($teamColors[$row['team']] ?? ['#e5e7eb', '#1f2937'])[0] }};color:{{ ($teamColors[$row['team']] ?? ['#e5e7eb', '#1f2937'])[1] }}">{{ $row['team'] }}</td>
                <td class="hr" style="background:{{ $bg }};color:{{ $fg }}">{{ $label ?? '#N/D' }}</td>
                @foreach ($months as $m)
                    @php $pat = $row['cells'][$m] ?? ''; $imp = str_starts_with(mb_strtoupper($pat), 'ÍMPAR') || str_starts_with(mb_strtoupper($pat), 'IMPAR'); @endphp
                    <td class="p {{ $imp ? 'imp' : '' }} {{ $m === $nowM ? 'cur' : '' }}">{{ $pat }}</td>
                @endforeach
            </tr>
            @php $prevTeam = $row['team']; @endphp
        @empty
            <tr><td colspan="{{ 3 + $months->count() }}" class="empty">Nada encontrado para este filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="leg">
    <b>Horários:</b>
    @foreach ($shiftColors as $k => [$bg, $fg])
        <span class="chip" style="background:{{ $bg }};color:{{ $fg }}">{{ str_replace('-', ' às ', $k) }}</span>
    @endforeach
    <span class="chip" style="background:#fff2cc;color:#7f6000">ÍMPAR</span>
    <span class="chip" style="background:#fff;border:1px solid #d4d4d8">PAR / outros</span>
    <span class="muted">Nome em vermelho = desligado · vermelho itálico = posto sem titular · negrito = líder · contorno laranja = mês atual</span>
</div>
@endsection
