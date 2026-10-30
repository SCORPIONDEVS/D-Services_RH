@extends('layouts.app')
@section('title', $cfg['title'])
@section('heading', $cfg['icon'] . ' ' . $cfg['title'] . ' — ' . ($row->exists ? 'editar' : 'novo'))

@section('content')
<form method="post" class="card form-grid"
      action="{{ $row->exists ? route('m.update', [$module, $row->id]) : route('m.store', $module) }}">
    @csrf
    @if ($row->exists) @method('PUT') @endif

    @foreach ($cfg['fields'] as $f)
        @include('module._field', ['f' => $f, 'value' => $row->{$f[0]}, 'prefix' => ''])
    @endforeach

    <datalist id="dl-employees">@foreach ($employeeNames as $n)<option value="{{ $n }}">@endforeach</datalist>
    @foreach ($suggest as $key => $items)
        <datalist id="dl-{{ $key }}">@foreach ($items as $n)<option value="{{ $n }}">@endforeach</datalist>
    @endforeach

    <div class="form-actions wide">
        <button class="btn primary">Salvar</button>
        <a class="btn ghost" href="{{ route('m.index', $module) }}">Cancelar</a>
    </div>
</form>
@endsection
