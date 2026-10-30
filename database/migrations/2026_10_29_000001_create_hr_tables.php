<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('short_name')->nullable()->unique();
            $t->string('cnpj')->nullable();
            $t->timestamps();
        });

        Schema::create('positions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('group')->nullable();               // Segurança / Limpeza
            $t->decimal('salary', 10, 2)->nullable();
            $t->decimal('vr_per_day', 8, 2)->nullable();
            $t->unsignedSmallInteger('staff_quota')->nullable(); // quadro previsto
            $t->string('quota_note')->nullable();
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('company')->nullable()->index();
            $t->string('team')->nullable()->index();
            $t->string('esocial_code')->nullable();
            $t->date('admission_date')->nullable();
            $t->decimal('salary', 10, 2)->nullable();
            $t->string('category')->nullable();
            $t->string('job_title')->nullable()->index();
            $t->string('cpf', 14)->nullable()->index();
            $t->date('aso_admission_date')->nullable();
            $t->date('termination_date')->nullable()->index();
            $t->string('termination_reason')->nullable();
            $t->date('aso_dismissal_date')->nullable();
            $t->string('cbo')->nullable();
            $t->string('function_description')->nullable();
            $t->string('work_schedule')->nullable();
            $t->string('scale')->nullable();
            $t->string('leader')->nullable();
            $t->string('sector')->nullable();
            $t->string('pis')->nullable();
            $t->string('rg')->nullable();
            $t->string('rg_uf', 2)->nullable();
            $t->string('rg_issuer')->nullable();
            $t->date('rg_issue_date')->nullable();
            $t->date('birth_date')->nullable();
            $t->string('birth_city')->nullable();
            $t->string('birth_uf', 2)->nullable();
            $t->string('birth_country')->nullable();
            $t->string('ctps')->nullable();
            $t->string('ctps_series')->nullable();
            $t->string('ctps_uf', 2)->nullable();
            $t->date('ctps_issue_date')->nullable();
            $t->string('sex', 1)->nullable();
            $t->string('marital_status')->nullable();
            $t->string('race')->nullable();
            $t->string('education')->nullable();
            $t->string('address')->nullable();
            $t->string('address_number')->nullable();
            $t->string('address_complement')->nullable();
            $t->string('district')->nullable();
            $t->string('zip', 9)->nullable();
            $t->string('city')->nullable();
            $t->string('state', 2)->nullable();
            $t->string('phone_alt')->nullable();
            $t->string('mobile')->nullable();
            $t->string('email')->nullable();
            $t->string('bank')->nullable();
            $t->string('pix_key')->nullable();
            $t->string('mother_name')->nullable();
            $t->string('father_name')->nullable();
            $t->string('uniform_shirt')->nullable();
            $t->string('uniform_pants')->nullable();
            $t->string('uniform_shoes')->nullable();
            $t->string('uniform_vest')->nullable();
            $t->string('uniform_gloves')->nullable();
            $t->boolean('vt_benefit')->default(false);
            $t->string('vt_company')->nullable();
            $t->decimal('vt_trips_per_day', 5, 2)->nullable();
            $t->decimal('vt_tariff', 8, 2)->nullable();
            $t->boolean('vr_benefit')->default(false);
            $t->decimal('vr_discount_value', 8, 2)->nullable();   // desconto VR por dia
            $t->string('benefit_count_type')->nullable();         // ex.: Dias alternados
            $t->date('vacation_start')->nullable();
            $t->date('vacation_end')->nullable();
            $t->json('aso_periodic')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_dependents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->date('birth_date')->nullable();
            $t->string('cpf', 14)->nullable();
            $t->string('relationship')->nullable();
            $t->timestamps();
        });

        Schema::create('job_openings', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('number')->unique();
            $t->string('job_title');
            $t->string('team')->nullable();
            $t->string('leader')->nullable();
            $t->string('reason')->nullable();
            $t->string('previous_employee')->nullable();
            $t->date('opened_at')->nullable();
            $t->string('stage')->nullable()->index();
            $t->date('closed_at')->nullable();
            $t->decimal('salary', 10, 2)->nullable();
            $t->string('period')->nullable();
            $t->string('schedule')->nullable();
            $t->string('approved_candidate')->nullable();
            $t->timestamps();
        });

        Schema::create('candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_opening_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name')->index();
            $t->string('contact')->nullable();
            $t->string('status')->nullable()->index();
            $t->boolean('message_sent')->default(false);
            $t->string('recruiter')->nullable();
            $t->date('interview_date')->nullable();
            $t->string('interview_time', 8)->nullable();
            $t->string('interview_accepted')->nullable();
            $t->string('referral')->nullable();
            $t->string('source')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('cids', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('description', 500);
        });

        Schema::create('medical_certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('team')->nullable();
            $t->string('type')->nullable();                // ATESTADO / DECLARACAO DE HORAS ...
            $t->date('start_date')->nullable()->index();
            $t->string('cid', 100)->nullable();
            $t->string('cid_description', 500)->nullable();
            $t->unsignedSmallInteger('days')->nullable();
            $t->string('entry_time', 30)->nullable();
            $t->string('exit_time', 30)->nullable();
            $t->string('form')->nullable();                // PRESENCIAL / TELEMEDICINA
            $t->text('worked_note')->nullable();
            $t->string('vt_discount')->nullable();
            $t->string('vr_discount')->nullable();
            $t->string('va_discount')->nullable();
            $t->date('competence')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
        });

        Schema::create('time_occurrences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name')->index();
            $t->string('team')->nullable();
            $t->date('date')->nullable()->index();
            $t->date('competence')->nullable();
            $t->string('kind')->nullable();
            $t->text('reason')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
        });

        Schema::create('coverages', function (Blueprint $t) {
            $t->id();
            $t->string('absent_name')->nullable();
            $t->date('date')->nullable();
            $t->string('cover_name');
            $t->decimal('amount', 10, 2)->nullable();
            $t->string('team')->nullable();
            $t->date('pay_date')->nullable();
            $t->string('status')->nullable();
            $t->string('finance_status')->nullable();
            $t->timestamps();
        });

        Schema::create('attendance_bonuses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employee_name');
            $t->date('competence');
            $t->decimal('amount', 8, 2)->default(0);
            $t->timestamps();
            $t->unique(['employee_name', 'competence']);
        });

        Schema::create('lockers', function (Blueprint $t) {
            $t->id();
            $t->string('group');
            $t->string('section')->nullable();
            $t->unsignedSmallInteger('slot');
            $t->string('occupant')->nullable();
            $t->string('team')->nullable();
            $t->timestamps();
        });

        Schema::create('calendar_tasks', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('day');
            $t->string('area');
            $t->string('title');
            $t->timestamps();
        });

        Schema::create('schedule_entries', function (Blueprint $t) {
            $t->id();
            $t->date('date');
            $t->unsignedTinyInteger('shift');
            $t->string('slot', 20);
            $t->string('person')->nullable();
            $t->boolean('leader_rotation')->default(false);
            $t->timestamps();
            $t->index(['date', 'shift']);
        });

        Schema::create('benefit_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('period', 12);                       // 2026-11-1Q | 2026-11-2Q
            $t->decimal('vt_days', 5, 1)->default(0);
            $t->decimal('vr_days', 5, 1)->default(0);
            $t->decimal('adjustment_plus', 8, 2)->default(0);
            $t->decimal('adjustment_minus', 8, 2)->default(0);
            $t->string('reason')->nullable();
            $t->timestamps();
            $t->unique(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        foreach (['benefit_entries','schedule_entries','calendar_tasks','lockers','attendance_bonuses','coverages',
                  'time_occurrences','medical_certificates','cids','candidates','job_openings','employee_dependents',
                  'employees','settings','positions','companies'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
