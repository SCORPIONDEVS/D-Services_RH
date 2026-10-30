{{-- Cascata Equipe -> Escala -> Jornada. Variáveis: $f (campo team), $e (funcionário), $cascade (mapa de ScheduleLinks::cascadeMap) --}}
@php
    $teamVal = old('team', $e->team);
    $scaleVal = old('scale', $e->scale);
    $shiftVal = old('work_shift_id', $e->work_shift_id);
    $shiftNow = $shiftVal ? \App\Models\WorkShift::find($shiftVal) : null;
@endphp

<label class="field" for="team">
    <span>Equipe</span>
    <select id="team" name="team">
        <option value=""></option>
        @foreach ($f['options'] as $opt)<option value="{{ $opt }}" @selected((string) $teamVal === (string) $opt)>{{ $opt }}</option>@endforeach
        @if ($teamVal && !in_array($teamVal, $f['options'], true))<option value="{{ $teamVal }}" selected>{{ $teamVal }}</option>@endif
    </select>
    @error('team')<small class="err">{{ $message }}</small>@enderror
</label>

<label class="field" for="scale">
    <span>Escala</span>
    {{-- as opções reais são montadas pelo script a partir da Equipe; aqui vai só o valor atual (funciona sem JS) --}}
    <select id="scale" name="scale">
        <option value=""></option>
        @if ($scaleVal)<option value="{{ $scaleVal }}" selected>{{ $scaleVal }}</option>@endif
    </select>
    @error('scale')<small class="err">{{ $message }}</small>@enderror
</label>

<label class="field" for="work_shift_id">
    <span>Jornada</span>
    <select id="work_shift_id" name="work_shift_id">
        <option value=""></option>
        @if ($shiftNow)<option value="{{ $shiftNow->id }}" selected>{{ $shiftNow->name }}</option>@endif
    </select>
    @error('work_shift_id')<small class="err">{{ $message }}</small>@enderror
</label>

<label class="field" for="shift_time">
    <span>Horário (preenchido pela jornada)</span>
    <input type="text" id="shift_time" readonly tabindex="-1"
           value="{{ $e->exists && !$shiftNow ? $e->work_schedule : '' }}">
    @if ($e->exists && $e->schedule_review)<small class="err">Pendente de revisão: escolha a jornada para regularizar.</small>@endif
</label>

<script>
(function () {
    var map = @json($cascade);
    var team = document.getElementById('team'), scale = document.getElementById('scale'),
        shift = document.getElementById('work_shift_id'), time = document.getElementById('shift_time');
    var legacy = @json($e->exists && !$shiftNow ? $e->work_schedule : '');

    function opts(sel, items, keep) {
        sel.innerHTML = '';
        sel.appendChild(new Option('', ''));
        var found = false;
        items.forEach(function (i) {
            var o = new Option(i.text, i.value);
            if (String(i.value) === String(keep)) { o.selected = true; found = true; }
            sel.appendChild(o);
        });
        // valor antigo fora do vínculo continua visível; o servidor decide se aceita (só se não mudou)
        if (keep && !found) { var o = new Option(keep + ' (fora do vínculo)', keep); o.selected = true; sel.appendChild(o); }
    }
    function scalesOf(t) { return (map.teams[t] || []).map(function (n) { return {value: n, text: n}; }); }
    function shiftsOf(s, t) {
        return (map.shifts[s] || []).filter(function (x) { return !x.team || x.team === t; })
            .map(function (x) { return {value: x.id, text: x.name + ' (' + x.entry + '–' + x.exit + ')', label: x.label}; });
    }
    function showTime() {
        var cur = shiftsOf(scale.value, team.value).filter(function (x) { return String(x.value) === shift.value; })[0];
        time.value = cur ? cur.label : (shift.value ? '' : legacy);
    }
    function loadScales(keep) { opts(scale, scalesOf(team.value), keep); }
    function loadShifts(keep) {
        var list = shiftsOf(scale.value, team.value);
        opts(shift, list, keep);
        // valor atual sem vínculo vira texto simples, sem inventar o id
        showTime();
    }

    // estado inicial (preserva o que veio do servidor)
    var initShift = shift.value;
    loadScales(scale.value);
    loadShifts(initShift);

    team.addEventListener('change', function () {      // trocar a Equipe limpa Escala e Jornada
        loadScales('');
        loadShifts('');
    });
    scale.addEventListener('change', function () {     // trocar a Escala limpa a Jornada
        loadShifts('');
    });
    shift.addEventListener('change', showTime);        // escolher a Jornada preenche o horário
})();
</script>
