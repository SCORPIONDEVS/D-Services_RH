<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HrStatus extends Command
{
    protected $signature = 'hr:status';
    protected $description = 'Mostra quantos registros existem em cada tabela importada da planilha';

    public function handle(): int
    {
        $expected = [
            'employees' => 93, 'employee_dependents' => null, 'positions' => null, 'companies' => 3, 'job_openings' => 83, 'candidates' => 392,
            'medical_certificates' => 365, 'cids' => 12142, 'time_occurrences' => 886, 'coverages' => 12, 'attendance_bonuses' => 891,
            'lockers' => 83, 'calendar_tasks' => 29, 'schedule_entries' => 3424, 'benefit_entries' => 767, 'payroll_entries' => 49,
            'leadership_bonuses' => 30, 'inconsistencies' => 126, 'shift_patterns' => 470, 'pattern_assignments' => 16,
            'schedule_slots' => 16, 'work_shifts' => 10,
        ];
        $rows = [];
        foreach ($expected as $table => $exp) {
            $n = DB::table($table)->count();
            $rows[] = [$table, $n, $exp ?? '—', $exp === null ? '' : ($n === $exp ? 'OK' : 'DIFERENTE')];
        }
        $this->table(['Tabela', 'No banco', 'Esperado', ''], $rows);
        return self::SUCCESS;
    }
}
