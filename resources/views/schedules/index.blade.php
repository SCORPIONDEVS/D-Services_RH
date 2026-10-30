@extends('layouts.app')
@section('title', 'Escalas de turno')
@section('heading', '🗓️ Escalas · ' . $shift . 'º turno')

@section('content')
<div class="tabs">
    <a class="on" href="{{ route('schedules.index') }}">Grade do Terminal</a>
    <a href="{{ route('schedules.patterns') }}">Padrão mensal (12x36)</a>
</div>
@if ($fixed->count())
<details class="card"><summary><b>Posições fixas do {{ $shift }}º turno</b> <span class="muted">(quem ocupa Colab. A–L e folguistas)</span></summary>
    <div class="row">@foreach ($fixed as $f)<span class="badge muted">{{ $f->slot }}: {{ $f->person ?: '—' }} <small>{{ $f->job_title }}</small></span>@endforeach</div>
    <a class="btn sm" href="{{ route('m.index', 'schedule-slots') }}">Editar posições</a>
</details>
@endif
<form method="get" class="filters card">
    <select name="shift" onchange="this.form.submit()">
        <option value="1" @selected($shift === 1)>1º turno (05:00–13:30)</option>
        <option value="2" @selected($shift === 2)>2º turno (13:30–22:00)</option>
    </select>
    <select name="month" onchange="this.form.submit()">
        @foreach ($months as $m)<option value="{{ $m }}" @selected($m === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('F/Y') }}</option>@endforeach
    </select>
    <span class="muted">Clique numa célula para editar · ★ marca a liderança do dia · “FOLGA” = descanso</span>
</form>

<div class="card table-wrap">
@if ($grid->isEmpty())
    <p class="empty">Nenhuma escala neste mês.</p>
@else
    <table class="grid compact sched">
        <thead><tr><th>Data</th>@foreach ($slots as $s)<th>{{ $s }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($grid as $date => $cells)
            @php $d = \Carbon\Carbon::parse($date); @endphp
            <tr class="{{ $d->isWeekend() ? 'weekend' : '' }}">
                <td class="nowrap"><b>{{ $d->format('d/m') }}</b> <span class="sub">{{ $d->translatedFormat('D') }}</span></td>
                @foreach ($slots as $s)
                    @php $c = $cells->firstWhere('slot', $s); @endphp
                    <td>
                        @if ($c)
                        <div class="cell" data-url="{{ route('schedules.update', $c) }}">
                            <input class="js-sched {{ $c->person === 'FOLGA' ? 'off' : '' }}" name="person" value="{{ $c->person }}">
                            <label class="star" title="Liderança"><input type="checkbox" class="js-sched" name="leader_rotation" value="1" @checked($c->leader_rotation)>★</label>
                        </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</div>
@endsection
