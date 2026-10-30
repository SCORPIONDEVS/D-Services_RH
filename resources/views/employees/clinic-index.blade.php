@extends('layouts.app')
@section('title', 'Guia de Clínica')
@section('heading', '🏥 Guia de Clínica')
@section('actions')
    <a class="btn" href="{{ route('employees.labels') }}" target="_blank">Etiquetas de prontuário</a>
@endsection

@section('content')
<form method="get" class="filters card">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar colaborador…" class="grow">
    <select name="status" onchange="this.form.submit()">
        <option value="ativo" @selected($status === 'ativo')>Somente ativos</option>
        <option value="inativo" @selected($status === 'inativo')>Desligados</option>
        <option value="todos" @selected($status === 'todos')>Todos</option>
    </select>
    <select name="team" onchange="this.form.submit()">
        <option value="">Todas as equipes</option>
        @foreach ($teams as $t)<option value="{{ $t }}" @selected(request('team') === $t)>{{ $t }}</option>@endforeach
    </select>
    <select name="tipo" onchange="this.form.submit()" title="Tipo de exame usado ao abrir a guia">
        @foreach ($types as $k => $label)<option value="{{ $k }}" @selected($k === $type)>Exame: {{ $label }}</option>@endforeach
    </select>
    <button class="btn">Filtrar</button>
</form>

<div class="card table-wrap">
    <table class="grid">
        <thead><tr><th>Colaborador</th><th>Cargo</th><th>Equipe</th><th>Admissão</th><th>ASO admissional</th><th></th></tr></thead>
        <tbody>
        @forelse ($rows as $e)
            <tr>
                <td><b>{{ $e->name }}</b></td><td>{{ $e->job_title }}</td><td>{{ $e->team }}</td>
                <td>{{ \App\Support\Fmt::date($e->admission_date) }}</td>
                <td>{{ \App\Support\Fmt::date($e->aso_admission_date) }}</td>
                <td><a class="btn sm primary" target="_blank" href="{{ route('employees.clinic', [$e, 'tipo' => $type]) }}">Gerar guia</a></td>
            </tr>
        @empty <tr><td colspan="6" class="empty">Nenhum colaborador encontrado.</td></tr> @endforelse
        </tbody>
    </table>
    @include('module._pager', ['rows' => $rows])
</div>
@endsection
