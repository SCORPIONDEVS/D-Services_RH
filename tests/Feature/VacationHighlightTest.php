<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VacationHighlightTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today = Carbon::parse('2026-10-30')->startOfDay();
        Carbon::setTestNow($this->today);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function emp(array $a = []): Employee
    {
        return Employee::create($a + ['name' => 'FULANO ' . uniqid(), 'team' => 'LIMPEZA', 'admission_date' => '2024-12-15']);
    }

    private function vac(Employee $e, int $alert = 90): ?array
    {
        return $e->fresh()->vacationStatus($this->today, $alert);
    }

    /* ---------- regra ---------- */

    public function test_em_ferias_quando_hoje_esta_entre_inicio_e_fim(): void
    {
        $s = $this->vac($this->emp(['vacation_start' => '2026-10-20', 'vacation_end' => '2026-11-03']));
        $this->assertSame('em_ferias', $s['status']);
        $this->assertSame('2026-11-03', $s['date']->toDateString());

        // limites inclusivos: último dia e primeiro dia
        $this->assertSame('em_ferias', $this->vac($this->emp(['vacation_start' => '2026-10-20', 'vacation_end' => '2026-10-30']))['status']);
        $this->assertSame('em_ferias', $this->vac($this->emp(['vacation_start' => '2026-10-30', 'vacation_end' => '2026-11-13']))['status']);
    }

    public function test_ferias_ja_terminadas_ou_futuras_nao_sao_em_ferias(): void
    {
        $this->assertNull($this->vac($this->emp(['vacation_start' => '2026-09-01', 'vacation_end' => '2026-09-30'])));  // gozada: período satisfeito
        $this->assertNull($this->vac($this->emp(['vacation_start' => '2026-11-20', 'vacation_end' => '2026-12-04'])));  // agendada: não alerta
    }

    public function test_em_ferias_tem_prioridade_sobre_vencendo(): void
    {
        // admissão 15/12/2024: período vence em 14/12/2026 (45 dias) — mas está em férias agora
        $this->assertSame('em_ferias', $this->vac($this->emp(['vacation_start' => '2026-10-25', 'vacation_end' => '2026-11-08']))['status']);
    }

    public function test_vencendo_quando_o_prazo_de_gozo_esta_dentro_da_janela(): void
    {
        $s = $this->vac($this->emp());                       // período 1 completo em 14/12/2025; gozo até 14/12/2026
        $this->assertSame('vencendo', $s['status']);
        $this->assertSame('2026-12-14', $s['date']->toDateString());
        $this->assertSame(45, $s['days']);

        $this->assertNull($this->vac($this->emp(), 30));     // janela menor que 45 dias: ainda não destaca
        $this->assertSame('vencendo', $this->vac($this->emp(), 45)['status']); // limite inclusivo
    }

    public function test_vencida_quando_o_prazo_ja_passou(): void
    {
        $s = $this->vac($this->emp(['admission_date' => '2024-01-10']));    // gozo até 09/01/2026, sem férias lançadas
        $this->assertSame('vencida', $s['status']);
        $this->assertSame('2026-01-09', $s['date']->toDateString());
        $this->assertLessThan(0, $s['days']);
    }

    public function test_varios_anos_considera_o_primeiro_periodo_sem_ferias(): void
    {
        // admissão 10/01/2024, última férias em 01/06/2025 (gozada no período 1): o período 2 completou em 09/01/2026,
        // gozo até 09/01/2027 => 71 dias, dentro da janela de 90
        $s = $this->vac($this->emp(['admission_date' => '2024-01-10', 'vacation_start' => '2025-06-01', 'vacation_end' => '2025-06-30']));
        $this->assertSame('vencendo', $s['status']);
        $this->assertSame('2027-01-09', $s['date']->toDateString());

        // com as férias do período 2 já lançadas (jun/2026), nada a destacar
        $this->assertNull($this->vac($this->emp(['admission_date' => '2024-01-10', 'vacation_start' => '2026-06-01', 'vacation_end' => '2026-06-30'])));
    }

    public function test_sem_destaque_para_periodo_incompleto_inativo_ou_sem_admissao(): void
    {
        $this->assertNull($this->vac($this->emp(['admission_date' => '2025-10-18'])));                       // aquisitivo termina em 17/10/2026
        $this->assertNull($this->vac($this->emp(['admission_date' => '2026-03-01'])));                       // ainda no 1º período
        $this->assertNull($this->vac($this->emp(['termination_date' => '2026-05-01'])));                     // desligado
        $this->assertNull($this->vac($this->emp(['admission_date' => null])));
        $this->assertNull($this->vac($this->emp(['admission_date' => '2025-02-15'])));                       // gozo até 14/02/2027: fora da janela
    }

    /* ---------- lista ---------- */

    public function test_lista_destaca_verde_e_laranja_e_mostra_a_legenda(): void
    {
        $this->actingAs(User::factory()->create());
        $this->emp(['name' => 'EM FERIAS', 'vacation_start' => '2026-10-20', 'vacation_end' => '2026-11-03']);
        $this->emp(['name' => 'VENCENDO']);
        $this->emp(['name' => 'VENCIDA', 'admission_date' => '2024-01-10']);
        $this->emp(['name' => 'NORMAL', 'admission_date' => '2026-03-01']);
        $this->emp(['name' => 'DESLIGADO', 'termination_date' => '2026-05-01', 'admission_date' => '2024-01-10']);

        $html = $this->get(route('employees.index', ['status' => 'todos']))->assertOk()
            ->assertSee('Em férias até 03/11/2026')
            ->assertSee('Férias vencem em 45 dias (14/12/2026)')
            ->assertSee('Férias vencidas desde 09/01/2026')
            ->assertSee('class="vac-legend"', false)
            ->getContent();

        $row = fn (string $name) => preg_match('/<tr class="([^"]*)">\s*<td class="id">\d+<\/td>\s*<td><a [^>]*><b>' . $name . '<\/b>/u', $html, $m) ? $m[1] : null;
        $this->assertSame('vac-on', $row('EM FERIAS'));
        $this->assertSame('vac-due', $row('VENCENDO'));
        $this->assertSame('vac-due', $row('VENCIDA'));
        $this->assertSame('', $row('NORMAL'));
        $this->assertSame('', $row('DESLIGADO'));
    }

    public function test_janela_de_alerta_pode_ser_ajustada_em_configuracao(): void
    {
        $this->actingAs(User::factory()->create());
        $this->emp(['name' => 'VENCENDO']);                                   // prazo em 45 dias

        Setting::put('vacation_alert_days', 30);
        $this->get(route('employees.index'))->assertDontSee('Férias vencem em')->assertSee('em até 30 dias');

        Setting::put('vacation_alert_days', 60);
        $this->get(route('employees.index'))->assertSee('Férias vencem em 45 dias');
    }

    /* ---------- selo de status e coluna ID ---------- */

    public function test_selo_de_status_inativo_e_vermelho_e_ativo_e_verde(): void
    {
        foreach (['INATIVO' => 'bad', 'inativo' => 'bad', 'ATIVO' => 'ok', 'NÃO LANÇADO' => 'bad', 'LANÇADO' => 'ok',
                  'PENDENTE' => 'bad', 'PAGO' => 'ok', 'ENTREVISTA' => 'warn', 'QUALQUER COISA' => 'muted'] as $texto => $classe) {
            $this->assertSame($classe, \App\Support\Fmt::badgeClass($texto), $texto);
        }
    }

    public function test_lista_mostra_coluna_id_e_selo_vermelho_para_inativo(): void
    {
        $this->actingAs(User::factory()->create());
        $ativo = $this->emp(['name' => 'ATIVO UM']);
        $inativo = $this->emp(['name' => 'INATIVO UM', 'termination_date' => '2026-05-01']);

        $html = $this->get(route('employees.index', ['status' => 'todos']))->assertOk()->assertSee('<th class="id">ID</th>', false)->getContent();

        $this->assertMatchesRegularExpression('/<td class="id">' . $ativo->id . '<\/td>\s*<td><a [^>]*><b>ATIVO UM<\/b>/u', $html);
        $this->assertMatchesRegularExpression('/<td class="id">' . $inativo->id . '<\/td>\s*<td><a [^>]*><b>INATIVO UM<\/b>/u', $html);
        $this->assertStringContainsString('<span class="badge bad">INATIVO</span>', $html);
        $this->assertStringContainsString('<span class="badge ok">ATIVO</span>', $html);
    }
}
