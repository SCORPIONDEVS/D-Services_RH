<?php

namespace App\Support;

use App\Models\Scale;
use App\Models\ScaleWorkShift;
use App\Models\TeamScale;
use App\Models\WorkShift;

/** Regras da cascata Equipe -> Escala -> Jornada (usadas pelo formulário, pela validação e pela migração). */
class ScheduleLinks
{
    /** Mapa para o formulário: escalas por equipe e jornadas por equipe|escala. */
    public static function cascadeMap(): array
    {
        $scales = Scale::orderBy('name')->get()->keyBy('id');
        $shifts = WorkShift::orderBy('id')->get()->keyBy('id');

        $teams = [];
        foreach (TeamScale::orderBy('id')->get() as $l) {
            if ($s = $scales->get($l->scale_id)) $teams[$l->team][] = $s->name;
        }

        $byScale = [];
        foreach (ScaleWorkShift::orderBy('id')->get() as $l) {
            $s = $scales->get($l->scale_id);
            $w = $shifts->get($l->work_shift_id);
            if (!$s || !$w) continue;
            $byScale[$s->name][] = [
                'id' => $w->id, 'name' => $w->name, 'entry' => $w->entry_time, 'exit' => $w->exit_time,
                'team' => $l->team, 'label' => $w->timeLabel($s->label),
            ];
        }

        return ['teams' => $teams, 'shifts' => $byScale];
    }

    /** Escalas permitidas para uma equipe. */
    public static function scalesFor(?string $team): array
    {
        if (!$team) return [];
        return TeamScale::where('team', $team)->join('scales', 'scales.id', '=', 'team_scale.scale_id')
            ->orderBy('scales.name')->pluck('scales.name')->all();
    }

    /** A jornada vale para esta escala (e, se o vínculo tem equipe, para esta equipe)? */
    public static function shiftAllowed(?string $team, ?string $scale, int $shiftId): bool
    {
        $scaleId = Scale::where('name', $scale)->value('id');
        if (!$scaleId) return false;

        return ScaleWorkShift::where('scale_id', $scaleId)->where('work_shift_id', $shiftId)
            ->where(fn ($q) => $q->whereNull('team')->orWhere('team', $team))->exists();
    }

    /**
     * Valida a combinação. Retorna [campo => mensagem em português] (vazio = válida).
     * Equipe/Escala/Jornada vazias são aceitas (cadastros antigos); só o que foi informado precisa ser coerente.
     */
    public static function errors(?string $team, ?string $scale, ?int $shiftId): array
    {
        $err = [];

        if ($scale) {
            if (!$team) {
                $err['scale'] = 'Escolha a equipe antes da escala.';
            } elseif (!in_array($scale, self::scalesFor($team), true)) {
                $err['scale'] = "A escala {$scale} não está disponível para a equipe {$team}.";
            }
        }

        if ($shiftId) {
            $shift = WorkShift::find($shiftId);
            if (!$shift) {
                $err['work_shift_id'] = 'A jornada escolhida não existe.';
            } elseif (!$scale) {
                $err['work_shift_id'] = 'Escolha a escala antes da jornada.';
            } elseif (!isset($err['scale']) && !self::shiftAllowed($team, $scale, $shiftId)) {
                $err['work_shift_id'] = "A jornada {$shift->name} não está disponível para a escala {$scale}" . ($team ? " na equipe {$team}." : '.');
            }
        }

        return $err;
    }

    /** Texto derivado gravado em employees.work_schedule: "06:00 às 18:00 - Jornada 12 x 36". */
    public static function derivedText(int $shiftId, ?string $scale): ?string
    {
        return WorkShift::find($shiftId)?->timeLabel(Scale::labelFor($scale));
    }

    /** Extrai [entrada, saída] ("HH:MM") do texto livre legado; null se não houver horário. */
    public static function parseLegacy(?string $text): ?array
    {
        if (!$text || !preg_match('/(\d{1,2}):(\d{2})\s*(?:às|a|-)\s*(\d{1,2}):(\d{2})/u', $text, $m)) return null;
        return [sprintf('%02d:%s', $m[1], $m[2]), sprintf('%02d:%s', $m[3], $m[4])];
    }
}
