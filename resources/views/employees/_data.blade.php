{{-- Exibição somente leitura dos campos do funcionário, agrupados por seção --}}
@foreach ($sections as $title => $fields)
    <section class="card">
        <h3>{{ $title }}</h3>
        <dl class="kv">
            @foreach ($fields as $f)
                @php
                    $v = $e->{$f[0]};
                    $txt = match ($f[2]) {
                        'date' => \App\Support\Fmt::date($v),
                        'money' => \App\Support\Fmt::money($v),
                        'bool' => $v ? 'Sim' : 'Não',
                        default => $v,
                    };
                @endphp
                @continue($txt === null || $txt === '')
                <div><dt>{{ $f[1] }}</dt><dd>{{ $txt }}</dd></div>
            @endforeach
        </dl>
    </section>
@endforeach
