@extends('layouts.app')
@section('title', $sheet->name)
@section('heading', '🗃️ ' . $sheet->name)
@section('actions')<a class="btn" href="{{ route('archive.index') }}">← Abas</a>@endsection

@section('content')
<form method="get" class="filters card">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nesta aba…" class="grow">
    <button class="btn">Buscar</button>
</form>
@php $cols = $sheet->columns ?? []; @endphp
<div class="card table-wrap">
    <table class="grid compact archive">
        <thead><tr><th>#</th>@foreach ($cols as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr><td class="muted">{{ $row->row_number }}</td>
                @foreach ($cols as $c)<td>{{ $row->cells[$c] ?? '' }}</td>@endforeach
            </tr>
        @empty <tr><td colspan="{{ count($cols) + 1 }}" class="empty">Nada encontrado.</td></tr> @endforelse
        </tbody>
    </table>
</div>
@include('module._pager')
@endsection
