<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Âncoras do rodízio 12x36: uma data em que o colaborador trabalha. Dali em diante ele trabalha dia sim, dia não,
 * até existir uma âncora posterior (reinício manual, como o de 02/08/2026 na planilha). Só acrescenta tabela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotation_anchors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->date('work_date');                 // dia trabalhado que define a sequência
            $t->string('note')->nullable();        // ex.: "importado de 2026-12 (PAR)" ou "reinício a partir de 02/08"
            $t->timestamps();
            $t->unique(['employee_name', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotation_anchors');
    }
};
