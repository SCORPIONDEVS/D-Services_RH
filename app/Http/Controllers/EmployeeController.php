<?php

namespace App\Http\Controllers;

use App\Models\Dependent;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Setting;
use App\Support\Fmt;
use App\Support\ScheduleLinks;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    private function fields(): array
    {
        return config('employee_fields');
    }

    public function index(Request $r)
    {
        $status = $r->query('status', 'ativo');
        $q = Employee::query();

        if ($status === 'ativo') $q->active();
        elseif ($status === 'inativo') $q->inactive();

        if ($v = $r->query('team')) $q->where('team', $v);
        if ($v = $r->query('company')) $q->where('company', $v);
        if ($v = $r->query('job_title')) $q->where('job_title', $v);
        if (($s = trim((string) $r->query('q'))) !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('cpf', 'like', '%' . preg_replace('/\D/', '', $s) . '%')->orWhere('leader', 'like', "%{$s}%"));
        }

        $employees = $q->orderBy('team')->orderBy('name')->paginate(50)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'teams' => Employee::distinct()->whereNotNull('team')->pluck('team'),
            'companies' => Employee::distinct()->whereNotNull('company')->pluck('company'),
            'jobTitles' => Employee::distinct()->whereNotNull('job_title')->orderBy('job_title')->pluck('job_title'),
            'status' => $status,
            'vacationAlertDays' => max(1, (int) Setting::get('vacation_alert_days', 90)),
            'counts' => ['ativo' => Employee::active()->count(), 'inativo' => Employee::inactive()->count()],
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['dependents', 'companyRef']);

        return view('employees.show', [
            'e' => $employee,
            'sections' => $this->fields(),
            'certificates' => $employee->certificates()->orderByDesc('start_date')->limit(30)->get(),
            'occurrences' => $employee->occurrences()->orderByDesc('date')->limit(30)->get(),
        ]);
    }

    public function print(Employee $employee)
    {
        $employee->load(['dependents', 'companyRef']);
        return view('employees.print', ['e' => $employee, 'sections' => $this->fields()]);
    }

    /** Menu "Guia de Clínica": escolhe o colaborador e o tipo de exame e abre o formulário pronto. */
    public function clinicIndex(Request $r)
    {
        $types = [
            'admissional' => 'Admissional', 'periodico' => 'Periódico', 'retorno' => 'Retorno ao Trabalho', 'demissional' => 'Demissional',
            'mudanca' => 'Mudança de Função', 'avaliacao' => 'Avaliação Médica', 'toxicologico' => 'Toxicológico',
        ];
        $type = array_key_exists($r->query('tipo'), $types) ? $r->query('tipo') : 'periodico';
        $status = in_array($r->query('status'), ['ativo', 'inativo', 'todos'], true) ? $r->query('status') : 'ativo';

        $q = Employee::query()->orderBy('name');
        if ($status === 'ativo') $q->active(); elseif ($status === 'inativo') $q->inactive();
        if ($v = $r->query('team')) $q->where('team', $v);
        if (($s = trim((string) $r->query('q'))) !== '') $q->where('name', 'like', "%{$s}%");

        return view('employees.clinic-index', [
            'rows' => $q->paginate(30)->withQueryString(), 'types' => $types, 'type' => $type, 'status' => $status,
            'teams' => Employee::whereNotNull('team')->distinct()->orderBy('team')->pluck('team'),
        ]);
    }

    /** Formulário de encaminhamento para exame ocupacional (aba GUIA CLÍNICA), já preenchido. */
    public function clinicGuide(Request $r, Employee $employee)
    {
        $types = [
            'admissional' => 'Admissional', 'periodico' => 'Periódico', 'retorno' => 'Retorno ao Trabalho', 'demissional' => 'Demissional',
            'mudanca' => 'Mudança de Função', 'avaliacao' => 'Avaliação Médica', 'toxicologico' => 'Toxicológico',
        ];
        $default = $employee->admission_date && $employee->admission_date->gte(now()->subDays(30)) ? 'admissional' : 'periodico';
        $type = array_key_exists($r->query('tipo'), $types) ? $r->query('tipo') : $default;

        return view('employees.clinic-guide', [
            'e' => $employee, 'types' => $types, 'type' => $type,
            'clinic' => Setting::get('clinic', []),
        ]);
    }

    /** Etiquetas de prontuário (aba ETIQUETAS PRONTUÁRIOS) para o filtro atual. */
    public function labels(Request $r)
    {
        $status = $r->query('status', 'ativo');
        $q = Employee::query();
        if ($status === 'ativo') $q->active(); elseif ($status === 'inativo') $q->inactive();
        if ($v = $r->query('team')) $q->where('team', $v);
        if ($v = $r->query('admitted_from')) $q->whereDate('admission_date', '>=', $v);

        return view('employees.labels', ['employees' => $q->orderBy('name')->get(['id', 'name'])]);
    }

    public function create()
    {
        return $this->form(new Employee());
    }

    public function edit(Employee $employee)
    {
        $employee->load('dependents');
        return $this->form($employee);
    }

    private function form(Employee $e)
    {
        return view('employees.form', [
            'e' => $e,
            'sections' => $this->fields(),
            'positions' => Position::orderBy('name')->pluck('name'),
            'cascade' => ScheduleLinks::cascadeMap(),
        ]);
    }

    public function store(Request $r)
    {
        $e = Employee::create($this->payload($r));
        $this->syncDependents($e, $r);

        return redirect()->route('employees.show', $e)->with('ok', 'Funcionário cadastrado.');
    }

    public function update(Request $r, Employee $employee)
    {
        $employee->update($this->payload($r, $employee));
        $this->syncDependents($employee, $r);

        return redirect()->route('employees.show', $employee)->with('ok', 'Cadastro atualizado.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('ok', 'Funcionário excluído.');
    }

    private function payload(Request $r, ?Employee $current = null): array
    {
        $rules = [];
        foreach ($this->fields() as $fields) {
            foreach ($fields as $f) {
                [$name, , $type] = $f;
                $rules[$name] = array_merge(
                    !empty($f['required']) ? ['required'] : ['nullable'],
                    match ($type) {
                        'date' => ['date'],
                        'number', 'money' => ['numeric'],
                        'bool' => ['boolean'],
                        'email' => ['email', 'max:255'],
                        'textarea' => ['string', 'max:5000'],
                        default => ['string', 'max:255'],
                    }
                );
            }
        }
        $rules['work_shift_id'] = ['nullable', 'integer'];
        $rules['dependents.*.name'] = ['nullable', 'string', 'max:255'];
        $rules['dependents.*.birth_date'] = ['nullable', 'date'];
        $rules['dependents.*.cpf'] = ['nullable', 'string', 'max:14'];
        $rules['dependents.*.relationship'] = ['nullable', 'string', 'max:100'];

        $data = $r->validate($rules);
        unset($data['dependents']);

        foreach ($this->fields() as $fields) {
            foreach ($fields as $f) {
                if ($f[2] === 'bool') $data[$f[0]] = $r->boolean($f[0]);
            }
        }
        foreach (['cpf', 'pis', 'zip'] as $k) {
            if (!empty($data[$k])) $data[$k] = preg_replace('/\D/', '', $data[$k]);
        }
        if (!empty($data['name'])) $data['name'] = mb_strtoupper(trim($data['name']));

        return $this->applySchedule($data, $current);
    }

    /**
     * Cascata Equipe -> Escala -> Jornada: valida a combinação no servidor e deriva o texto work_schedule.
     * Uma combinação legada que não foi alterada nesta edição é tolerada (fica "pendente de revisão"),
     * para não impedir a edição de outros dados do funcionário.
     */
    private function applySchedule(array $data, ?Employee $current): array
    {
        $team = $data['team'] ?? null;
        $scale = $data['scale'] ?? null;
        $shiftId = !empty($data['work_shift_id']) ? (int) $data['work_shift_id'] : null;
        $data['work_shift_id'] = $shiftId;
        unset($data['work_schedule']); // derivado do vínculo; não é mais digitado

        $unchanged = $current && $current->exists
            && ($current->team ?: null) === $team && ($current->scale ?: null) === $scale
            && ($current->work_shift_id ? (int) $current->work_shift_id : null) === $shiftId;

        if (!$unchanged && ($errors = ScheduleLinks::errors($team, $scale, $shiftId))) {
            throw ValidationException::withMessages($errors);
        }

        if ($shiftId) {
            $data['work_schedule'] = ScheduleLinks::derivedText($shiftId, $scale);
            $data['schedule_review'] = false;
        } elseif (!$unchanged) {
            $data['work_schedule'] = null;
        }

        return $data;
    }

    private function syncDependents(Employee $e, Request $r): void
    {
        $e->dependents()->delete();
        foreach ((array) $r->input('dependents', []) as $d) {
            if (!empty($d['name'])) {
                $e->dependents()->create([
                    'name' => mb_strtoupper(trim($d['name'])),
                    'birth_date' => $d['birth_date'] ?: null,
                    'cpf' => isset($d['cpf']) ? preg_replace('/\D/', '', $d['cpf']) : null,
                    'relationship' => $d['relationship'] ?? null,
                ]);
            }
        }
    }

    /** Exporta a base em CSV (separador ';' e BOM, para abrir direto no Excel). */
    public function export(Request $r)
    {
        $status = $r->query('status', 'ativo');
        $q = Employee::query()->with('companyRef');
        if ($status === 'ativo') $q->active();
        elseif ($status === 'inativo') $q->inactive();

        $cols = [
            'Nome' => 'name', 'Status' => 'status', 'Empresa' => 'company', 'CNPJ' => 'cnpj', 'Equipe' => 'team',
            'Cargo' => 'job_title', 'Salário' => 'salary', 'Admissão' => 'admission_date', 'Fim 45d' => 'trial45End',
            'Fim 90d' => 'trial90End', 'ASO admissional' => 'aso_admission_date', 'Venc. ASO' => 'asoAdmissionExpiry',
            'Demissão' => 'termination_date', 'Motivo demissão' => 'termination_reason', 'CPF' => 'cpf', 'Escala' => 'scale',
            'Jornada' => 'work_schedule', 'Líder' => 'leader', 'Celular' => 'mobile', 'VT/dia' => 'vtPerDay', 'VR/dia' => 'vrPerDay',
        ];

        return response()->streamDownload(function () use ($q, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($cols), ';');
            $q->orderBy('team')->orderBy('name')->chunk(200, function ($chunk) use ($out, $cols) {
                foreach ($chunk as $e) {
                    fputcsv($out, array_map(function ($attr) use ($e) {
                        $v = $e->{$attr};
                        return $v instanceof \Carbon\CarbonInterface ? $v->format('d/m/Y') : $v;
                    }, $cols), ';');
                }
            });
            fclose($out);
        }, 'funcionarios-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
