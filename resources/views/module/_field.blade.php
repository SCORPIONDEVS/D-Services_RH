{{-- Renderiza um campo de formulário. Variáveis: $f (definição), $value, $relations, $employeeNames, $suggest, $prefix --}}
@php
    [$name, $label, $type] = $f;
    $id = ($prefix ?? '') . $name;
    $val = old($name, $value);
    if ($val instanceof \Carbon\CarbonInterface) $val = $val->format($type === 'date' ? 'Y-m-d' : 'Y-m-d');
    $req = !empty($f['required']) ? 'required' : '';
@endphp
<label class="field {{ $type === 'textarea' ? 'wide' : '' }}" for="{{ $id }}">
    <span>{{ $label }}@if($req) <b class="req">*</b>@endif</span>
    @switch($type)
        @case('textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3" {{ $req }}>{{ $val }}</textarea>
            @break
        @case('bool')
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $val)>
            @break
        @case('select')
            <select id="{{ $id }}" name="{{ $name }}" {{ $req }}>
                <option value=""></option>
                @foreach ($f['options'] as $opt)<option value="{{ $opt }}" @selected((string) $val === (string) $opt)>{{ $opt }}</option>@endforeach
                @if ($val !== null && $val !== '' && !in_array($val, $f['options'], true))<option value="{{ $val }}" selected>{{ $val }}</option>@endif
            </select>
            @break
        @case('relation')
            <select id="{{ $id }}" name="{{ $name }}" {{ $req }}>
                <option value=""></option>
                @foreach (($relations[$name] ?? []) as $k => $text)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $text }}</option>@endforeach
            </select>
            @break
        @case('employee')
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" list="dl-employees" autocomplete="off" {{ $req }}>
            @break
        @case('cid')
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" maxlength="100" data-cid-lookup="#cid_description" style="text-transform:uppercase" {{ $req }}>
            @break
        @case('money')
            <input type="number" step="0.01" min="0" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" {{ $req }}>
            @break
        @case('number')
            <input type="number" step="any" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" {{ $req }}>
            @break
        @case('date')
            <input type="date" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" {{ $req }}>
            @break
        @case('time')
            <input type="time" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" {{ $req }}>
            @break
        @case('email')
            <input type="email" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}" {{ $req }}>
            @break
        @default
            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $val }}"
                   @if(!empty($f['mask'])) data-mask="{{ $f['mask'] }}" @endif
                   @if(!empty($f['suggest'])) list="dl-{{ $f['suggest'] }}" @endif {{ $req }}>
    @endswitch
    @error($name)<small class="err">{{ $message }}</small>@enderror
</label>
