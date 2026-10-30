@extends('layouts.app')
@section('title', $e->exists ? 'Editar ' . $e->name : 'Novo funcionário')
@section('heading', $e->exists ? '✏️ ' . $e->name : '➕ Novo funcionário')

@section('content')
<form method="post" action="{{ $e->exists ? route('employees.update', $e) : route('employees.store') }}">
    @csrf
    @if ($e->exists) @method('PUT') @endif

    @foreach ($sections as $title => $fields)
        <fieldset class="card form-grid">
            <legend>{{ $title }}</legend>
            @foreach ($fields as $f)
                @php
                    if (($f['mask'] ?? null) === 'positions') $f['suggest'] = 'positions';
                @endphp
                @include('module._field', ['f' => $f, 'value' => $e->{$f[0]}, 'prefix' => '', 'relations' => [], 'employeeNames' => [], 'suggest' => []])
            @endforeach
        </fieldset>
    @endforeach

    <datalist id="dl-positions">@foreach ($positions as $p)<option value="{{ $p }}">@endforeach</datalist>

    <fieldset class="card">
        <legend>Dependentes</legend>
        @php $deps = $e->exists ? $e->dependents->values() : collect(); @endphp
        <div class="deps">
            @for ($i = 0; $i < 4; $i++)
                @php $d = $deps[$i] ?? null; @endphp
                <div class="dep">
                    <label class="field"><span>Nome</span><input name="dependents[{{ $i }}][name]" value="{{ old("dependents.$i.name", $d?->name) }}"></label>
                    <label class="field"><span>Nascimento</span><input type="date" name="dependents[{{ $i }}][birth_date]" value="{{ old("dependents.$i.birth_date", $d?->birth_date?->format('Y-m-d')) }}"></label>
                    <label class="field"><span>CPF</span><input name="dependents[{{ $i }}][cpf]" data-mask="cpf" value="{{ old("dependents.$i.cpf", $d?->cpf) }}"></label>
                    <label class="field"><span>Parentesco</span><input name="dependents[{{ $i }}][relationship]" value="{{ old("dependents.$i.relationship", $d?->relationship) }}"></label>
                </div>
            @endfor
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn primary">Salvar</button>
        <a class="btn ghost" href="{{ $e->exists ? route('employees.show', $e) : route('employees.index') }}">Cancelar</a>
    </div>
</form>
@endsection
