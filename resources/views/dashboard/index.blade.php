@extends('layouts.app')
@section('title', 'Painel')
@section('heading', '🏠 Painel · ' . $today->translatedFormat('d \d\e F \d\e Y'))

@section('content')
<div class="kpis">
    <a class="kpi" href="{{ route('employees.index') }}"><small>Funcionários ativos</small><b>{{ $kpi['active'] }}</b></a>
    <a class="kpi" href="{{ route('employees.index', ['status' => 'inativo']) }}"><small>Inativos</small><b>{{ $kpi['inactive'] }}</b></a>
    <a class="kpi" href="{{ route('m.index', 'job-openings') }}"><small>Vagas em andamento</small><b>{{ $kpi['openings'] }}</b></a>
    <a class="kpi" href="{{ route('m.index', 'certificates') }}"><small>Atestados (30 dias)</small><b>{{ $kpi['certs30'] }}</b></a>
</div>

<div class="cols">
    <section class="card"><h3>Hoje no calendário RH (dia {{ $today->day }})</h3>
        @forelse ($tasks as $t)
            <div class="row"><span class="badge {{ $t->area === 'RH' ? 'warn' : 'ok' }}">{{ $t->area }}</span> {{ $t->title }}</div>
        @empty <p class="muted">Sem tarefas fixas hoje.</p> @endforelse
        @if ($upcomingTasks->count())
            <h4>Próximos 5 dias</h4>
            @foreach ($upcomingTasks as $t)<div class="row sub"><b>dia {{ $t->day }}</b> · {{ $t->area }} · {{ $t->title }}</div>@endforeach
        @endif
    </section>

    <section class="card"><h3>Contratos de experiência vencendo (15 dias)</h3>
        @forelse ($trials as $t)
            <div class="row"><a href="{{ route('employees.show', $t['e']) }}">{{ $t['e']->name }}</a>
                <span class="sub">{{ $t['e']->team }}</span> <span class="badge warn">{{ $t['label'] }} · {{ \App\Support\Fmt::date($t['date']) }}</span></div>
        @empty <p class="muted">Nenhum vencimento nos próximos 15 dias.</p> @endforelse
    </section>

    <section class="card"><h3>ASO admissional vencido / a vencer (30 dias)</h3>
        @forelse ($asos->take(12) as $e)
            <div class="row"><a href="{{ route('employees.show', $e) }}">{{ $e->name }}</a>
                <span class="badge {{ $e->asoAdmissionExpiry->isPast() ? 'bad' : 'warn' }}">{{ \App\Support\Fmt::date($e->asoAdmissionExpiry) }}</span></div>
        @empty <p class="muted">Tudo em dia.</p> @endforelse
        @if ($asos->count() > 12)<p class="muted">+ {{ $asos->count() - 12 }} outros</p>@endif
    </section>

    <section class="card"><h3>Férias: período aquisitivo completo sem lançamento</h3>
        @forelse ($vacations->take(12) as $e)
            <div class="row"><a href="{{ route('employees.show', $e) }}">{{ $e->name }}</a>
                <span class="sub">aquisitivo até {{ \App\Support\Fmt::date($e->acquisitiveEnd) }}</span>
                <span class="badge {{ $e->grantEnd->isPast() ? 'bad' : 'warn' }}">gozo até {{ \App\Support\Fmt::date($e->grantEnd) }}</span></div>
        @empty <p class="muted">Nenhuma pendência.</p> @endforelse
        @if ($vacations->count() > 12)<p class="muted">+ {{ $vacations->count() - 12 }} outros</p>@endif
    </section>

    <section class="card"><h3>Quadro de funcionários (previsto × ativos)</h3>
        <table class="grid"><thead><tr><th>Cargo</th><th>Quadro</th><th>Ativos</th><th>Dif.</th></tr></thead><tbody>
        @foreach ($quota as $q)
            <tr><td>{{ $q['name'] }}</td><td>{{ $q['quota'] }}</td><td>{{ $q['active'] }}</td>
                <td class="{{ $q['diff'] < 0 ? 'hot' : '' }}">{{ $q['diff'] > 0 ? '+' : '' }}{{ $q['diff'] }}</td></tr>
        @endforeach
        </tbody></table>
    </section>

    <section class="card"><h3>Vagas em andamento</h3>
        @forelse ($openings as $v)
            <div class="row"><b>#{{ $v->number }}</b> {{ $v->job_title }} <span class="sub">{{ $v->team }} · {{ $v->leader }}</span>
                {{ \App\Support\Fmt::cell($v->stage, 'badge') }}</div>
        @empty <p class="muted">Sem vagas abertas.</p> @endforelse
    </section>

    <section class="card"><h3>Ativos por equipe</h3><canvas id="chTeam" height="200"></canvas></section>
    <section class="card"><h3>Atestados por mês</h3><canvas id="chCerts" height="200"></canvas></section>
    <section class="card"><h3>Candidatos por status</h3><canvas id="chCand" height="200"></canvas></section>

    <section class="card"><h3>Aniversariantes do mês</h3>
        @forelse ($birthdays as $e)
            <div class="row">{{ $e->birth_date->format('d/m') }} · <a href="{{ route('employees.show', $e) }}">{{ $e->name }}</a> <span class="sub">{{ $e->team }}</span></div>
        @empty <p class="muted">Nenhum.</p> @endforelse
    </section>
</div>

<script type="application/json" id="dash-data">{!! json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (!window.Chart) return;
    var d = JSON.parse(document.getElementById('dash-data').textContent);
    var palette = ['#1f6feb', '#2da44e', '#d29922', '#cf222e', '#8250df', '#57606a'];
    function make(id, type, src, label) {
        new Chart(document.getElementById(id), {
            type: type,
            data: { labels: src.labels, datasets: [{ label: label, data: src.data, backgroundColor: palette, borderRadius: 4 }] },
            options: { plugins: { legend: { display: type === 'doughnut' } }, scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {} }
        });
    }
    make('chTeam', 'doughnut', d.team, 'Ativos');
    make('chCerts', 'bar', d.certs, 'Atestados');
    make('chCand', 'bar', d.candidates, 'Candidatos');
})();
</script>
@endpush
