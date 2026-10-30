<?php

namespace Tests\Feature;

use App\Models\BenefitEntry;
use App\Models\Employee;
use App\Models\ShiftPattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Percorre as telas principais com um usuário autenticado para pegar erros 500 e rotas quebradas. */
class AppSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('employees.index'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_telas_principais_abrem_sem_dados_e_com_dados(): void
    {
        $this->actingAs(User::factory()->create());

        // sem nenhum dado
        foreach (['dashboard', 'employees.index', 'employees.create', 'clinic.index', 'benefits.index', 'schedules.index',
                  'schedules.overview', 'schedules.patterns'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (array_keys(config('modules')) as $module) {
            $this->get(route('m.index', $module))->assertOk();
            $this->get(route('m.create', $module))->assertOk();
        }

        // com dados
        $e = Employee::create(['name' => 'ALAIDE TESTE', 'team' => 'LIMPEZA', 'scale' => '12X36', 'job_title' => 'AUXILIAR DE LIMPEZA',
            'admission_date' => '2025-10-18', 'salary' => 1837.40, 'vt_benefit' => true, 'vr_benefit' => true]);
        ShiftPattern::create(['employee_id' => $e->id, 'employee_name' => $e->name, 'team' => 'LIMPEZA',
            'schedule' => '06:00 às 18:00 - Jornada 12 x 36', 'month' => '2026-10', 'pattern' => 'ÍMPAR 8/8']);
        BenefitEntry::create(['employee_id' => $e->id, 'period' => '2026-10-1Q', 'vt_days' => 8, 'vr_days' => 8]);

        foreach (['dashboard', 'employees.index', 'schedules.overview', 'schedules.patterns', 'benefits.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('employees.show', $e))->assertOk();
        $this->get(route('employees.edit', $e))->assertOk();
        $this->get(route('employees.print', $e))->assertOk();
        $this->get(route('employees.clinic', $e))->assertOk();
        $this->get(route('employees.labels'))->assertOk();
        $this->get(route('employees.export'))->assertOk();
        $this->get(route('benefits.export'))->assertOk();
    }

    public function test_cadastro_e_edicao_de_funcionario_sem_escala(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('employees.store'), ['name' => 'maria teste'])->assertSessionHasNoErrors();
        $e = Employee::where('name', 'MARIA TESTE')->firstOrFail();
        $this->put(route('employees.update', $e), ['name' => 'MARIA TESTE', 'leader' => 'JESSICA'])->assertSessionHasNoErrors();
        $this->assertSame('JESSICA', $e->fresh()->leader);
        $this->delete(route('employees.destroy', $e))->assertRedirect(route('employees.index'));
    }

    public function test_postos_automaticos_e_atualizacao_do_padrao(): void
    {
        $this->actingAs(User::factory()->create());
        $e = Employee::create(['name' => 'FULANO', 'team' => 'LIMPEZA', 'scale' => '12X36', 'job_title' => 'AUXILIAR']);
        $p = ShiftPattern::create(['employee_id' => $e->id, 'employee_name' => 'FULANO', 'team' => 'LIMPEZA',
            'schedule' => '06:00 às 18:00 - Jornada 12 x 36', 'month' => '2026-10', 'pattern' => 'ÍMPAR 8/8']);

        $this->post(route('schedules.patterns.postos'), ['month' => '2026-10'])->assertSessionHasNoErrors();
        $this->patch(route('schedules.patterns.update', $p), ['pattern' => 'PAR 7/8'])->assertJson(['ok' => true, 'pattern' => 'PAR 7/8']);
    }

    public function test_beneficios_usam_os_dias_do_padrao_mensal(): void
    {
        $this->actingAs(User::factory()->create());
        $e = Employee::create(['name' => 'FULANO', 'team' => 'LIMPEZA', 'scale' => '12X36', 'admission_date' => '2025-01-01',
            'vt_benefit' => true, 'vr_benefit' => true, 'vt_tariff' => 5, 'vt_trips_per_day' => 2]);
        ShiftPattern::create(['employee_id' => $e->id, 'employee_name' => 'FULANO', 'team' => 'LIMPEZA', 'schedule' => 'x', 'month' => '2026-10', 'pattern' => 'ÍMPAR 8/7']);

        $this->post(route('benefits.generate'), ['period' => '2026-10-1Q'])->assertSessionHasNoErrors();
        $this->post(route('benefits.generate'), ['period' => '2026-10-2Q'])->assertSessionHasNoErrors();
        $this->assertSame(8, (int) BenefitEntry::where('period', '2026-10-1Q')->value('vt_days')); // 8 na 1ª quinzena
        $this->assertSame(7, (int) BenefitEntry::where('period', '2026-10-2Q')->value('vt_days')); // 7 na 2ª quinzena

        $entry = BenefitEntry::where('period', '2026-10-1Q')->first();
        $this->get(route('benefits.receipt', $entry))->assertOk();
        $this->patch(route('benefits.update', $entry), ['vt_days' => 6])->assertJsonStructure(['vt', 'vr', 'total', 'totals']);
        $this->delete(route('benefits.destroy', $entry))->assertRedirect();
    }
}
