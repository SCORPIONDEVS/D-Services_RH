<?php

namespace Database\Seeders;

use App\Models\BenefitEntry;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Importa o restante da planilha: histórico de benefícios, apontamento de folha, liderança do Terminal,
 * inconsistências de ponto, padrões de escala, posições fixas, jornadas e mensagens dos candidatos.
 * Idempotente. Pode rodar sozinho:  php artisan db:seed --class=ExtraSheetsSeeder
 */
class ExtraSheetsSeeder extends Seeder
{
    private string $dir;
    private array $ids = [];

    public function run(): void
    {
        $this->dir = database_path('seed-data');

        // se o cadastro base ainda não foi importado (ex.: importação anterior interrompida), importa primeiro
        if (Employee::count() === 0) $this->call(SpreadsheetSeeder::class);

        $this->ids = Employee::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtoupper(trim($n)) => $id])->all();

        DB::transaction(function () {
            $this->benefitEntries();
            $this->candidateMessages();
            $this->load('payroll_entries', 'payroll_entries', byName: true);
            $this->load('leadership_bonuses', 'leadership_bonuses', byName: true);
            $this->load('inconsistencies', 'inconsistencies', byName: true);
            $this->load('shift_patterns', 'shift_patterns', byName: true);
            $this->load('pattern_assignments', 'pattern_assignments');
            $this->load('schedule_slots', 'schedule_slots');
            $this->load('work_shifts', 'work_shifts');

            if (!Setting::find('clinic')) {
                Setting::put('clinic', [
                    'company' => "D'SERVICE SERVICOS GERAIS LTDA", 'cnpj' => '60.865.697/0001-95',
                    'responsible' => 'EMERSON', 'contact' => 'DANNIÉLE', 'email' => 'dservicerh@hotmail.com',
                    'clinic_name' => 'Stilo', 'clinic_email' => 'suporte@stiloseg.com.br',
                ]);
            }
        });

        $this->command?->info('Histórico e demais abas importados.');
    }

    private function json(string $name): array
    {
        $path = "{$this->dir}/{$name}.json";
        return is_file($path) ? json_decode(file_get_contents($path), true) : [];
    }

    private function load(string $file, string $table, bool $byName = false): void
    {
        if (DB::table($table)->exists()) return;

        $now = now();
        $rows = [];
        foreach ($this->json($file) as $r) {
            if ($byName) $r['employee_id'] = $this->ids[mb_strtoupper(trim($r['employee_name']))] ?? null;
            $rows[] = $r + ['created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 300) as $chunk) DB::table($table)->insert($chunk);
        $this->command?->line(sprintf('  %-22s %d registros', $table, count($rows)));
    }

    private function benefitEntries(): void
    {
        if (BenefitEntry::whereNotNull('vt_amount')->orWhereNotNull('vr_amount')->exists()) return;

        $fields = ['vt_days', 'vr_days', 'vt_amount', 'vr_amount', 'adjustment_plus', 'adjustment_minus', 'adjustment_vt',
                   'adjustment_vr', 'correction_vt', 'correction_vr', 'reason', 'correction_reason'];
        $done = $skipped = 0;
        foreach ($this->json('benefit_entries') as $e) {
            $id = $this->ids[mb_strtoupper(trim($e['employee_name']))] ?? null;
            if (!$id) { $skipped++; continue; }

            $attrs = array_filter(Arr::only($e, $fields), fn ($v) => $v !== null);
            $attrs['extra'] = $e['extra'] ?: null;
            BenefitEntry::updateOrCreate(['employee_id' => $id, 'period' => $e['period']], $attrs);
            $done++;
        }
        $this->command?->line("  benefit_entries        $done lançamentos" . ($skipped ? " ($skipped sem funcionário correspondente)" : ''));
    }

    private function candidateMessages(): void
    {
        foreach ($this->json('candidates') as $c) {
            if (!($c['invite_message'] ?? null) && !($c['approved_message'] ?? null) && !($c['rejected_message'] ?? null)) continue;
            Candidate::where('name', $c['name'])->where('contact', $c['contact'])->whereNull('invite_message')
                ->whereNull('approved_message')->whereNull('rejected_message')
                ->update(Arr::only($c, ['invite_message', 'approved_message', 'rejected_message']));
        }
    }
}
