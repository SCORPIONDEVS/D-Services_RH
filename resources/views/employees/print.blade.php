<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Ficha — {{ $e->name }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="print">
<h2>FICHA CADASTRAL — {{ $e->name }}</h2>
<p class="muted">{{ $e->companyRef?->name ?? $e->company }} @if($e->cnpj) · CNPJ {{ $e->cnpj }} @endif · Status: {{ $e->status }}</p>
@include('employees._data')
@if ($e->dependents->count())
<section class="card"><h3>Dependentes</h3>
@foreach ($e->dependents as $d)<div>{{ $d->name }} — {{ $d->relationship }} — {{ \App\Support\Fmt::date($d->birth_date) }} — {{ $d->cpf }}</div>@endforeach
</section>
@endif
<script>window.print()</script>
</body></html>
