@extends('layouts.app')
@section('title', 'Arquivo da planilha')
@section('heading', '🗃️ Arquivo da planilha')

@section('content')
<p class="muted">Abas da planilha original que não viraram módulo próprio (folha, padrões de escala, rascunhos e abas ocultas). Os valores foram preservados em grade, somente leitura.</p>
<div class="card table-wrap">
    <table class="grid">
        <thead><tr><th>Aba</th><th>Linhas</th><th>Colunas</th><th>Situação na planilha</th></tr></thead>
        <tbody>
        @foreach ($sheets as $s)
            <tr>
                <td><a href="{{ route('archive.show', $s->slug) }}"><b>{{ $s->name }}</b></a></td>
                <td>{{ $s->rows_count }}</td><td>{{ count($s->columns ?? []) }}</td>
                <td>{{ \App\Support\Fmt::cell($s->hidden ? 'oculta' : 'visível', 'badge') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
