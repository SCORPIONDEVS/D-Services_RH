<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ShiftPattern;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\ScheduleLinks;
use Database\Seeders\ScheduleLinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ScheduleCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // cadastro "Jornadas por cargo" (aba HORAS) + vínculos sugeridos
        foreach ([
            ['FISCAL DIURNO', '07:00', '19:00'], ['FISCAL NOTURNO', '19:00', '07:00'],
            ['LIMPEZA PISO/BANHEIRO DIURNO', '06:00', '18:00'], ['LIMPEZA PRAÇA/BOULEVARD', '08:00', '20:00'],
            ['LIMPEZA NOTURNO', '18:00', '06:00'], ['LIMPEZA FOOD', '10:00', '22:00'], ['ZELADOR', '07:00', '17:00'],
            ['ORIENTADOR MANHÃ', '05:00', '13:30'], ['ORIENTADOR TARDE', '13:30', '22:00'],
        ] as [$n, $in, $out]) WorkShift::create(['name' => $n, 'entry_time' => $in, 'exit_time' => $out]);

        $this->seed(ScheduleLinksSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    private function shift(string $name): int { return WorkShift::where('name', $name)->value('id'); }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/backups'));
        parent::tearDown();
    }

    /* ---------- filtro em cascata ---------- */

    public function test_mapa_da_cascata_filtra_escalas_por_equipe_e_jornadas_por_escala_e_equipe(): void
    {
        $map = ScheduleLinks::cascadeMap();

        $this->assertSame(['12X36'], $map['teams']['LIMPEZA']);
        $this->assertEqualsCanonicalizing(['12X36', '5x2 seg-sex', '5X2 2ª-6ª', '6X1 2ª-Sáb'], $map['teams']['SEGURANÇA']);
        $this->assertEqualsCanonicalizing(['4x2', '5x2 seg-sex'], $map['teams']['TERMINAL']);

        // 12X36 existe em duas equipes: cada jornada fica presa à sua equipe
        $limpeza = collect($map['shifts']['12X36'])->where('team', 'LIMPEZA')->pluck('name')->all();
        $seguranca = collect($map['shifts']['12X36'])->where('team', 'SEGURANÇA')->pluck('name')->all();
        $this->assertContains('LIMPEZA FOOD', $limpeza);
        $this->assertNotContains('FISCAL DIURNO', $limpeza);
        $this->assertSame(['FISCAL DIURNO', 'FISCAL NOTURNO'], $seguranca);
        $this->assertSame('06:00 às 18:00 - Jornada 12 x 36', collect($map['shifts']['12X36'])->firstWhere('name', 'LIMPEZA PISO/BANHEIRO DIURNO')['label']);
    }

    public function test_formulario_embute_o_mapa_e_o_script_que_limpa_os_campos_filhos(): void
    {
        $html = $this->get(route('employees.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="team"', $html);
        $this->assertStringContainsString('id="scale"', $html);
        $this->assertStringContainsString('id="work_shift_id"', $html);
        $this->assertStringNotContainsString('name="work_schedule"', $html); // texto livre saiu do formulário
        $this->assertStringContainsString('"LIMPEZA":["12X36"]', $html);
        // trocar Equipe recarrega Escala e Jornada vazias; trocar Escala recarrega só a Jornada
        $this->assertMatchesRegularExpression("/team\.addEventListener\('change'.*?loadScales\(''\);\s*loadShifts\(''\);/s", $html);
        $this->assertMatchesRegularExpression("/scale\.addEventListener\('change'.*?loadShifts\(''\);/s", $html);
    }

    /* ---------- validação no servidor ---------- */

    public function test_combinacao_valida_salva_e_deriva_o_horario(): void
    {
        $this->post(route('employees.store'), [
            'name' => 'Alaide Teste', 'team' => 'LIMPEZA', 'scale' => '12X36', 'work_shift_id' => $this->shift('LIMPEZA PISO/BANHEIRO DIURNO'),
            'work_schedule' => 'TEXTO DIGITADO À MÃO', // ignorado: é derivado
        ])->assertSessionHasNoErrors();

        $e = Employee::where('name', 'ALAIDE TESTE')->firstOrFail();
        $this->assertSame('06:00 às 18:00 - Jornada 12 x 36', $e->work_schedule);
        $this->assertFalse((bool) $e->schedule_review);
    }

    public function test_servidor_recusa_escala_que_nao_pertence_a_equipe(): void
    {
        $this->post(route('employees.store'), ['name' => 'X', 'team' => 'LIMPEZA', 'scale' => '4x2'])
            ->assertSessionHasErrors(['scale' => 'A escala 4x2 não está disponível para a equipe LIMPEZA.']);
        $this->assertSame(0, Employee::count());
    }

    public function test_servidor_recusa_jornada_de_outra_escala_ou_de_outra_equipe(): void
    {
        // jornada do Terminal em escala 12X36
        $this->post(route('employees.store'), ['name' => 'X', 'team' => 'SEGURANÇA', 'scale' => '12X36', 'work_shift_id' => $this->shift('ORIENTADOR MANHÃ')])
            ->assertSessionHasErrors('work_shift_id');
        // 12X36 é válida para LIMPEZA, mas FISCAL é da SEGURANÇA
        $this->post(route('employees.store'), ['name' => 'X', 'team' => 'LIMPEZA', 'scale' => '12X36', 'work_shift_id' => $this->shift('FISCAL DIURNO')])
            ->assertSessionHasErrors('work_shift_id');
        // jornada sem escala / escala sem equipe
        $this->post(route('employees.store'), ['name' => 'X', 'team' => 'LIMPEZA', 'work_shift_id' => $this->shift('LIMPEZA FOOD')])
            ->assertSessionHasErrors(['work_shift_id' => 'Escolha a escala antes da jornada.']);
        $this->post(route('employees.store'), ['name' => 'X', 'scale' => '12X36'])
            ->assertSessionHasErrors(['scale' => 'Escolha a equipe antes da escala.']);

        $this->assertSame(0, Employee::count());
    }

    public function test_combinacao_legada_inalterada_nao_bloqueia_a_edicao_mas_mudar_para_invalida_sim(): void
    {
        $e = Employee::create(['name' => 'LEGADO', 'team' => 'LIMPEZA', 'scale' => '5x2 seg-sex', 'work_schedule' => '07:00 às 17:00 Seg a Sex', 'schedule_review' => true]);

        $this->put(route('employees.update', $e), ['name' => 'LEGADO', 'team' => 'LIMPEZA', 'scale' => '5x2 seg-sex', 'leader' => 'ROSALIA'])
            ->assertSessionHasNoErrors();
        $e->refresh();
        $this->assertSame('ROSALIA', $e->leader);
        $this->assertSame('07:00 às 17:00 Seg a Sex', $e->work_schedule); // texto legado preservado
        $this->assertTrue((bool) $e->schedule_review);

        $this->put(route('employees.update', $e), ['name' => 'LEGADO', 'team' => 'LIMPEZA', 'scale' => '4x2'])->assertSessionHasErrors('scale');
    }

    /* ---------- migração dos dados atuais ---------- */

    private function legacyEmployees(): void
    {
        foreach ([
            ['ALAIDE MARIA DO NASCIMENTO', 'LIMPEZA', '12X36', '06:00 às 18:00 - Jornada 12 x 36'],
            ['ORIENTADOR UM', 'TERMINAL', '4x2', '05:00 às 13:30 - Jornada 4 x 2'],
            ['SEGURANCA SEM JORNADA', 'SEGURANÇA', '12X36', '09:00 às 21:00 - Jornada 12 x 36'],   // horário não cadastrado
            ['SEIS POR UM', 'SEGURANÇA', '6X1 2ª-Sáb', '08:00 às 17:00 (Seg a Sex)/ 08:00 às 12:00 (Sab)'],
            ['COMBINACAO INVALIDA', 'TERMINAL', '12X36', '06:00 às 18:00 - Jornada 12 x 36'],
            ['TIPO DIVERGENTE', 'TERMINAL', '4x2', '05:00 às 13:30 - Jornada 5 x 2'],
        ] as [$n, $t, $s, $w]) Employee::create(['name' => $n, 'team' => $t, 'scale' => $s, 'work_schedule' => $w]);
    }

    public function test_dry_run_nao_grava_nada(): void
    {
        $this->legacyEmployees();
        Artisan::call('hr:migrate-schedules', ['--dry-run' => true]);

        $this->assertSame(0, Employee::whereNotNull('work_shift_id')->count());
        $this->assertSame(0, Employee::where('schedule_review', true)->count());
        $this->assertNotEmpty(glob(storage_path('app/backups/schedules-pending-*.csv')));
    }

    public function test_migracao_vincula_so_correspondencia_exata_marca_pendentes_e_nao_altera_dados(): void
    {
        $this->legacyEmployees();
        $before = Employee::orderBy('id')->get(['id', 'name', 'team', 'scale', 'work_schedule'])->toArray();

        Artisan::call('hr:migrate-schedules');

        // nada foi perdido nem reescrito
        $this->assertSame($before, Employee::orderBy('id')->get(['id', 'name', 'team', 'scale', 'work_schedule'])->toArray());

        $alaide = Employee::where('name', 'ALAIDE MARIA DO NASCIMENTO')->first();
        $this->assertSame($this->shift('LIMPEZA PISO/BANHEIRO DIURNO'), $alaide->work_shift_id);
        $this->assertFalse((bool) $alaide->schedule_review);
        $this->assertSame($this->shift('ORIENTADOR MANHÃ'), Employee::where('name', 'ORIENTADOR UM')->value('work_shift_id'));

        foreach (['SEGURANCA SEM JORNADA', 'SEIS POR UM', 'COMBINACAO INVALIDA', 'TIPO DIVERGENTE'] as $n) {
            $x = Employee::where('name', $n)->first();
            $this->assertNull($x->work_shift_id, $n);
            $this->assertTrue((bool) $x->schedule_review, $n);
        }

        $report = file_get_contents(glob(storage_path('app/backups/schedules-pending-*.csv'))[0]);
        $this->assertStringContainsString('SEGURANCA SEM JORNADA', $report);
        $this->assertStringContainsString('não vinculada à equipe TERMINAL', $report);
        $this->assertStringNotContainsString('ALAIDE', $report);
        $this->assertNotEmpty(glob(storage_path('app/backups/employees-schedules-*.csv'))); // backup feito antes

        // idempotente
        Artisan::call('hr:migrate-schedules');
        $this->assertSame(2, Employee::whereNotNull('work_shift_id')->count());
    }

    /* ---------- telas leem o horário do vínculo ---------- */

    public function test_padrao_mensal_mostra_o_horario_do_vinculo_e_nao_o_texto_livre(): void
    {
        $this->legacyEmployees();
        Artisan::call('hr:migrate-schedules');

        foreach (['ALAIDE MARIA DO NASCIMENTO' => 'ÍMPAR 8/7', 'SEGURANCA SEM JORNADA' => 'PAR 7/8'] as $nome => $pat) {
            ShiftPattern::create(['employee_name' => $nome, 'team' => Employee::where('name', $nome)->value('team'),
                'schedule' => $nome === 'ALAIDE MARIA DO NASCIMENTO' ? 'TEXTO ANTIGO DA PLANILHA' : '09:00 às 21:00 - Jornada 12 x 36', 'month' => '2026-10', 'pattern' => $pat]);
        }

        $html = $this->get(route('schedules.patterns', ['status' => 'todos']))->assertOk()->getContent();
        $this->assertStringContainsString('06:00 às 18:00 - Jornada 12 x 36', $html);   // vem do vínculo
        $this->assertStringNotContainsString('TEXTO ANTIGO DA PLANILHA', $html);
        $this->assertStringContainsString('09:00 às 21:00 - Jornada 12 x 36', $html);   // não migrado: texto legado como reserva

        $this->get(route('schedules.overview', ['status' => 'todos']))->assertOk()->assertSee('06:00 às 18:00');
    }

    /* ---------- tela de cadastro dos vínculos ---------- */

    public function test_tela_de_vinculos_lista_e_recusa_duplicado(): void
    {
        $this->get(route('m.index', 'scale-work-shifts'))->assertOk()->assertSee('FISCAL DIURNO');
        $this->get(route('m.index', 'team-scales'))->assertOk();

        $scaleId = \App\Models\Scale::where('name', '4x2')->value('id');
        $this->post(route('m.store', 'team-scales'), ['team' => 'TERMINAL', 'scale_id' => $scaleId])->assertSessionHasErrors('team');
        $this->post(route('m.store', 'team-scales'), ['team' => 'LIMPEZA', 'scale_id' => $scaleId])->assertSessionHasNoErrors();
        $this->assertContains('4x2', ScheduleLinks::scalesFor('LIMPEZA')); // passou a valer sem mexer em código
    }

    public function test_editar_o_rotulo_da_escala_reflete_no_horario_sem_reiniciar(): void
    {
        $e = Employee::create(['name' => 'FULANO', 'team' => 'LIMPEZA', 'scale' => '12X36', 'work_shift_id' => $this->shift('LIMPEZA FOOD')]);
        $this->assertSame('10:00 às 22:00 - Jornada 12 x 36', $e->fresh()->schedule_label);

        $scale = \App\Models\Scale::where('name', '12X36')->first();
        $this->put(route('m.update', ['scales', $scale->id]), ['name' => '12X36', 'label' => 'Jornada 12x36'])->assertSessionHasNoErrors();

        $this->assertSame('10:00 às 22:00 - Jornada 12x36', $e->fresh()->schedule_label);
    }
}
