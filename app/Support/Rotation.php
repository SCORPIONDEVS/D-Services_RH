<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\RotationAnchor;
use App\Models\ShiftPattern;
use Illuminate\Support\Carbon;

/**
 * Rodízio 12x36 por dias ímpares/pares.
 *
 * Regra (conferida contra os 44 colaboradores da planilha, março a dezembro de 2026): o colaborador trabalha dia sim,
 * dia não, sem reiniciar a cada mês. Por isso a paridade (ÍMPAR/PAR) troca depois de alguns meses de 31 dias.
 * A "âncora" é uma data trabalhada; vale a última âncora até o dia em questão, e a própria data da âncora conta
 * como trabalhada. O padrão do mês é "ÍMPAR 8/7": paridade do último dia trabalhado + dias trabalhados na 1ª e na
 * 2ª quinzena (é o que o cálculo de VT/VR lê).
 */
class Rotation
{
    /** @param Carbon[] $anchors datas trabalhadas, em ordem crescente */
    public static function worksOn(array $anchors, Carbon $day): bool
    {
        if (!$anchors) return false;
        $a = $anchors[0];
        foreach ($anchors as $x) {
            if ($x->lte($day)) $a = $x;
        }
        // dias inteiros e com sinal (a âncora pode ser posterior ao dia: a sequência vale para trás também)
        return (int) round($a->copy()->startOfDay()->diffInDays($day->copy()->startOfDay(), false)) % 2 === 0;
    }

    /** @return int[] dias do mês trabalhados */
    public static function workedDays(array $anchors, int $year, int $month, ?Carbon $admission = null): array
    {
        $out = [];
        $n = Carbon::create($year, $month, 1)->daysInMonth;
        for ($d = 1; $d <= $n; $d++) {
            $day = Carbon::create($year, $month, $d)->startOfDay();
            if ($admission && $day->lt($admission->copy()->startOfDay())) continue;
            if (self::worksOn($anchors, $day)) $out[] = $d;
        }
        return $out;
    }

    /** "ÍMPAR 8/7" para o mês, ou null se o colaborador não trabalha em nenhum dia dele. */
    public static function monthPattern(array $anchors, int $year, int $month, ?Carbon $admission = null): ?string
    {
        $days = self::workedDays($anchors, $year, $month, $admission);
        if (!$days) return null;

        $q1 = count(array_filter($days, fn ($d) => $d <= 15));
        return sprintf('%s %d/%d', end($days) % 2 === 1 ? 'ÍMPAR' : 'PAR', $q1, count($days) - $q1);
    }

    /** 'ÍMPAR' | 'PAR' | null a partir de um padrão como "ÍMPAR 8/7" ou "PAR". */
    public static function parity(?string $pattern): ?string
    {
        $p = mb_strtoupper(trim((string) $pattern));
        if (str_starts_with($p, 'ÍMPAR') || str_starts_with($p, 'IMPAR')) return 'ÍMPAR';
        if (str_starts_with($p, 'PAR')) return 'PAR';
        return null;
    }

    public static function is12x36(?string $scale): bool
    {
        return str_contains(strtoupper((string) $scale), '12X36');
    }

    /** @return Carbon[] */
    public static function anchorsOf(Employee $e): array
    {
        return RotationAnchor::where('employee_id', $e->id)->orWhere(fn ($q) => $q->whereNull('employee_id')->where('employee_name', $e->name))
            ->orderBy('work_date')->get()->pluck('work_date')->map->copy()->all();
    }

    /**
     * Importa as âncoras do cadastro que já existe: para cada colaborador ATIVO em 12X36 sem âncora, usa o padrão do
     * mês mais recente em shift_patterns (ÍMPAR → dia 1 do mês; PAR → dia 2). Não altera colaboradores que já têm âncora.
     *
     * @return array{criadas:int, ja_tinham:int, sem_padrao:int}
     */
    public static function importAnchors(bool $dryRun = false): array
    {
        $res = ['criadas' => 0, 'ja_tinham' => 0, 'sem_padrao' => 0];

        foreach (Employee::active()->orderBy('id')->get(['id', 'name', 'scale']) as $e) {
            if (!self::is12x36($e->scale)) continue;
            if (RotationAnchor::where('employee_id', $e->id)->exists()) { $res['ja_tinham']++; continue; }

            $latest = ShiftPattern::where(fn ($q) => $q->where('employee_id', $e->id)->orWhere('employee_name', $e->name))
                ->orderByDesc('month')->get()->first(fn ($p) => self::parity($p->pattern) !== null);
            if (!$latest) { $res['sem_padrao']++; continue; }

            $parity = self::parity($latest->pattern);
            $date = Carbon::createFromFormat('Y-m-d', $latest->month . '-' . ($parity === 'ÍMPAR' ? '01' : '02'))->startOfDay();
            if (!$dryRun) {
                RotationAnchor::create(['employee_id' => $e->id, 'employee_name' => $e->name, 'work_date' => $date,
                    'note' => "importado de {$latest->month} ({$latest->pattern})"]);
            }
            $res['criadas']++;
        }

        return $res;
    }

    /**
     * Gera o padrão do mês ("YYYY-MM") para os ATIVOS em 12X36 com âncora. Nunca sobrescreve: quem já tem padrão
     * lançado no mês é mantido como está.
     *
     * @return array{criados:int, ja_existiam:int, sem_ancora:int}
     */
    public static function generateMonth(string $month, bool $dryRun = false): array
    {
        [$y, $m] = array_map('intval', explode('-', $month));
        $res = ['criados' => 0, 'ja_existiam' => 0, 'sem_ancora' => 0];
        $monthEnd = Carbon::create($y, $m, 1)->endOfMonth();

        $existing = ShiftPattern::where('month', $month)->get(['employee_id', 'employee_name']);
        $haveId = $existing->pluck('employee_id')->filter()->flip();
        $haveName = $existing->pluck('employee_name')->map(fn ($n) => mb_strtoupper(trim($n)))->flip();

        foreach (Employee::active()->with('workShift')->orderBy('team')->orderBy('name')->get() as $e) {
            if (!self::is12x36($e->scale)) continue;
            if ($e->admission_date && $e->admission_date->gt($monthEnd)) continue;

            if ($haveId->has($e->id) || $haveName->has(mb_strtoupper(trim($e->name)))) { $res['ja_existiam']++; continue; }

            $anchors = self::anchorsOf($e);
            $pattern = $anchors ? self::monthPattern($anchors, $y, $m, $e->admission_date) : null;
            if (!$pattern) { $res['sem_ancora']++; continue; }

            if (!$dryRun) {
                ShiftPattern::create(['employee_id' => $e->id, 'employee_name' => $e->name, 'team' => $e->team,
                    'schedule' => $e->schedule_label ?? $e->work_schedule, 'month' => $month, 'pattern' => $pattern]);
            }
            $res['criados']++;
        }

        return $res;
    }
}
