<?php

namespace Tests\Feature;

use App\Models\BenefitEntry;
use App\Models\Employee;
use App\Models\RotationAnchor;
use App\Models\ShiftPattern;
use App\Models\User;
use App\Support\Rotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RotationTest extends TestCase
{
    use RefreshDatabase;

    private function d(string $s): Carbon { return Carbon::parse($s)->startOfDay(); }

    private function labels(array $anchors, int $from, int $to): array
    {
        return array_map(fn ($m) => Rotation::monthPattern($anchors, 2026, $m), range($from, $to));
    }

    /* ---------- regra, conferida com os padrões lançados na planilha (mar–dez/2026) ---------- */

    public function test_rotacao_estrita_reproduz_marco_a_julho_do_grupo_par(): void
    {
        // JESSICA / ALAIDE...: PAR em março (trabalham 30/03), planilha: PAR, ÍMPAR 8/7, ÍMPAR 8/8, PAR 7/8, PAR 7/8
        $this->assertSame(['PAR 7/8', 'ÍMPAR 8/7', 'ÍMPAR 8/8', 'PAR 7/8', 'PAR 7/8'], $this->labels([$this->d('2026-03-30')], 3, 7));
    }

    public function test_reinicio_manual_de_agosto_reproduz_o_resto_do_ano_do_grupo_par(): void
    {
        $a = [$this->d('2026-03-30'), $this->d('2026-08-02')];
        // planilha ago–dez: PAR 8/8, ÍMPAR 8/7, ÍMPAR 8/8, PAR 7/8, PAR 7/8
        $this->assertSame(['PAR 8/8', 'ÍMPAR 8/7', 'ÍMPAR 8/8', 'PAR 7/8', 'PAR 7/8'], $this->labels($a, 8, 12));
        // sem o reinício a rotação estrita daria outra coisa em agosto: o reinício é mesmo necessário
        $this->assertSame('ÍMPAR 8/8', Rotation::monthPattern([$this->d('2026-03-30')], 2026, 8));
    }

    public function test_grupo_impar_com_reinicio_em_01_08(): void
    {
        $a = [$this->d('2026-03-31'), $this->d('2026-08-01')];
        // ROSALIA: ÍMPAR, PAR 7/8, PAR 7/8, ÍMPAR 8/7, ÍMPAR 8/8, ÍMPAR 8/8, PAR 7/8, PAR 7/8, ÍMPAR 8/7, ÍMPAR 8/8
        $this->assertSame(['ÍMPAR 8/8', 'PAR 7/8', 'PAR 7/8', 'ÍMPAR 8/7', 'ÍMPAR 8/8', 'ÍMPAR 8/8', 'PAR 7/8', 'PAR 7/8', 'ÍMPAR 8/7', 'ÍMPAR 8/8'], $this->labels($a, 3, 12));
    }

    public function test_dias_e_quinzenas_conferem_com_a_tabela_dias_escalas(): void
    {
        // aba DIAS-ESCALAS: mês de 28 dias PAR 7/7 e ÍMPAR 8/6; de 29 dias ÍMPAR 8/7; de 30 dias PAR 7/8 e ÍMPAR 8/7; de 31 dias ÍMPAR 8/8
        $this->assertSame('PAR 7/7', Rotation::monthPattern([$this->d('2026-02-02')], 2026, 2));
        $this->assertSame('ÍMPAR 8/6', Rotation::monthPattern([$this->d('2026-02-01')], 2026, 2));
        $this->assertSame('ÍMPAR 8/7', Rotation::monthPattern([$this->d('2028-02-01')], 2028, 2)); // 2028 é bissexto
        $this->assertSame('PAR 7/8', Rotation::monthPattern([$this->d('2026-04-02')], 2026, 4));
        $this->assertSame('ÍMPAR 8/8', Rotation::monthPattern([$this->d('2026-05-01')], 2026, 5));
    }

    public function test_admissao_no_meio_do_mes_so_conta_dias_a_partir_da_admissao(): void
    {
        $a = [$this->d('2026-10-01')];                                   // ÍMPAR em outubro
        $this->assertSame('ÍMPAR 8/8', Rotation::monthPattern($a, 2026, 10));
        $this->assertSame('ÍMPAR 2/8', Rotation::monthPattern($a, 2026, 10, $this->d('2026-10-12'))); // 13 e 15; 17..31
        $this->assertNull(Rotation::monthPattern($a, 2026, 10, $this->d('2026-11-01')));
    }

    public function test_parity_reconhece_os_rotulos_da_planilha(): void
    {
        $this->assertSame('ÍMPAR', Rotation::parity('ÍMPAR 8/7'));
        $this->assertSame('ÍMPAR', Rotation::parity('impar'));
        $this->assertSame('PAR', Rotation::parity('PAR 7/8'));
        $this->assertNull(Rotation::parity('5X2 2ª-6ª'));
        $this->assertNull(Rotation::parity(null));
    }

    /* ---------- importação do cadastro ---------- */

    private function emp(string $name, string $scale = '12X36', ?string $termination = null): Employee
    {
        return Employee::create(['name' => $name, 'team' => 'LIMPEZA', 'scale' => $scale, 'termination_date' => $termination, 'admission_date' => '2025-01-01']);
    }

    private function pat(Employee $e, string $month, string $pattern): void
    {
        ShiftPattern::create(['employee_id' => $e->id, 'employee_name' => $e->name, 'team' => 'LIMPEZA', 'schedule' => 'x', 'month' => $month, 'pattern' => $pattern]);
    }

    public function test_importa_ancoras_do_ultimo_padrao_cadastrado(): void
    {
        $par = $this->emp('PAR PESSOA');
        $imp = $this->emp('IMPAR PESSOA');
        $this->pat($par, '2026-11', 'ÍMPAR 8/7');          // mês antigo: não vale
        $this->pat($par, '2026-12', 'PAR 7/8');            // último: PAR → dia 2
        $this->pat($imp, '2026-12', 'ÍMPAR 8/8');          // ÍMPAR → dia 1
        $this->emp('CINCO POR DOIS', '5x2 seg-sex');       // não é 12x36
        $this->emp('DESLIGADO', '12X36', '2026-05-01');    // inativo
        $this->emp('SEM PADRAO');                          // 12x36 sem nenhum padrão

        $dry = Rotation::importAnchors(true);
        $this->assertSame(['criadas' => 2, 'ja_tinham' => 0, 'sem_padrao' => 1], $dry);
        $this->assertSame(0, RotationAnchor::count());      // simulação não grava

        $this->assertSame(['criadas' => 2, 'ja_tinham' => 0, 'sem_padrao' => 1], Rotation::importAnchors());
        $this->assertSame('2026-12-02', RotationAnchor::where('employee_id', $par->id)->value('work_date')->toDateString());
        $this->assertSame('2026-12-01', RotationAnchor::where('employee_id', $imp->id)->value('work_date')->toDateString());

        $this->assertSame(['criadas' => 0, 'ja_tinham' => 2, 'sem_padrao' => 1], Rotation::importAnchors()); // idempotente
        $this->assertSame(2, RotationAnchor::count());
    }

    /* ---------- geração do mês ---------- */

    public function test_gera_janeiro_continuando_a_rotacao_de_dezembro(): void
    {
        $par = $this->emp('PAR EM DEZ');
        $imp = $this->emp('IMPAR EM DEZ');
        $this->pat($par, '2026-12', 'PAR 7/8');
        $this->pat($imp, '2026-12', 'ÍMPAR 8/8');
        Rotation::importAnchors();

        $res = Rotation::generateMonth('2027-01');
        $this->assertSame(['criados' => 2, 'ja_existiam' => 0, 'sem_ancora' => 0], $res);

        // dezembro/2026 tem 31 dias: quem era PAR vira ÍMPAR em janeiro e vice-versa
        $this->assertSame('ÍMPAR 8/8', ShiftPattern::where(['employee_id' => $par->id, 'month' => '2027-01'])->value('pattern'));
        $this->assertSame('PAR 7/8', ShiftPattern::where(['employee_id' => $imp->id, 'month' => '2027-01'])->value('pattern'));

        // fevereiro/2027 (28 dias): continua dia sim, dia não a partir de janeiro
        Rotation::generateMonth('2027-02');
        $this->assertSame('ÍMPAR 8/6', ShiftPattern::where(['employee_id' => $imp->id, 'month' => '2027-02'])->value('pattern'));
    }

    public function test_geracao_nunca_sobrescreve_e_so_considera_ativos_12x36_com_ancora(): void
    {
        $a = $this->emp('JA TEM PADRAO');
        $b = $this->emp('SEM ANCORA');
        $this->emp('CINCO POR DOIS', '5x2 seg-sex');
        $this->emp('DESLIGADO', '12X36', '2026-05-01');
        $futuro = Employee::create(['name' => 'ADMITIDO DEPOIS', 'team' => 'LIMPEZA', 'scale' => '12X36', 'admission_date' => '2027-03-01']);
        RotationAnchor::create(['employee_id' => $a->id, 'employee_name' => $a->name, 'work_date' => '2027-01-01']);
        RotationAnchor::create(['employee_id' => $futuro->id, 'employee_name' => $futuro->name, 'work_date' => '2027-01-01']);
        $this->pat($a, '2027-01', 'PAR 1/1');     // lançado à mão: deve ser mantido

        $res = Rotation::generateMonth('2027-01');

        $this->assertSame(['criados' => 0, 'ja_existiam' => 1, 'sem_ancora' => 1], $res);
        $this->assertSame('PAR 1/1', ShiftPattern::where(['employee_id' => $a->id, 'month' => '2027-01'])->value('pattern'));
        $this->assertSame(0, ShiftPattern::where('employee_id', $b->id)->count());
        $this->assertSame(0, ShiftPattern::where('employee_id', $futuro->id)->count());
        $this->assertSame(1, ShiftPattern::where('month', '2027-01')->count());
    }

    public function test_padrao_gerado_alimenta_os_dias_de_vt_vr(): void
    {
        $this->actingAs(User::factory()->create());
        $e = $this->emp('FULANO');
        $e->update(['vt_benefit' => true, 'vr_benefit' => true, 'vt_tariff' => 5, 'vt_trips_per_day' => 2]);
        RotationAnchor::create(['employee_id' => $e->id, 'employee_name' => $e->name, 'work_date' => '2026-10-01']);
        Rotation::generateMonth('2026-10');                                  // ÍMPAR 8/8

        $this->post(route('benefits.generate'), ['period' => '2026-10-1Q']);
        $this->post(route('benefits.generate'), ['period' => '2026-10-2Q']);
        $this->assertSame(8, (int) BenefitEntry::where('period', '2026-10-1Q')->value('vt_days'));
        $this->assertSame(8, (int) BenefitEntry::where('period', '2026-10-2Q')->value('vt_days'));
    }

    /* ---------- comando e telas ---------- */

    public function test_comando_valida_argumentos_e_gera(): void
    {
        $e = $this->emp('FULANO');
        $this->pat($e, '2026-12', 'ÍMPAR 8/8');

        $this->assertSame(2, Artisan::call('hr:rotation'));                                  // sem opções
        $this->assertSame(2, Artisan::call('hr:rotation', ['--generate' => '2027-13']));     // mês inválido
        $this->assertSame(0, Artisan::call('hr:rotation', ['--import' => true, '--generate' => '2027-01']));
        $this->assertSame('PAR 7/8', ShiftPattern::where(['employee_id' => $e->id, 'month' => '2027-01'])->value('pattern'));
    }

    public function test_telas_importar_e_gerar(): void
    {
        $this->actingAs(User::factory()->create());
        $e = $this->emp('FULANO');
        $this->pat($e, '2026-12', 'ÍMPAR 8/8');

        $this->get(route('schedules.patterns'))->assertOk()->assertSee('Importar rodízio do cadastro')->assertSee('value="2027-01"', false);
        $this->post(route('schedules.rotation.import'))->assertSessionHas('ok');
        $this->post(route('schedules.rotation.generate'), ['month' => '2027-01'])->assertRedirect(route('schedules.patterns', ['month' => '2027-01']));
        $this->assertSame(1, ShiftPattern::where('month', '2027-01')->count());

        $this->post(route('schedules.rotation.generate'), ['month' => '2027-13'])->assertSessionHasErrors('month');
        $this->post(route('schedules.rotation.generate'), [])->assertSessionHasErrors(['month' => 'Escolha o mês.']);

        // reinício manual pela tela de cadastro (vale para o mês seguinte gerado)
        $this->post(route('m.store', 'rotation-anchors'), ['employee_name' => 'FULANO', 'work_date' => '2027-02-02', 'note' => 'reinício'])->assertSessionHasNoErrors();
        $this->post(route('m.store', 'rotation-anchors'), ['employee_name' => 'FULANO', 'work_date' => '2027-02-02'])->assertSessionHasErrors('employee_name'); // duplicado
        Rotation::generateMonth('2027-02');
        // fev/2027: dia 01 ainda vem da sequência antiga (ÍMPAR) e a âncora de 02/02 trabalha 02, 04 ... 28 => 8 + 7
        // (mesmo efeito do reinício de 02/08 na planilha, que lança "PAR 8/8")
        $this->assertSame('PAR 8/7', ShiftPattern::where(['employee_id' => $e->id, 'month' => '2027-02'])->value('pattern'));
    }

    public function test_tela_de_padroes_abre_sem_nenhum_padrao_cadastrado(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('schedules.patterns'))->assertOk();
    }
}
