<?php

namespace App\Http\Controllers;

use App\Models\BenefitEntry;
use App\Models\Employee;
use App\Models\ShiftPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Benefícios VT/VR por período (quinzenas "YYYY-MM-1Q"/"2Q" e mês "YYYY-MM-M").
 * Inclui todo o histórico da planilha (valores originais fixados) e permite gerar novos períodos.
 * VT = dias × (tarifa × passagens/dia) · VR = dias × VR/dia do cargo.
 */
class BenefitController extends Controller
{
    private const PERIOD_RE = '/^\d{4}-\d{2}-(1Q|2Q|M)$/';

    private function period(Request $r): string
    {
        $p = (string) $r->query('period');
        if (preg_match(self::PERIOD_RE, $p)) return $p;

        // padrão: o período mais recente que já tem lançamentos; senão, a quinzena de hoje
        $latest = BenefitEntry::where('period', '<=', now()->format('Y-m') . '-2Q')->max('period');
        return $latest ?: now()->format('Y-m') . (now()->day <= 15 ? '-1Q' : '-2Q');
    }

    /** Períodos com lançamentos + quinzenas próximas, do mais novo ao mais antigo. */
    private function periodOptions(string $current): array
    {
        $periods = BenefitEntry::distinct()->pluck('period')->all();
        for ($i = -1; $i <= 2; $i++) {
            $m = now()->startOfMonth()->addMonths($i)->format('Y-m');
            array_push($periods, "$m-1Q", "$m-2Q");
        }
        $periods[] = $current;
        $periods = array_unique($periods);
        rsort($periods);

        return collect($periods)->mapWithKeys(fn ($p) => [$p => BenefitEntry::label($p)])->all();
    }

    public function index(Request $r)
    {
        $period = $this->period($r);
        $entries = BenefitEntry::with('employee')->where('period', $period)->get()
            ->sortBy(fn ($e) => $e->employee->team . $e->employee->name)->values();

        return view('benefits.index', [
            'period' => $period, 'entries' => $entries, 'options' => $this->periodOptions($period),
            'totals' => ['vt' => $entries->sum('vt_value'), 'vr' => $entries->sum('vr_value'), 'total' => $entries->sum('total')],
        ]);
    }

    public function generate(Request $r)
    {
        $period = $r->validate(['period' => ['required', 'regex:' . self::PERIOD_RE]])['period'];
        [$y, $m, $q] = explode('-', $period);
        $first = Carbon::create((int) $y, (int) $m, 1);
        $start = $q === '2Q' ? $first->copy()->day(16) : $first->copy();
        $end = $q === '1Q' ? $first->copy()->day(15) : $first->copy()->endOfMonth()->startOfDay();

        $created = 0;
        $employees = Employee::active()->where(fn ($w) => $w->where('vt_benefit', true)->orWhere('vr_benefit', true))->get();
        $patterns = ShiftPattern::where('month', "$y-$m")->pluck('pattern', 'employee_id');

        foreach ($employees as $e) {
            if ($e->admission_date && $e->admission_date->gt($end)) continue;
            $from = ($e->admission_date && $e->admission_date->gt($start)) ? $e->admission_date : $start;
            $days = $this->defaultDays($e, $from, $start, $end, $q, $patterns[$e->id] ?? null);

            $entry = BenefitEntry::firstOrCreate(
                ['employee_id' => $e->id, 'period' => $period],
                ['vt_days' => $e->vt_benefit ? $days : 0, 'vr_days' => $e->vr_benefit ? $days : 0]
            );
            if ($entry->wasRecentlyCreated) $created++;
        }

        return redirect()->route('benefits.index', ['period' => $period])
            ->with('ok', "$created lançamentos gerados. Ajuste os dias de cada colaborador na tabela.");
    }

    /**
     * Dias iniciais da quinzena. Com padrão mensal cadastrado (ex.: "ÍMPAR 8/7" = 8 dias na 1ª e 7 na 2ª quinzena)
     * usa-o; caso contrário estima pela escala. Sempre editável depois.
     */
    private function defaultDays(Employee $e, Carbon $from, Carbon $start, Carbon $end, string $q, ?string $pattern): int
    {
        $fullPeriod = $from->equalTo($start);

        if ($pattern && $fullPeriod && preg_match('/(\d+)\s*\/\s*(\d+)/', $pattern, $m)) {
            return (int) ($q === '1Q' ? $m[1] : $m[2]);
        }

        $n = $from->diffInDays($end) + 1;
        $scale = strtoupper((string) $e->scale);

        if (str_contains($scale, '12X36')) return (int) ceil($n / 2);
        if (str_contains($scale, '4X2')) return (int) round($n * 4 / 6);

        $days = 0;
        for ($d = $from->copy(); $d->lte($end); $d->addDay()) {
            if ($d->dayOfWeekIso <= 5 || (str_contains($scale, '6X1') && $d->dayOfWeekIso === 6)) $days++;
        }
        return $days;
    }

    public function update(Request $r, BenefitEntry $entry)
    {
        $data = $r->validate([
            'vt_days' => 'sometimes|numeric|min:0|max:31', 'vr_days' => 'sometimes|numeric|min:0|max:31',
            'adjustment_plus' => 'sometimes|numeric|min:0', 'adjustment_minus' => 'sometimes|numeric|min:0',
            'reason' => 'sometimes|nullable|string|max:255',
        ]);
        // mudar os dias volta a calcular o valor com o preço/dia atual
        if (array_key_exists('vt_days', $data)) $data['vt_amount'] = null;
        if (array_key_exists('vr_days', $data)) $data['vr_amount'] = null;

        $entry->update($data);
        $entry->load('employee');
        $all = BenefitEntry::with('employee')->where('period', $entry->period)->get();

        return response()->json([
            'vt' => $entry->vt_value, 'vr' => $entry->vr_value, 'total' => $entry->total,
            'totals' => ['vt' => $all->sum('vt_value'), 'vr' => $all->sum('vr_value'), 'total' => $all->sum('total')],
        ]);
    }

    public function destroy(BenefitEntry $entry)
    {
        $period = $entry->period;
        $entry->delete();
        return redirect()->route('benefits.index', ['period' => $period]);
    }

    public function receipt(BenefitEntry $entry)
    {
        $entry->load('employee.companyRef');
        return view('benefits.receipt', ['entry' => $entry, 'e' => $entry->employee]);
    }

    public function export(Request $r)
    {
        $period = $this->period($r);
        $entries = BenefitEntry::with('employee')->where('period', $period)->get()
            ->sortBy(fn ($e) => $e->employee->team . $e->employee->name);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'Equipe', 'CPF', 'Dias VT', 'VT/dia', 'VT', 'Dias VR', 'VR/dia', 'VR', 'Ajuste +', 'Ajuste -', 'Ajuste VT', 'Ajuste VR', 'Correção VT', 'Correção VR', 'Motivo', 'Total'], ';');
            foreach ($entries as $x) {
                fputcsv($out, [
                    $x->employee->name, $x->employee->team, $x->employee->cpf, $x->vt_days, $x->vt_rate, $x->vt_value,
                    $x->vr_days, $x->vr_rate, $x->vr_value, $x->adjustment_plus, $x->adjustment_minus, $x->adjustment_vt,
                    $x->adjustment_vr, $x->correction_vt, $x->correction_vr, trim($x->reason . ' ' . $x->correction_reason), $x->total,
                ], ';');
            }
            fclose($out);
        }, "beneficios-{$period}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
