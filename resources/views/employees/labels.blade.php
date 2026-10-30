<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Etiquetas de prontuário</title>
<style>
 body{font-family:Arial,sans-serif;margin:12px} .bar{margin-bottom:12px}
 .sheet{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
 .lbl{border:1px dashed #888;padding:14px 8px;text-align:center;font-weight:700;font-size:13px;min-height:54px;display:flex;align-items:center;justify-content:center}
 @media print{.bar{display:none}}
</style></head>
<body>
<div class="bar">{{ $employees->count() }} etiqueta(s) · <button onclick="window.print()">🖨 Imprimir</button>
 <small>(filtros: ?status=ativo|inativo|todos &amp;team=&amp;admitted_from=AAAA-MM-DD)</small></div>
<div class="sheet">@foreach ($employees as $e)<div class="lbl">{{ $e->name }}</div>@endforeach</div>
</body></html>
