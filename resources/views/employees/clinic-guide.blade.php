<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Guia clínica — {{ $e->name }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<style>.cb{font-family:monospace}.sel{margin:0 0 14px}.form td,.form th{padding:5px 8px;border:1px solid #bbb;text-align:left}.form{border-collapse:collapse;width:100%}</style></head>
<body class="print">
<form method="get" class="sel no-print">Tipo de exame:
    <select name="tipo" onchange="this.form.submit()">@foreach ($types as $k => $label)<option value="{{ $k }}" @selected($k === $type)>{{ $label }}</option>@endforeach</select>
    <button type="button" class="btn" onclick="window.print()">🖨 Imprimir</button>
</form>
<h2 style="text-align:center">FORMULÁRIO DE ENCAMINHAMENTO</h2>
<table class="form">
    <tr><th>Empresa</th><td>{{ $clinic['company'] ?? $e->companyRef?->name ?? $e->company }}</td><th>Responsável</th><td>{{ $clinic['responsible'] ?? '' }}</td></tr>
    <tr><th>CNPJ</th><td>{{ $e->cnpj ?? ($clinic['cnpj'] ?? '') }}</td><th>Contato</th><td>{{ $clinic['contact'] ?? '' }} · {{ $clinic['email'] ?? '' }}</td></tr>
</table>
<br>
<table class="form">
    <tr><th>Nome do funcionário</th><td colspan="3">{{ $e->name }}</td><th>RG</th><td>{{ $e->rg }}</td></tr>
    <tr><th>Setor</th><td>{{ $e->sector ?: $e->team }}</td><th>Cargo</th><td>{{ $e->job_title }}</td><th>CBO</th><td>{{ $e->cbo }}</td></tr>
    <tr><th>Nascimento</th><td>{{ \App\Support\Fmt::date($e->birth_date) }}</td><th>Admissão</th><td>{{ \App\Support\Fmt::date($e->admission_date) }}</td><th>CPF</th><td>{{ $e->cpf }}</td></tr>
</table>
<p>A empresa utiliza o sistema SOC: <span class="cb">SIM (x)   NÃO ( )</span></p>
<p><small>Obs.: Assim que registrar informar a Matrícula do e-Social e Data de Admissão no e-mail: {{ $clinic['clinic_email'] ?? 'suporte@stiloseg.com.br' }}</small></p>
<h4>Ao submeter-se ao Exame Médico Ocupacional para:</h4>
<p class="cb">
@foreach ($types as $k => $label){{ $k === $type ? '( X )' : '(   )' }} {{ $label }}&nbsp;&nbsp;&nbsp;@endforeach
</p>
<p>Outros exames complementares: ______________________________</p>
<h4>PAGAMENTO</h4>
<p class="cb">Dinheiro (   ) &nbsp; Cartão (   ) &nbsp; ( X ) Faturamento direto com a {{ $clinic['clinic_name'] ?? 'Stilo' }} (apenas se houver contrato)</p>
<p><small>A partir da liberação dos exames complementares, nossa Recepção encaminhará por e-mail o ASO para a Empresa Responsável e assim fica a responsabilidade da empresa repassar o ASO ao funcionário. Se for somente o Exame Clínico, será liberado no mesmo dia do atendimento via e-mail para a empresa e físico ao Funcionário.</small></p>
<br><br>
<p>Data: {{ now()->format('d/m/Y') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ______________________________ Assinatura do Responsável</p>
<style>@media print{.no-print{display:none}}</style>
</body></html>
