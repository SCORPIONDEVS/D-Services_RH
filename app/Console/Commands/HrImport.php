<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Database\Seeders\ExtraSheetsSeeder;
use Database\Seeders\SpreadsheetSeeder;
use Illuminate\Console\Command;
use Throwable;

class HrImport extends Command
{
    protected $signature = 'hr:import';
    protected $description = 'Importa os dados da planilha para o banco e mostra o erro exato, se houver';

    public function handle(): int
    {
        $this->info('Banco: ' . config('database.default') . ' / ' . config('database.connections.' . config('database.default') . '.database'));
        $this->line('Funcionários antes: ' . Employee::count());

        foreach ([SpreadsheetSeeder::class, ExtraSheetsSeeder::class] as $class) {
            $this->line("→ {$class}");
            try {
                $seeder = app($class)->setContainer(app())->setCommand($this);
                $seeder->run();
            } catch (Throwable $e) {
                $this->error('FALHOU: ' . $e->getMessage());
                $this->line($e->getFile() . ':' . $e->getLine());
                return self::FAILURE;
            }
        }

        $this->call('hr:admin');
        $this->call('hr:status');
        return self::SUCCESS;
    }
}
