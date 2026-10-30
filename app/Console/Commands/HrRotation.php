<?php

namespace App\Console\Commands;

use App\Support\Rotation;
use Illuminate\Console\Command;

/** Importa as âncoras do rodízio 12x36 do cadastro e/ou gera o padrão de um mês. */
class HrRotation extends Command
{
    protected $signature = 'hr:rotation {--import : cria as âncoras a partir dos padrões já cadastrados} {--generate= : gera o padrão do mês (AAAA-MM)} {--dry-run : simula, sem gravar}';
    protected $description = 'Rodízio 12x36 (dias ímpares/pares): importa âncoras do cadastro e gera o padrão mensal sem sobrescrever';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $month = $this->option('generate');

        if (!$this->option('import') && !$month) {
            $this->error('Informe --import e/ou --generate=AAAA-MM.');
            return self::INVALID;
        }
        if ($month && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('Mês inválido; use AAAA-MM (ex.: 2027-01).');
            return self::INVALID;
        }

        if ($this->option('import')) {
            $r = Rotation::importAnchors($dry);
            $this->info(sprintf('Âncoras%s: %d criadas, %d colaboradores já tinham, %d sem padrão anterior para importar.', $dry ? ' (simulação)' : '', $r['criadas'], $r['ja_tinham'], $r['sem_padrao']));
        }
        if ($month) {
            $r = Rotation::generateMonth($month, $dry);
            $this->info(sprintf('Padrão de %s%s: %d criados, %d já existiam (mantidos), %d sem âncora.', $month, $dry ? ' (simulação)' : '', $r['criados'], $r['ja_existiam'], $r['sem_ancora']));
        }

        return self::SUCCESS;
    }
}
