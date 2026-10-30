<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\Position;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importa os JSON gerados por tools/export_xlsm.py (dados da planilha original).
 * Idempotente: se já houver funcionários, não faz nada.  Para reimportar: php artisan migrate:fresh --seed
 */
class SpreadsheetSeeder extends Seeder
{
    private string $dir;

    public function run(): void
    {
        $this->dir = database_path('seed-data');

        if (!is_file("{$this->dir}/employees.json")) {
            $this->command?->warn('Dados de RH não incluídos no repositório; importação da planilha ignorada.');
            return;
        }

        if (Employee::count() > 0) {
            $this->command?->warn('Já existem funcionários — importação ignorada.');
            return;
        }

        DB::transaction(function () {
            $this->settingsAndBase();
            $employeeIds = $this->employees();
            $this->recruitment();
            $this->byEmployeeName('certificates', 'medical_certificates', $employeeIds, 'employee_name');
            $this->byEmployeeName('time_occurrences', 'time_occurrences', $employeeIds, 'employee_name');
            $this->byEmployeeName('attendance_bonuses', 'attendance_bonuses', $employeeIds, 'employee_name');
            $this->bulk('coverages', 'coverages');
            $this->bulk('lockers', 'lockers');
            $this->bulk('calendar_tasks', 'calendar_tasks');
            $this->bulk('schedule_entries', 'schedule_entries');
            $this->bulk('cids', 'cids', timestamps: false, unique: 'code');
        });

        $this->command?->info('Planilha importada com sucesso.');
    }

    private function load(string $name): array
    {
        $path = "{$this->dir}/{$name}.json";
        if (!is_file($path)) {
            throw new \RuntimeException("Arquivo de dados não encontrado: {$path}. Copie a pasta database/seed-data do pacote para o projeto.");
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function bulk(string $file, string $table, bool $timestamps = true, ?callable $map = null, ?string $unique = null): void
    {
        $now = now();
        $rows = [];
        $seen = [];
        foreach ($this->load($file) as $row) {
            if ($map) $row = $map($row);
            if ($unique) {
                if (isset($seen[$row[$unique]])) continue;
                $seen[$row[$unique]] = true;
            }
            if ($timestamps) $row += ['created_at' => $now, 'updated_at' => $now];
            $rows[] = $row;
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function settingsAndBase(): void
    {
        $short = ['D SERVICE SERVICOS', 'D SERVICE LIMPEZA', null];
        foreach ($this->load('companies') as $i => $c) {
            Company::create($c + ['short_name' => $short[$i] ?? null]);
        }

        $settings = $this->load('settings');
        $quota = collect($settings['staff_quota'] ?? [])->keyBy('job_title');
        foreach ($this->load('positions') as $p) {
            $q = $quota->get($p['name']);
            Position::create($p + [
                'staff_quota' => $q ? (int) $q['quota'] : null,
                'quota_note' => $q['note'] ?? null,
            ]);
        }
        // cargos que só existem no quadro previsto
        foreach ($quota as $title => $q) {
            Position::firstOrCreate(['name' => $title], ['staff_quota' => (int) $q['quota'], 'quota_note' => $q['note']]);
        }

        foreach (['va_monthly_value', 'assiduity_salary_cap', 'vt_discount_percent', 'assiduity_rules'] as $key) {
            if (isset($settings[$key])) Setting::put($key, $settings[$key]);
        }
    }

    /** @return array<string,int> nome => id */
    private function employees(): array
    {
        $ids = [];
        foreach ($this->load('employees') as $row) {
            $deps = $row['dependents'] ?? [];
            unset($row['dependents']);

            $e = Employee::create($row);
            foreach ($deps as $d) $e->dependents()->create($d);

            $ids[mb_strtoupper(trim($e->name))] = $e->id;
        }
        return $ids;
    }

    private function recruitment(): void
    {
        $byNumber = [];
        foreach ($this->load('job_openings') as $v) {
            $byNumber[$v['number']] = JobOpening::create($v)->id;
        }

        $this->bulk('candidates', 'candidates', map: function ($c) use ($byNumber) {
            $c['job_opening_id'] = $byNumber[$c['job_number'] ?? 0] ?? null;
            unset($c['job_number']);
            return $c;
        });
    }

    private function byEmployeeName(string $file, string $table, array $ids, string $nameKey): void
    {
        $this->bulk($file, $table, map: function ($r) use ($ids, $nameKey) {
            $r['employee_id'] = $ids[mb_strtoupper(trim($r[$nameKey]))] ?? null;
            return $r;
        });
    }
}
