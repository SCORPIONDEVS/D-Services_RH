<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;

class Fmt
{
    public static function money($v): string
    {
        return $v === null || $v === '' ? '' : 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    public static function date($v): string
    {
        return $v instanceof CarbonInterface ? $v->format('d/m/Y') : '';
    }

    /** Renderiza uma célula da listagem conforme o tipo declarado em config/modules.php. */
    public static function cell($value, ?string $type = null): HtmlString|string
    {
        return match ($type) {
            'money' => self::money($value),
            'date' => self::date($value),
            'month' => $value instanceof CarbonInterface ? $value->format('m/Y') : '',
            'bool' => $value ? 'Sim' : 'Não',
            'badge' => $value === null || $value === '' ? '' : new HtmlString(
                '<span class="badge ' . self::badgeClass((string) $value) . '">' . e($value) . '</span>'
            ),
            default => (string) ($value ?? ''),
        };
    }

    public static function badgeClass(string $v): string
    {
        $v = mb_strtoupper($v);
        // Negativos primeiro: "INATIVO" contém "ATIV" e "NÃO LANÇADO" contém "LANÇADO"; se os positivos fossem checados antes, ficariam verdes.
        foreach ([
            'bad' => ['INATIV', 'REPROVADO', 'DESISTENTE', 'NÃO LANÇADO', 'NAO LANÇADO', 'NAO LANCADO', 'PENDENTE'],
            'ok' => ['ATIV', 'ABERTA', 'PAGO', 'LANÇADO', 'OK', 'CONTRATADO', 'ENVIADO', 'DISPONÍVEL', 'DISPONIVEL'],
            'warn' => ['PAUSADA', 'ENTREVISTA', 'PRÉ-APROVADO', 'CONTRATAÇÃO', 'OCUPADO', 'VERIFICAR'],
        ] as $cls => $words) {
            foreach ($words as $w) {
                if (str_contains($v, $w)) return $cls;
            }
        }
        return 'muted';
    }
}
