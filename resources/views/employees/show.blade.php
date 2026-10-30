@extends('layouts.app')
@section('title', $e->name)
@section('heading', $e->name)
@section('actions')
    <a class="btn" href="{{ route('employees.clinic', $e) }}" target="_blank">🩺 Guia clínica</a>
    <a class="btn" href="{{ route('employees.print', $e) }}" target="_blank">🖨 Ficha</a>
    <a class="btn primary" href="{{ route('employees.edit', $e) }}">Editar</a>
@endsection

@section('content')
<div class="kpis">
    <div class="kpi"><small>Status</small><b>{{ \App\Support\Fmt::cell($e->status, 'badge') }}</b></div>
    <div class="kpi"><small>Cargo / equipe</small><b class="t">{{ $e->job_title }}<br><span class="sub">{{ $e->team }} · {{ $e->company }}</span></b></div>
    <div class="kpi"><small>Experiência (45d / 90d)</small><b class="t">{{ \App\Support\Fmt::date($e->trial45End) }}<br>{{ \App\Support\Fmt::date($e->trial90End) }}</b></div>
    <div class="kpi"><small>ASO admissional vence</small><b class="t">{{ \App\Support\Fmt::date($e->asoAdmissionExpiry) ?: '—' }}</b></div>
    <div class="kpi"><small>Período aquisitivo de férias</small><b class="t">{{ \App\Support\Fmt::date($e->admission_date) }} → {{ \App\Support\Fmt::date($e->acquisitiveEnd) }}<br><span class="sub">gozo até {{ \App\Support\Fmt::date($e->grantEnd) }}</span></b></div>
    <div class="kpi"><small>VT/dia · VR/dia</small><b class="t">{{ \App\Support\Fmt::money($e->vtPerDay) }} · {{ \App\Support\Fmt::money($e->vrPerDay) }}<br><span class="sub">Prêmio assiduidade: {{ $e->vaAssiduityEligible ? 'elegível' : 'não elegível' }}</span></b></div>
</div>

@include('employees._data')

@if ($e->dependents->count())
<section class="card"><h3>Dependentes</h3>
    <table class="grid"><thead><tr><th>Nome</th><th>Nascimento</th><th>CPF</th><th>Parentesco</th></tr></thead><tbody>
    @foreach ($e->dependents as $d)
        <tr><td>{{ $d->name }}</td><td>{{ \App\Support\Fmt::date($d->birth_date) }}</td><td>{{ $d->cpf }}</td><td>{{ $d->relationship }}</td></tr>
    @endforeach
    </tbody></table>
</section>
@endif

<section class="card"><h3>Atestados ({{ $certificates->count() }})</h3>
    <table class="grid"><thead><tr><th>Data</th><th>Tipo</th><th>Dias</th><th>CID</th><th>Descrição</th><th>Status</th></tr></thead><tbody>
    @forelse ($certificates as $c)
        <tr><td>{{ \App\Support\Fmt::date($c->start_date) }}</td><td>{{ $c->type }}</td><td>{{ $c->days }}</td><td>{{ $c->cid }}</td><td>{{ $c->cid_description }}</td><td>{{ \App\Support\Fmt::cell($c->status, 'badge') }}</td></tr>
    @empty <tr><td colspan="6" class="empty">Nenhum atestado.</td></tr> @endforelse
    </tbody></table>
</section>

<section class="card"><h3>Ocorrências de ponto ({{ $occurrences->count() }})</h3>
    <table class="grid"><thead><tr><th>Data</th><th>Tipo</th><th>Motivo</th><th>Status</th></tr></thead><tbody>
    @forelse ($occurrences as $o)
        <tr><td>{{ \App\Support\Fmt::date($o->date) }}</td><td>{{ $o->kind }}</td><td>{{ $o->reason }}</td><td>{{ \App\Support\Fmt::cell($o->status, 'badge') }}</td></tr>
    @empty <tr><td colspan="4" class="empty">Nenhuma ocorrência.</td></tr> @endforelse
    </tbody></table>
</section>



<form method="post" action="{{ route('employees.destroy', $e) }}" data-confirm="Excluir definitivamente este funcionário e seus dependentes?" class="danger-zone">
    @csrf @method('DELETE')
    <button class="btn danger sm">Excluir funcionário</button>
</form>
@endsection
