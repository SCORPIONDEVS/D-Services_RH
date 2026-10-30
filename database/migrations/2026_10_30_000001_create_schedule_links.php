<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cascata Equipe -> Escala -> Jornada.
 * Só ACRESCENTA tabelas e colunas: employees.team, scale e work_schedule permanecem intactos (legado/derivado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scales', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();      // ex.: 12X36 (mesmo valor gravado em employees.scale)
            $t->string('label')->nullable();   // sufixo exibido no horário, ex.: "Jornada 12 x 36"
            $t->timestamps();
        });

        Schema::create('team_scale', function (Blueprint $t) {
            $t->id();
            $t->string('team');                // mesmo valor de employees.team (SEGURANÇA, LIMPEZA, TERMINAL)
            $t->foreignId('scale_id')->constrained('scales')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['team', 'scale_id']);
        });

        Schema::create('scale_work_shift', function (Blueprint $t) {
            $t->id();
            $t->foreignId('scale_id')->constrained('scales')->cascadeOnDelete();
            $t->foreignId('work_shift_id')->constrained('work_shifts')->cascadeOnDelete();
            // vazio = a jornada serve a qualquer equipe que use a escala; preenchido = só àquela equipe
            // (necessário porque 12X36 existe em LIMPEZA e SEGURANÇA com jornadas diferentes)
            $t->string('team')->nullable();
            $t->timestamps();
            $t->unique(['scale_id', 'work_shift_id', 'team']);
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('work_shift_id')->nullable()->constrained('work_shifts')->nullOnDelete();
            $t->boolean('schedule_review')->default(false); // "pendente de revisão"
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('work_shift_id');
            $t->dropColumn('schedule_review');
        });
        Schema::dropIfExists('scale_work_shift');
        Schema::dropIfExists('team_scale');
        Schema::dropIfExists('scales');
    }
};
