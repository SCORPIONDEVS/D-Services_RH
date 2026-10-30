<?php

namespace App\Http\Controllers;

use App\Models\PatternAssignment;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\ShiftPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Escalas de turno (abas "Escala 1º TURNO" e "Escala 2º TURNO"). */
class ScheduleController extends Controller
{
    public function index(Request $r)
    {
        $shift = in_array((int) $r->query('shift'), [1, 2]) ? (int) $r->query('shift') : 1;
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $r->query('month')) ? $r->query('month') : null;

        if (!$month) {
            $latest = ScheduleEntry::where('shift', $shift)->whereDate('date', '<=', now())->max('date')
                ?? ScheduleEntry::where('shift', $shift)->min('date');
            $month = $latest ? Carbon::parse($latest)->format('Y-m') : now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $entries = ScheduleEntry::where('shift', $shift)
            ->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')->orderBy('id')->get();

        $slots = $entries->pluck('slot')->unique()->values();
        $grid = $entries->groupBy(fn ($e) => $e->date->toDateString());

        $months = ScheduleEntry::where('shift', $shift)->orderBy('date')->pluck('date')
            ->map(fn ($d) => $d->format('Y-m'))->unique()->values();

        $fixed = ScheduleSlot::where('shift', $shift)->orderBy('id')->get();

        return view('schedules.index', compact('shift', 'month', 'start', 'slots', 'grid', 'months', 'fixed'));
    }

    public function update(Request $r, ScheduleEntry $entry)
    {
        $data = $r->validate(['person' => 'nullable|string|max:255', 'leader_rotation' => 'sometimes|boolean']);
        if (isset($data['person'])) $data['person'] = mb_strtoupper(trim($data['person'])) ?: null;
        $entry->update($data);

        return response()->json(['ok' => true, 'person' => $entry->person, 'leader_rotation' => $entry->leader_rotation]);
    }

    /** Visão colorida da escala mensal (estilo da planilha): horário por cor e ÍMPAR/PAR por mês. */
    public function overview(Request $r)
    {
        $allMonths = ShiftPattern::distinct()->orderBy('month')->pluck('month')->values();
        $from = $allMonths->contains($r->query('de')) ? $r->query('de') : ($allMonths->first(fn ($m) => $m >= now()->subMonth()->format('Y-m')) ?? $allMonths->first());
        $qtd = in_array((int) $r->query('qtd'), [3, 4, 6, 12], true) ? (int) $r->query('qtd') : 4;
        $months = $allMonths->filter(fn ($m) => $m >= $from)->take($qtd)->values();

        $status = in_array($r->query('status'), ['ativos', 'desligados', 'todos'], true) ? $r->query('status') : 'ativos';
        $emps = \App\Models\Employee::with('workShift')->get(['id', 'name', 'job_title', 'termination_date', 'scale', 'work_shift_id'])->keyBy(fn ($e) => mb_strtoupper(trim($e->name)));

        $rows = [];
        foreach (ShiftPattern::whereIn('month', $months)->orderBy('id')->get() as $p) {
            $key = mb_strtoupper(trim($p->employee_name)) . '|' . $p->team;
            $emp = $emps->get(mb_strtoupper(trim($p->employee_name)));
            $sched = $emp?->schedule_label ?? $p->schedule; // horário do vínculo; texto legado só se ainda não migrado
            $rows[$key] ??= ['name' => $p->employee_name, 'team' => $p->team, 'schedule' => $sched, 'cells' => [], 'emp' => $emp];
            $rows[$key]['cells'][$p->month] = $p->pattern;
            if ($sched) $rows[$key]['schedule'] = $sched;
        }
        $rows = collect($rows)->filter(function ($row) use ($status) {
            $inactive = $row['emp'] && $row['emp']->termination_date;
            return match ($status) { 'ativos' => $row['emp'] ? !$inactive : false, 'desligados' => $inactive, default => true };
        });
        if ($t = $r->query('team')) $rows = $rows->where('team', $t);
        if (($q = trim((string) $r->query('q'))) !== '') $rows = $rows->filter(fn ($x) => mb_stripos($x['name'], $q) !== false);

        return view('schedules.overview', [
            'rows' => $rows->values(), 'months' => $months, 'allMonths' => $allMonths, 'from' => $from, 'qtd' => $qtd, 'status' => $status,
            'teams' => ShiftPattern::whereNotNull('team')->distinct()->orderBy('team')->pluck('team'),
        ]);
    }

    /** Padrão mensal de escala 12x36 (PAR/ÍMPAR e dias por quinzena), por colaborador. */
    public function patterns(Request $r)
    {
        $months = ShiftPattern::distinct()->orderByDesc('month')->pluck('month');
        $month = $months->contains($r->query('month')) ? $r->query('month') : $months->first();

        $q = ShiftPattern::where('month', $month)->orderBy('team')->orderBy('employee_name');
        if (($s = trim((string) $r->query('q'))) !== '') $q->where('employee_name', 'like', "%{$s}%");

        // Situação: ativos (padrão), desligados ou todos
        $status = in_array($r->query('status'), ['ativos', 'desligados', 'todos'], true) ? $r->query('status') : 'ativos';
        if ($status === 'ativos') {
            $q->whereIn('employee_name', \App\Models\Employee::active()->pluck('name'));
        } elseif ($status === 'desligados') {
            $q->whereNotIn('employee_name', \App\Models\Employee::active()->pluck('name'));
        }

        $rows = $q->get();
        $emps = \App\Models\Employee::with('workShift')->get(['id', 'name', 'scale', 'work_shift_id'])->keyBy(fn ($e) => mb_strtoupper(trim($e->name)));
        foreach ($rows as $p) { // horário exibido vem do vínculo do funcionário; texto legado só como reserva
            $p->setAttribute('display_schedule', $emps->get(mb_strtoupper(trim($p->employee_name)))?->schedule_label ?? $p->schedule);
        }

        return view('schedules.patterns', [
            'status' => $status,
            'months' => $months, 'month' => $month, 'rows' => $rows,
            'assignments' => PatternAssignment::orderBy('id')->get(),
        ]);
    }

    /**
     * Preenche "Postos 12x36" automaticamente: colaboradores ATIVOS com padrão ÍMPAR/PAR no mês,
     * agrupados por cargo + horário; o 1º ímpar faz par com o 1º par, e assim por diante.
     */
    public function fillPostos(Request $r)
    {
        $months = ShiftPattern::distinct()->orderByDesc('month')->pluck('month');
        $cur = now()->format('Y-m');
        $month = $months->contains($r->input('month')) ? $r->input('month') : ($months->contains($cur) ? $cur : $months->first());

        $emps = \App\Models\Employee::active()->with('workShift')->get(['id', 'name', 'job_title', 'scale', 'work_shift_id'])
            ->keyBy(fn ($e) => mb_strtoupper(trim($e->name)));

        $groups = [];
        foreach (ShiftPattern::where('month', $month)->orderBy('employee_name')->get() as $p) {
            $pat = mb_strtoupper(trim($p->pattern));
            $side = str_starts_with($pat, 'ÍMPAR') || str_starts_with($pat, 'IMPAR') ? 'odd' : (str_starts_with($pat, 'PAR') ? 'even' : null);
            $emp = $emps->get(mb_strtoupper(trim($p->employee_name)));
            if (!$side || !$emp) continue; // só 12x36 e só ativos

            $hours = trim(preg_replace('/\s*-?\s*Jornada.*$/iu', '', (string) ($emp->schedule_label ?? $p->schedule)));
            $label = trim(($emp->job_title ?: 'SEM CARGO') . ($hours !== '' ? ' · ' . $hours : ''));
            $groups[$label][$side][] = $p->employee_name;
        }
        ksort($groups);

        \Illuminate\Support\Facades\DB::transaction(function () use ($groups) {
            PatternAssignment::query()->delete();
            foreach ($groups as $label => $g) {
                $odd = $g['odd'] ?? [];
                $even = $g['even'] ?? [];
                for ($i = 0, $n = max(count($odd), count($even)); $i < $n; $i++) {
                    PatternAssignment::create(['position' => $label, 'odd_person' => $odd[$i] ?? null, 'even_person' => $even[$i] ?? null]);
                }
            }
        });

        return back()->with('ok', 'Postos 12x36 preenchidos automaticamente com os ativos de ' . $month . ' (' . PatternAssignment::count() . ' linhas).');
    }

    public function updatePattern(Request $r, ShiftPattern $pattern)
    {
        $data = $r->validate(['pattern' => 'required|string|max:40']);
        $pattern->update($data);

        return response()->json(['ok' => true, 'pattern' => $pattern->pattern]);
    }
}
