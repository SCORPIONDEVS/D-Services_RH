<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'D\'SERVICE RH')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $m = fn ($k) => ["m/$k*", config("modules.$k.icon"), config("modules.$k.title"), route('m.index', $k)];
    $groups = [
        '' => [['/', '🏠', 'Painel', route('dashboard')]],
        'Pessoas' => [['employees*', '👥', 'Funcionários', route('employees.index')], $m('certificates'), ['guia-clinica*', '🏥', 'Guia de Clínica', route('clinic.index')], $m('lockers')],
        'Recrutamento' => [$m('job-openings'), $m('candidates')],
        'Ponto e folha' => [$m('occurrences'), $m('inconsistencies'), $m('payroll'), $m('attendance'), $m('leadership'), $m('coverages')],
        'Benefícios' => [['benefits*', '🚌', 'Benefícios VT/VR', route('benefits.index')]],
        'Escalas' => [['schedules', '🗓️', 'Grade do Terminal', route('schedules.index')], ['schedules/visao*', '🎨', 'Escala colorida', route('schedules.overview')], ['schedules/patterns*', '🔄', 'Padrão mensal 12x36', route('schedules.patterns')], $m('work-shifts'), $m('scales'), $m('team-scales'), $m('scale-work-shifts')],
        'Cadastros' => [$m('positions'), $m('calendar')],
    ];
@endphp
<div class="shell">
    <aside class="side" id="side">
        <div class="brand">D'SERVICE <small>RH &amp; DP</small></div>
        <nav>
            @foreach ($groups as $title => $items)
                @if ($title !== '')<div class="nav-h">{{ $title }}</div>@endif
                @foreach ($items as [$pattern, $icon, $label, $url])
                    <a href="{{ $url }}" class="{{ request()->is($pattern) ? 'on' : '' }}"><span>{{ $icon }}</span> {{ $label }}</a>
                @endforeach
            @endforeach
        </nav>
        <form method="post" action="{{ route('logout') }}" class="logout">@csrf
            <span>{{ auth()->user()->name }}</span>
            <button class="link">Sair</button>
        </form>
    </aside>
    <main class="main">
        <header class="top">
            <button class="burger" type="button" data-toggle="#side" aria-label="Menu">☰</button>
            <h1>@yield('heading')</h1>
            <div class="actions">@yield('actions')</div>
        </header>
        @if (session('ok')) <div class="flash ok">{{ session('ok') }}</div> @endif
        @if ($errors->any())
            <div class="flash bad">Corrija os campos destacados:
                <ul>@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
