@extends('layouts.app')
@section('title', $cfg['title'])
@section('heading', $cfg['icon'] . ' ' . $cfg['title'])
@section('actions')
    @if ($module === 'pattern-assignments')
        <form method="post" action="{{ route('schedules.patterns.postos') }}" style="display:inline" onsubmit="return confirm('Substituir a lista de postos pelos colaboradores ativos do padrão mensal?')">@csrf<button class="btn">⚡ Preencher automaticamente</button></form>
    @endif
    <a class="btn primary" href="{{ route('m.create', $module) }}">+ Novo</a>
@endsection

@section('content')
<form method="get" class="filters card">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar…" class="grow">
    @foreach ($filters as $f)
        <select name="{{ $f[0] }}">
            <option value="">{{ $f[1] }}: todos</option>
            @foreach ($f[2] as $value => $label)<option value="{{ $value }}" @selected((string) request($f[0]) === (string) $value)>{{ $label }}</option>@endforeach
        </select>
    @endforeach
    <button class="btn">Filtrar</button>
    @if (request()->query()) <a class="btn ghost" href="{{ route('m.index', $module) }}">Limpar</a> @endif
</form>

<div class="card table-wrap">
    <table class="grid" data-live-filter>
        <thead><tr>
            @foreach ($cfg['columns'] as $c)<th>{{ $c[1] }}</th>@endforeach
            <th class="r"></th>
        </tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                @foreach ($cfg['columns'] as $c)
                    <td>{{ \App\Support\Fmt::cell(data_get($row, $c[0]), $c[2] ?? null) }}</td>
                @endforeach
                <td class="r nowrap">
                    <a class="btn sm" href="{{ route('m.edit', [$module, $row->id]) }}">Editar</a>
                    <form method="post" action="{{ route('m.destroy', [$module, $row->id]) }}" class="inline" data-confirm="Excluir este registro?">
                        @csrf @method('DELETE') <button class="btn sm danger">Excluir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ count($cfg['columns']) + 1 }}" class="empty">Nenhum registro encontrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('module._pager')
@endsection
