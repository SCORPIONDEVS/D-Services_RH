<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Demonstrativo de benefícios — {{ $e->name }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body class="print">

<h2>DEMONSTRATIVO DE BENEFÍCIOS</h2>
<p><b>{{ $e->companyRef?->name ?? $e->company }}</b> @if($e->cnpj) · CNPJ {{ $e->cnpj }} @endif<br>
Competência: {{ \App\Models\BenefitEntry::label($entry->period) }}</p>
<table class="grid">
    <tr><th>Colaborador</th><td>{{ $e->name }}</td><th>CPF</th><td>{{ $e->cpf }}</td></tr>
    <tr><th>Cargo</th><td>{{ $e->job_title }}</td><th>Admissão</th><td>{{ \App\Support\Fmt::date($e->admission_date) }}</td></tr>
</table>
<table class="grid" style="margin-top:12px">
    <thead><tr><th>Benefício</th><th>Dias</th><th>Valor/dia</th><th>Desconto</th><th>Valor</th></tr></thead>
    <tbody>
        <tr><td>Vale-transporte</td><td>{{ $entry->vt_days + 0 }}</td><td>{{ \App\Support\Fmt::money($entry->vt_rate) }}</td><td>—</td><td>{{ \App\Support\Fmt::money($entry->vt_value) }}</td></tr>
        <tr><td>Vale-refeição</td><td>{{ $entry->vr_days + 0 }}</td><td>{{ \App\Support\Fmt::money($entry->vr_rate) }}</td><td>—</td><td>{{ \App\Support\Fmt::money($entry->vr_value) }}</td></tr>
        @if ($entry->correction_vt != 0 || $entry->correction_vr != 0)<tr><td>Correção {{ $entry->correction_reason }}</td><td></td><td></td><td></td><td>{{ \App\Support\Fmt::money($entry->correction_vt + $entry->correction_vr) }}</td></tr>@endif
        @if ($entry->adjustment_vt != 0 || $entry->adjustment_vr != 0)<tr><td>Ajuste VT/VR {{ $entry->reason }}</td><td></td><td></td><td></td><td>{{ \App\Support\Fmt::money($entry->adjustment_vt + $entry->adjustment_vr) }}</td></tr>@endif
        @if ($entry->adjustment_plus > 0)<tr><td>Ajuste (+) {{ $entry->reason }}</td><td></td><td></td><td></td><td>{{ \App\Support\Fmt::money($entry->adjustment_plus) }}</td></tr>@endif
        @if ($entry->adjustment_minus > 0)<tr><td>Ajuste (−) {{ $entry->reason }}</td><td></td><td></td><td></td><td>− {{ \App\Support\Fmt::money($entry->adjustment_minus) }}</td></tr>@endif
    </tbody>
    <tfoot><tr><th colspan="4" style="text-align:right">Total creditado</th><th>{{ \App\Support\Fmt::money($entry->total) }}</th></tr></tfoot>
</table>
<p class="muted">Desconto de VR (coparticipação) de {{ \App\Support\Fmt::money($e->vr_discount_value) }} por dia e {{ \App\Models\Setting::get('vt_discount_percent', 6) }}% do salário em VT são aplicados em folha.</p>
<script>window.print()</script>
</body></html>
