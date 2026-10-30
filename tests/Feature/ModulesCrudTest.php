<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Cria, edita e exclui um registro em cada módulo CRUD do config/modules.php. */
class ModulesCrudTest extends TestCase
{
    use RefreshDatabase;

    private function sample(array $f, array $relationIds): mixed
    {
        [$name, , $type] = $f;
        return match ($type) {
            'date' => '2026-10-15', 'time' => '08:00', 'number', 'money' => 5, 'bool' => 1,
            'select' => $f['options'][0] ?? null,
            'relation' => $relationIds[$f['model']] ?? null,
            'employee' => 'ALAIDE TESTE',
            'textarea' => 'texto',
            default => str_contains($name, 'month') || $name === 'competence' ? '2026-10' : 'TESTE',
        };
    }

    public function test_todos_os_modulos_criam_editam_e_excluem(): void
    {
        $this->actingAs(User::factory()->create());
        Employee::create(['name' => 'ALAIDE TESTE', 'team' => 'LIMPEZA']);

        $relationIds = [];
        foreach (config('modules') as $module => $cfg) {
            // cria dependências de relação antes (vaga -> candidato; escala/jornada -> vínculos)
            foreach ($cfg['fields'] as $f) {
                if ($f[2] === 'relation' && !isset($relationIds[$f['model']])) {
                    $dep = array_search($f['model'], array_column(array_map(fn ($c) => ['m' => $c['model']], config('modules')), 'm'), true);
                    $depModule = array_keys(config('modules'))[$dep];
                    $this->post(route('m.store', $depModule), $this->payload(config("modules.$depModule"), $relationIds));
                    $relationIds[$f['model']] = $f['model']::query()->value('id');
                }
            }

            $before = $cfg['model']::count();
            $this->post(route('m.store', $module), $this->payload($cfg, $relationIds))
                ->assertSessionHasNoErrors("store $module");
            $this->assertSame($before + 1, $cfg['model']::count(), "store $module");

            $row = $cfg['model']::query()->latest('id')->first();
            $this->get(route('m.edit', [$module, $row->id]))->assertOk();
            $this->put(route('m.update', [$module, $row->id]), $this->payload($cfg, $relationIds))->assertSessionHasNoErrors("update $module");
            $this->get(route('m.index', $module))->assertOk();
            $this->delete(route('m.destroy', [$module, $row->id]))->assertRedirect();
            $this->assertSame($before, $cfg['model']::count(), "destroy $module");
        }
    }

    private function payload(array $cfg, array $relationIds): array
    {
        $data = [];
        foreach ($cfg['fields'] as $f) $data[$f[0]] = $this->sample($f, $relationIds);
        return $data;
    }
}
