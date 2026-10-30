<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRow;
use App\Models\ArchiveSheet;
use App\Models\BenefitHistory;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /** Histórico de benefícios VT/VR por bloco (mês/quinzena): colaboradores × campos. */
    public function benefits(Request $r)
    {
        $blocks = BenefitHistory::select('block', 'block_order')->distinct()->orderBy('block_order')->pluck('block')->all();
        $block = in_array($r->query('block'), $blocks, true) ? $r->query('block') : ($blocks[1] ?? ($blocks[0] ?? null));

        $q = BenefitHistory::where('block', $block)->orderBy('field_order');
        if (($s = trim((string) $r->query('q'))) !== '') $q->where('employee_name', 'like', "%{$s}%");
        $rows = $q->get();

        $fields = $rows->pluck('field')->unique()->values();
        $byEmployee = $rows->groupBy('employee_name')->sortKeys();

        // Soma das colunas numéricas (dias e valores)
        $sums = [];
        foreach ($fields as $f) {
            $nums = $rows->where('field', $f)->pluck('number')->filter(fn ($n) => $n !== null);
            $sums[$f] = $nums->count() ? $nums->sum() : null;
        }

        return view('history.benefits', compact('blocks', 'block', 'fields', 'byEmployee', 'sums'));
    }

    public function archiveIndex()
    {
        return view('archive.index', ['sheets' => ArchiveSheet::orderBy('hidden')->orderBy('name')->get()]);
    }

    public function archiveShow(Request $r, string $slug)
    {
        $sheet = ArchiveSheet::where('slug', $slug)->firstOrFail();
        $q = ArchiveRow::where('archive_sheet_id', $sheet->id)->orderBy('row_number');
        if (($s = trim((string) $r->query('q'))) !== '') $q->where('cells', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $s) . '%');
        $rows = $q->paginate(100)->withQueryString();

        return view('archive.show', compact('sheet', 'rows'));
    }
}
