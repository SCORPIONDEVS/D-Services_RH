<?php

namespace App\Http\Controllers;

use App\Models\CalendarTask;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\MedicalCertificate;
use App\Models\Position;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $active = Employee::active()->get();

        // Quadro de funcionários: previsto x ativos por cargo
        $quota = Position::whereNotNull('staff_quota')->orderBy('name')->get()->map(function ($p) use ($active) {
            $n = $active->where('job_title', $p->name)->count();
            return ['name' => $p->name, 'quota' => (int) $p->staff_quota, 'active' => $n, 'diff' => $n - (int) $p->staff_quota, 'note' => $p->quota_note];
        });

        // Contratos de experiência: próximo vencimento (45 ou 90 dias) nos próximos 15 dias
        $trials = $active->map(function ($e) use ($today) {
            if (!$e->trial45End) return null;
            if ($today->lte($e->trial45End)) return ['e' => $e, 'date' => $e->trial45End, 'label' => '45 dias'];
            if ($today->lte($e->trial90End)) return ['e' => $e, 'date' => $e->trial90End, 'label' => '90 dias'];
            return null;
        })->filter()->filter(fn ($t) => $t['date']->lte($today->copy()->addDays(15)))->sortBy('date')->values();

        // ASO admissional vencido / a vencer em 30 dias
        $asos = $active->filter(fn ($e) => $e->asoAdmissionExpiry && $e->asoAdmissionExpiry->lte($today->copy()->addDays(30)))
            ->sortBy(fn ($e) => $e->asoAdmissionExpiry)->values();

        // Férias: período aquisitivo completo sem férias lançadas
        $vacations = $active->filter(fn ($e) => $e->acquisitiveEnd && $e->acquisitiveEnd->lt($today) && !$e->vacation_start)
            ->sortBy(fn ($e) => $e->acquisitiveEnd)->values();

        $birthdays = $active->filter(fn ($e) => $e->birth_date && $e->birth_date->month === $today->month)
            ->sortBy(fn ($e) => $e->birth_date->day)->values();

        // Atestados por mês (últimos 6 meses)
        $from = $today->copy()->startOfMonth()->subMonths(5);
        $certs = MedicalCertificate::where('start_date', '>=', $from)->get(['start_date']);
        $certLabels = [];
        $certData = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $from->copy()->addMonths($i);
            $certLabels[] = $m->format('m/Y');
            $certData[] = $certs->filter(fn ($c) => $c->start_date->format('Y-m') === $m->format('Y-m'))->count();
        }

        $byTeam = $active->groupBy(fn ($e) => $e->team ?: 'Sem equipe')->map->count();
        $candidateStatus = Candidate::selectRaw('status, count(*) as total')->groupBy('status')->orderBy('status')->pluck('total', 'status');

        $chart = [
            'team' => ['labels' => $byTeam->keys()->values(), 'data' => $byTeam->values()],
            'certs' => ['labels' => $certLabels, 'data' => $certData],
            'candidates' => ['labels' => $candidateStatus->keys()->values(), 'data' => $candidateStatus->values()],
        ];

        return view('dashboard.index', [
            'today' => $today,
            'kpi' => [
                'active' => $active->count(),
                'inactive' => Employee::inactive()->count(),
                'openings' => JobOpening::whereIn('stage', ['ABERTA', 'PAUSADA', 'CONTRATAÇÃO'])->count(),
                'certs30' => MedicalCertificate::where('start_date', '>=', $today->copy()->subDays(30))->count(),
            ],
            'quota' => $quota, 'trials' => $trials, 'asos' => $asos, 'vacations' => $vacations, 'birthdays' => $birthdays,
            'openings' => JobOpening::whereIn('stage', ['ABERTA', 'PAUSADA', 'CONTRATAÇÃO'])->orderByDesc('number')->limit(8)->get(),
            'tasks' => CalendarTask::where('day', $today->day)->orderBy('area')->get(),
            'upcomingTasks' => CalendarTask::where('day', '>', $today->day)->where('day', '<=', $today->day + 5)->orderBy('day')->get(),
            'chart' => $chart,
        ]);
    }
}
