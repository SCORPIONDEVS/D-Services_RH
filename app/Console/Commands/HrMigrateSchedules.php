<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Scale;
use App\Models\ScaleWorkShift;
use App\Models\WorkShift;
use App\Support\ScheduleLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Converte o texto livre de employees.work_schedule em work_shift_id (jornada do cadastro) quando há
 * correspondência exata de horário + tipo. Não altera team/scale/work_schedule nem apaga nada: quem não casa,
 * ou tem combinação inválida, só recebe schedule_review = true e entra no relatório.
 */
class HrMigrateSchedules extends Command
{
    protected $signature = 'hr:migrate-schedules {--dry-run : só gera o relatório, sem gravar no banco}';
    protected $description = 'Vincula os funcionários às jornadas do cadastro e gera o relatório dos pendentes de revisão';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (!$dry) $this->call('hr:backup-schedules'); // backup antes de gravar qualquer coisa

        $shifts = WorkShift::all();
        $scaleIds = Scale::pluck('id', 'name');
        $links = ScaleWorkShift::all()->groupBy('scale_id');

        $linked = 0;
        $pending = [];

        foreach (Employee::whereNull('work_shift_id')->orderBy('id')->get() as $e) {
            $text = trim((string) $e->work_schedule);
            if ($text === '' && !$e->scale) continue; // nada a migrar

            $reason = $this->diagnose($e, $text, $shifts, $scaleIds, $links, $found);

            if ($reason === null) {
                $linked++;
                if (!$dry) DB::table('employees')->where('id', $e->id)->update(['work_shift_id' => $found, 'schedule_review' => false]);
            } else {
                $pending[] = [$e->id, $e->name, $e->termination_date ? 'INATIVO' : 'ATIVO', $e->team, $e->scale, $text, $reason];
                if (!$dry) DB::table('employees')->where('id', $e->id)->update(['schedule_review' => true]);
            }
        }

        $report = $this->report($pending);
        $this->table(['Resultado', 'Qtde'], [['Vinculados' . ($dry ? ' (simulação)' : ''), $linked], ['Pendentes de revisão', count($pending)]]);
        $this->info("Relatório dos pendentes: $report");

        return self::SUCCESS;
    }

    /** null = casou com exatamente uma jornada ($found recebe o id); senão, o motivo do pendente. */
    private function diagnose(Employee $e, string $text, $shifts, $scaleIds, $links, &$found): ?string
    {
        $found = null;
        $scaleId = $scaleIds[$e->scale] ?? null;

        if (!$e->team) return 'Sem equipe';
        if (!$e->scale) return 'Sem escala';
        if (!$scaleId) return "Escala {$e->scale} não cadastrada";
        if (!in_array($e->scale, ScheduleLinks::scalesFor($e->team), true)) return "Escala {$e->scale} não vinculada à equipe {$e->team}";

        $time = ScheduleLinks::parseLegacy($text);
        if (!$time) return 'Texto da jornada sem horário reconhecível';

        // "tipo": o texto precisa dizer a mesma jornada da escala (ex.: "Jornada 12 x 36" x escala 12X36)
        if (!preg_match('/Jornada\s*(\d+)\s*x\s*(\d+)/iu', $text, $m)) return 'Texto da jornada sem o tipo (ex.: Jornada 12 x 36)';
        if (strtoupper("{$m[1]}X{$m[2]}") !== strtoupper(preg_replace('/^(\d+X\d+).*$/i', '$1', $e->scale))) return "Tipo do texto (Jornada {$m[1]} x {$m[2]}) difere da escala {$e->scale}";

        $ok = [];
        foreach ($links->get($scaleId, collect()) as $l) {
            if ($l->team !== null && $l->team !== $e->team) continue;
            $w = $shifts->firstWhere('id', $l->work_shift_id);
            if ($w && $w->entry_time === $time[0] && $w->exit_time === $time[1]) $ok[$w->id] = true;
        }

        if (count($ok) === 1) { $found = array_key_first($ok); return null; }
        return count($ok) === 0 ? "Nenhuma jornada cadastrada para {$time[0]} às {$time[1]} na escala {$e->scale}" : 'Mais de uma jornada com o mesmo horário';
    }

    private function report(array $rows): string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $path = $dir . '/schedules-pending-' . now()->format('Ymd-His') . '.csv';
        $out = fopen($path, 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['id', 'nome', 'status', 'equipe', 'escala', 'jornada_texto', 'motivo'], ';');
        foreach ($rows as $r) fputcsv($out, $r, ';');
        fclose($out);
        return $path;
    }
}
