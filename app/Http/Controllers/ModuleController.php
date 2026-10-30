<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use App\Models\TimeOccurrence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * CRUD genérico dirigido por config/modules.php: cargos, vagas, candidatos, atestados,
 * ocorrências de ponto, coberturas, assiduidade, armários e calendário.
 */
class ModuleController extends Controller
{
    private function cfg(string $module): array
    {
        $cfg = config("modules.$module");
        abort_unless($cfg, 404);
        return $cfg;
    }

    public function index(Request $r, string $module)
    {
        $cfg = $this->cfg($module);
        $q = $cfg['model']::query();

        if (!empty($cfg['with'])) $q->with($cfg['with']);

        if (($s = trim((string) $r->query('q'))) !== '') {
            $q->where(function ($w) use ($cfg, $s) {
                foreach ($cfg['search'] as $col) $w->orWhere($col, 'like', "%{$s}%");
            });
        }
        foreach ($cfg['filters'] ?? [] as $f) {
            if (($v = $r->query($f[0])) !== null && $v !== '') $q->where($f[0], $v);
        }

        $rows = $q->orderBy(...$cfg['order'])->paginate(30)->withQueryString();
        $filters = collect($cfg['filters'] ?? [])->map(fn ($f) => [$f[0], $f[1], $this->filterOptions($cfg, $f)])->all();

        return view('module.index', compact('cfg', 'module', 'rows', 'filters'));
    }

    /** Opções do filtro: lista fixa, meses distintos ('@months') ou valores distintos ('@values'). */
    private function filterOptions(array $cfg, array $f): array
    {
        if ($f[2] === '@months') {
            return $cfg['model']::query()->whereNotNull($f[0])->distinct()->orderByDesc($f[0])->pluck($f[0])
                ->mapWithKeys(fn ($d) => [$d->format('Y-m-d') => $d->format('m/Y')])->all();
        }
        if ($f[2] === '@values') {
            $vals = $cfg['model']::query()->whereNotNull($f[0])->distinct()->orderByDesc($f[0])->pluck($f[0])->all();
            return array_combine($vals, $vals);
        }
        return array_combine($f[2], $f[2]);
    }

    public function create(string $module)
    {
        return $this->form($module, new ($this->cfg($module)['model'])());
    }

    public function edit(string $module, int $id)
    {
        return $this->form($module, $this->cfg($module)['model']::findOrFail($id));
    }

    private function form(string $module, $row)
    {
        $cfg = $this->cfg($module);

        $relations = [];
        foreach ($cfg['fields'] as $f) {
            if ($f[2] === 'relation') {
                $items = $f['model']::orderBy(...($f['order'] ?? ['id', 'asc']))->get();
                $relations[$f[0]] = $items->mapWithKeys(fn ($i) => [
                    $i->getKey() => collect($f['label'])->map(fn ($c) => $i->{$c})->filter()->implode(' - '),
                ])->all();
            }
        }

        $employeeNames = Employee::orderBy('name')->pluck('name');
        $suggest = [
            'positions' => Position::orderBy('name')->pluck('name'),
            'occurrence_kinds' => TimeOccurrence::query()->whereNotNull('kind')->distinct()->orderBy('kind')->pluck('kind'),
        ];

        return view('module.form', compact('cfg', 'module', 'row', 'relations', 'employeeNames', 'suggest'));
    }

    public function store(Request $r, string $module)
    {
        $cfg = $this->cfg($module);
        $row = new $cfg['model']();
        $row->fill($this->payload($r, $cfg))->save();

        return redirect()->route('m.index', $module)->with('ok', 'Registro criado.');
    }

    public function update(Request $r, string $module, int $id)
    {
        $cfg = $this->cfg($module);
        $cfg['model']::findOrFail($id)->update($this->payload($r, $cfg));

        return redirect()->route('m.index', $module)->with('ok', 'Registro atualizado.');
    }

    public function destroy(string $module, int $id)
    {
        $this->cfg($module)['model']::findOrFail($id)->delete();

        return redirect()->route('m.index', $module)->with('ok', 'Registro excluído.');
    }

    private function payload(Request $r, array $cfg): array
    {
        $rules = [];
        foreach ($cfg['fields'] as $f) {
            [$name, , $type] = $f;
            $rules[$name] = array_merge(
                !empty($f['required']) ? ['required'] : ['nullable'],
                match ($type) {
                    'date' => ['date'],
                    'number', 'money' => ['numeric'],
                    'bool' => ['boolean'],
                    'relation' => ['integer'],
                    'textarea' => ['string', 'max:5000'],
                    default => ['string', 'max:500'],
                }
            );
        }
        $data = $r->validate($rules);

        foreach ($cfg['fields'] as $f) {
            if ($f[2] === 'bool') $data[$f[0]] = $r->boolean($f[0]);
        }

        // vincula ao cadastro de funcionários pelo nome (a planilha identifica por nome)
        $table = (new $cfg['model']())->getTable();
        if (isset($data['employee_name']) && Schema::hasColumn($table, 'employee_id')) {
            $data['employee_id'] = Employee::where('name', $data['employee_name'])->value('id');
        }

        return $data;
    }
}
