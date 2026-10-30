<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Backup (CSV + cópia do SQLite) dos dados de escala antes da migração Equipe/Escala/Jornada. */
class HrBackupSchedules extends Command
{
    protected $signature = 'hr:backup-schedules';
    protected $description = 'Exporta funcionários (equipe/escala/jornada) e padrões mensais para CSV, e copia o arquivo SQLite se for o driver em uso';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $stamp = now()->format('Ymd-His');

        $this->csv("$dir/employees-schedules-$stamp.csv", DB::table('employees')->orderBy('id'),
            ['id', 'name', 'termination_date', 'team', 'scale', 'work_schedule']);
        $this->csv("$dir/shift-patterns-$stamp.csv", DB::table('shift_patterns')->orderBy('id'),
            ['id', 'employee_id', 'employee_name', 'team', 'schedule', 'month', 'pattern']);

        if (config('database.default') === 'sqlite') {
            $file = config('database.connections.sqlite.database');
            if ($file && $file !== ':memory:' && is_file($file)) {
                copy($file, "$dir/database-$stamp.sqlite");
                $this->line("  cópia do banco: $dir/database-$stamp.sqlite");
            }
        } else {
            $this->warn('Banco não é SQLite: faça também o dump do banco (mysqldump/pg_dump) antes de migrar.');
        }

        $this->info("Backup gravado em $dir (carimbo $stamp)");
        return self::SUCCESS;
    }

    private function csv(string $path, $query, array $cols): void
    {
        $out = fopen($path, 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $cols, ';');
        $query->chunkById(200, function ($chunk) use ($out, $cols) {
            foreach ($chunk as $r) fputcsv($out, array_map(fn ($c) => $r->{$c}, $cols), ';');
        });
        fclose($out);
        $this->line("  $path");
    }
}
