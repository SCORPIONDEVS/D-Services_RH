<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tabelas de uma versão anterior do pacote (histórico/arquivo genérico) — substituídas pelas telas reais
        Schema::dropIfExists('archive_rows');
        Schema::dropIfExists('archive_sheets');
        Schema::dropIfExists('benefit_history');

        // Benefícios: histórico entra como lançamentos com os valores originais preservados
        Schema::table('benefit_entries', function (Blueprint $t) {
            $t->decimal('vt_amount', 10, 2)->nullable()->after('vr_days');   // valor fixado (histórico); null = calcular
            $t->decimal('vr_amount', 10, 2)->nullable()->after('vt_amount');
            $t->decimal('adjustment_vt', 10, 2)->default(0)->after('adjustment_minus');
            $t->decimal('adjustment_vr', 10, 2)->default(0)->after('adjustment_vt');
            $t->decimal('correction_vt', 10, 2)->default(0)->after('adjustment_vr');
            $t->decimal('correction_vr', 10, 2)->default(0)->after('correction_vt');
            $t->string('correction_reason')->nullable()->after('reason');
            $t->text('extra')->nullable();                                   // JSON: dias corretos, diferenças etc.
        });

        // Candidatos: mensagens de WhatsApp geradas na planilha
        Schema::table('candidates', function (Blueprint $t) {
            $t->text('invite_message')->nullable();
            $t->text('approved_message')->nullable();
            $t->text('rejected_message')->nullable();
        });

        Schema::create('payroll_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('team')->nullable();
            $t->date('competence')->index();
            $t->string('job_title')->nullable();
            $t->string('night_hours', 12)->nullable();
            $t->decimal('absence_days', 5, 1)->nullable();
            $t->string('partial_absence_hours', 12)->nullable();
            $t->string('overtime_50', 12)->nullable();
            $t->string('overtime_100', 12)->nullable();
            $t->decimal('meal_voucher', 10, 2)->nullable();
            $t->decimal('transport_voucher', 10, 2)->nullable();
            $t->decimal('food_voucher', 10, 2)->nullable();
            $t->decimal('assiduity_bonus', 10, 2)->nullable();
            $t->decimal('function_accumulation', 10, 2)->nullable();
            $t->decimal('total_va', 10, 2)->nullable();
            $t->decimal('certificate_days', 5, 1)->nullable();
            $t->string('certificate_dates')->nullable();
            $t->string('absence_dates')->nullable();
            $t->string('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('leadership_bonuses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('job_title')->nullable();
            $t->decimal('salary', 10, 2)->nullable();
            $t->string('slot', 20)->nullable();
            $t->string('shift', 10)->nullable();
            $t->date('competence');
            $t->decimal('lead_days', 5, 1)->nullable();
            $t->decimal('amount', 10, 2)->nullable();       // 10% do salário proporcional aos dias de liderança
            $t->timestamps();
        });

        Schema::create('inconsistencies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('team')->nullable();
            $t->string('shift', 30)->nullable();
            $t->date('date')->nullable();
            $t->date('competence')->nullable();
            $t->string('occurrence')->nullable();
            $t->string('kind')->nullable();
            $t->text('doubt')->nullable();
            $t->text('notes')->nullable();
            $t->text('answer')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
        });

        Schema::create('shift_patterns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('team')->nullable();
            $t->string('schedule')->nullable();
            $t->string('month', 7)->index();                // YYYY-MM
            $t->string('pattern', 40);                      // ex.: ÍMPAR 8/7
            $t->timestamps();
        });

        Schema::create('pattern_assignments', function (Blueprint $t) {
            $t->id();
            $t->string('position');
            $t->string('odd_person')->nullable();
            $t->string('even_person')->nullable();
            $t->timestamps();
        });

        Schema::create('schedule_slots', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('shift');
            $t->string('slot', 20);
            $t->string('person')->nullable();
            $t->string('job_title')->nullable();
            $t->timestamps();
        });

        Schema::create('work_shifts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('entry_time', 8)->nullable();
            $t->string('exit_time', 8)->nullable();
            $t->string('hours_total', 8)->nullable();
            $t->string('hours_worked', 8)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['work_shifts', 'schedule_slots', 'pattern_assignments', 'shift_patterns', 'inconsistencies', 'leadership_bonuses', 'payroll_entries'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
        Schema::table('candidates', fn (Blueprint $t) => $t->dropColumn(['invite_message', 'approved_message', 'rejected_message']));
        Schema::table('benefit_entries', fn (Blueprint $t) => $t->dropColumn(['vt_amount', 'vr_amount', 'adjustment_vt', 'adjustment_vr', 'correction_vt', 'correction_vr', 'correction_reason', 'extra']));
    }
};
