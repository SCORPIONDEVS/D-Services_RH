<?php

namespace Database\Seeders;

use App\Models\Scale;
use App\Models\ScaleWorkShift;
use App\Models\TeamScale;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;

/**
 * Vínculos iniciais Equipe -> Escala -> Jornada. SUGESTÃO inferida da planilha: revise e ajuste
 * pela tela "Vínculos de escala" (Escalas). Idempotente:  php artisan db:seed --class=ScheduleLinksSeeder
 */
class ScheduleLinksSeeder extends Seeder
{
    public function run(): void
    {
        $scales = [
            '12X36' => 'Jornada 12 x 36', '4x2' => 'Jornada 4 x 2', '5x2 seg-sex' => 'Jornada 5 x 2',
            '6X1 2ª-Sáb' => 'Jornada 6 x 1', '5X2 2ª-6ª' => 'Jornada 5 x 2',
        ];
        foreach ($scales as $name => $label) Scale::firstOrCreate(['name' => $name], ['label' => $label]);

        $teamScales = [
            'LIMPEZA' => ['12X36'],
            'SEGURANÇA' => ['12X36', '5x2 seg-sex', '5X2 2ª-6ª', '6X1 2ª-Sáb'],
            'TERMINAL' => ['4x2', '5x2 seg-sex'],
        ];
        foreach ($teamScales as $team => $names) {
            foreach ($names as $n) TeamScale::firstOrCreate(['team' => $team, 'scale_id' => Scale::where('name', $n)->value('id')]);
        }

        // Jornada usada na planilha (Bombeiro Civil 09:00-21:00) que não consta em "Jornadas por cargo".
        // Mesmo padrão das demais 12x36: expediente 12:00, 11:00 efetivas (supõe 1h de intervalo; ajuste se for outro).
        WorkShift::firstOrCreate(['name' => 'FISCAL DIURNO 09-21'], [
            'entry_time' => '09:00', 'exit_time' => '21:00', 'hours_total' => '12:00', 'hours_worked' => '11:00',
        ]);

        // [escala, equipe, jornada (nome em "Jornadas por cargo")]
        $links = [];
        foreach (['LIMPEZA PISO/BANHEIRO DIURNO', 'LIMPEZA PRAÇA/BOULEVARD', 'LIMPEZA NOTURNO', 'LIMPEZA FOOD'] as $j) $links[] = ['12X36', 'LIMPEZA', $j];
        foreach (['FISCAL DIURNO', 'FISCAL DIURNO 09-21', 'FISCAL NOTURNO'] as $j) $links[] = ['12X36', 'SEGURANÇA', $j];
        // 6X1: a jornada RH cobre seg-sex 08:00-17:00; o sábado (08:00-12:00) não cabe no cadastro, que só tem entrada/saída.
        $links[] = ['6X1 2ª-Sáb', 'SEGURANÇA', 'RH'];
        foreach (['5x2 seg-sex', '5X2 2ª-6ª'] as $s) $links[] = [$s, 'SEGURANÇA', 'ZELADOR'];
        foreach (['4x2', '5x2 seg-sex'] as $s) {
            foreach (['ORIENTADOR MANHÃ', 'ORIENTADOR TARDE'] as $j) $links[] = [$s, 'TERMINAL', $j];
        }

        foreach ($links as [$scale, $team, $shift]) {
            $scaleId = Scale::where('name', $scale)->value('id');
            $shiftId = WorkShift::where('name', $shift)->value('id');
            if (!$scaleId || !$shiftId) { $this->command?->warn("Jornada não encontrada, vínculo ignorado: $shift"); continue; }
            ScaleWorkShift::firstOrCreate(['scale_id' => $scaleId, 'work_shift_id' => $shiftId, 'team' => $team]);
        }

        $this->command?->info('Vínculos de escala criados (revise na tela "Vínculos de escala").');
    }
}
