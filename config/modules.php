<?php

/*
 * Módulos CRUD genéricos (uma entrada = uma aba da planilha).
 * Tipos de campo: text, textarea, date, time, number, money, select, bool, employee, relation, cid
 * Somente arrays/strings aqui, para permitir `php artisan config:cache`.
 */

$teams = ['SEGURANÇA', 'LIMPEZA', 'TERMINAL'];

return [

    'positions' => [
        'title' => 'Cargos e valores', 'icon' => '💼', 'model' => \App\Models\Position::class,
        'search' => ['name', 'group'], 'order' => ['name', 'asc'],
        'columns' => [
            ['name', 'Cargo'], ['group', 'Grupo'], ['salary', 'Salário', 'money'],
            ['vr_per_day', 'VR/dia', 'money'], ['staff_quota', 'Quadro previsto'], ['quota_note', 'Obs.'],
        ],
        'fields' => [
            ['name', 'Cargo', 'text', 'required' => true],
            ['group', 'Grupo', 'select', 'options' => ['Segurança', 'Limpeza']],
            ['salary', 'Salário', 'money'], ['vr_per_day', 'Vale-refeição por dia', 'money'],
            ['staff_quota', 'Quadro previsto (nº de vagas)', 'number'], ['quota_note', 'Observação', 'text'],
        ],
    ],

    'job-openings' => [
        'title' => 'Vagas', 'icon' => '📣', 'model' => \App\Models\JobOpening::class,
        'search' => ['job_title', 'leader', 'previous_employee', 'approved_candidate'], 'order' => ['number', 'desc'],
        'filters' => [['stage', 'Estágio', ['ABERTA', 'PAUSADA', 'CONTRATAÇÃO', 'FECHADA']], ['team', 'Equipe', $teams]],
        'columns' => [
            ['number', 'Nº'], ['job_title', 'Cargo'], ['team', 'Equipe'], ['leader', 'Líder'], ['reason', 'Motivo'],
            ['opened_at', 'Abertura', 'date'], ['stage', 'Estágio', 'badge'], ['closed_at', 'Fechamento', 'date'],
            ['approved_candidate', 'Aprovado'],
        ],
        'fields' => [
            ['number', 'Nº da vaga', 'number', 'required' => true],
            ['job_title', 'Cargo', 'text', 'required' => true, 'suggest' => 'positions'],
            ['team', 'Equipe', 'select', 'options' => $teams], ['leader', 'Líder', 'text'],
            ['reason', 'Motivo', 'select', 'options' => ['SUBSTITUIÇÃO', 'NOVA POSIÇÃO', 'REALOCAÇÃO']],
            ['previous_employee', 'Nome anterior (substituído)', 'employee'],
            ['opened_at', 'Abertura', 'date'],
            ['stage', 'Estágio', 'select', 'options' => ['ABERTA', 'PAUSADA', 'CONTRATAÇÃO', 'FECHADA']],
            ['closed_at', 'Fechamento', 'date'], ['salary', 'Salário', 'money'],
            ['period', 'Período', 'select', 'options' => ['DIURNO', 'NOTURNO', 'MANHÃ', 'TARDE']],
            ['schedule', 'Horário', 'text'], ['approved_candidate', 'Candidato aprovado', 'text'],
        ],
    ],

    'candidates' => [
        'title' => 'Candidatos', 'icon' => '🧑‍💼', 'model' => \App\Models\Candidate::class,
        'search' => ['name', 'contact', 'recruiter', 'notes'], 'order' => ['id', 'desc'], 'with' => ['jobOpening'],
        'filters' => [
            ['status', 'Status', ['1- ENTREVISTA', '4- PRÉ-APROVADO', '5- CONTRATADO', '6- REPROVADO', '7- DESISTENTE']],
            ['source', 'Fonte', ['CURRÍCULO', 'SINE', 'INFOJOBS', 'PRESENCIAL', 'WHATSAPP', 'INDEED', 'CATHO', 'ARQUIVO', 'BNE']],
        ],
        'columns' => [
            ['name', 'Candidato'], ['contact', 'Contato'], ['jobOpening.number', 'Vaga'], ['jobOpening.job_title', 'Cargo'],
            ['status', 'Status', 'badge'], ['recruiter', 'Responsável'], ['interview_date', 'Entrevista', 'date'],
            ['interview_accepted', 'Aceite'], ['source', 'Fonte'], ['notes', 'Obs.'],
        ],
        'fields' => [
            ['name', 'Candidato', 'text', 'required' => true], ['contact', 'Contato (WhatsApp)', 'text', 'mask' => 'phone'],
            ['job_opening_id', 'Vaga', 'relation', 'model' => \App\Models\JobOpening::class, 'label' => ['number', 'job_title', 'team'], 'order' => ['number', 'desc']],
            ['status', 'Status', 'select', 'options' => ['1- ENTREVISTA', '4- PRÉ-APROVADO', '5- CONTRATADO', '6- REPROVADO', '7- DESISTENTE']],
            ['message_sent', 'Mensagem enviada', 'bool'], ['recruiter', 'Responsável (ADM)', 'text'],
            ['interview_date', 'Data da entrevista', 'date'], ['interview_time', 'Hora da entrevista', 'time'],
            ['interview_accepted', 'Aceite da entrevista', 'select', 'options' => ['SIM', 'NÃO', 'OMISSO', 'REAGENDAR']],
            ['referral', 'Indicação', 'text'],
            ['source', 'Fonte', 'select', 'options' => ['CURRÍCULO', 'SINE', 'INFOJOBS', 'PRESENCIAL', 'WHATSAPP', 'INDEED', 'CATHO', 'ARQUIVO', 'BNE']],
            ['notes', 'Observações', 'textarea'],
            ['invite_message', 'Mensagem de convite (WhatsApp)', 'textarea'],
            ['approved_message', 'Mensagem de aprovação', 'textarea'],
            ['rejected_message', 'Mensagem de reprovação', 'textarea'],
        ],
    ],

    'certificates' => [
        'title' => 'Atestados', 'icon' => '🩺', 'model' => \App\Models\MedicalCertificate::class,
        'search' => ['employee_name', 'cid', 'cid_description'], 'order' => ['start_date', 'desc'],
        'filters' => [['team', 'Equipe', $teams], ['type', 'Tipo', ['ATESTADO', 'DECLARACAO DE HORAS']], ['status', 'Status', ['LANÇADO', 'OK']]],
        'columns' => [
            ['employee_name', 'Colaborador'], ['team', 'Equipe'], ['type', 'Tipo'], ['start_date', 'Data', 'date'],
            ['days', 'Dias'], ['end_date', 'Término', 'date'], ['cid', 'CID'], ['cid_description', 'Descrição CID'],
            ['competence', 'Compt.', 'month'], ['status', 'Status', 'badge'],
        ],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true], ['team', 'Equipe', 'select', 'options' => $teams],
            ['type', 'Tipo', 'select', 'options' => ['ATESTADO', 'DECLARACAO DE HORAS', 'CERTIDÃO DE CASAMENTO']],
            ['start_date', 'Data', 'date', 'required' => true], ['cid', 'CID-10', 'cid'], ['cid_description', 'Descrição do CID', 'text'],
            ['days', 'Dias de afastamento', 'number'], ['entry_time', 'Entrada (declaração de horas)', 'time'],
            ['exit_time', 'Saída (declaração de horas)', 'time'],
            ['form', 'Forma', 'select', 'options' => ['PRESENCIAL', 'TELEMEDICINA']], ['worked_note', 'Trabalhou? Nº de horas', 'text'],
            ['vt_discount', 'Desconto VT', 'text'], ['vr_discount', 'Desconto VR', 'text'], ['va_discount', 'Desconto VA assiduidade', 'text'],
            ['competence', 'Competência (1º dia do mês)', 'date'],
            ['status', 'Status', 'select', 'options' => ['LANÇADO', 'OK', 'VERIFICAR']],
        ],
    ],

    'occurrences' => [
        'title' => 'Ocorrências de ponto', 'icon' => '⏱️', 'model' => \App\Models\TimeOccurrence::class,
        'search' => ['employee_name', 'kind', 'reason'], 'order' => ['date', 'desc'],
        'filters' => [['team', 'Equipe', $teams], ['status', 'Status', ['LANÇADO', 'OK', 'NÃO LANÇADO']]],
        'columns' => [
            ['employee_name', 'Colaborador'], ['team', 'Equipe'], ['date', 'Data', 'date'], ['competence', 'Compt.', 'month'],
            ['kind', 'Tipo/Horário'], ['reason', 'Motivo'], ['status', 'Status', 'badge'],
        ],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true], ['team', 'Equipe', 'select', 'options' => $teams],
            ['date', 'Data', 'date', 'required' => true], ['competence', 'Competência (1º dia do mês)', 'date'],
            ['kind', 'Tipo / horário', 'text', 'suggest' => 'occurrence_kinds'], ['reason', 'Motivo', 'textarea'],
            ['status', 'Status', 'select', 'options' => ['LANÇADO', 'OK', 'NÃO LANÇADO']],
        ],
    ],

    'coverages' => [
        'title' => 'FT / Coberturas', 'icon' => '🔁', 'model' => \App\Models\Coverage::class,
        'search' => ['absent_name', 'cover_name'], 'order' => ['date', 'desc'],
        'filters' => [['team', 'Equipe', $teams], ['status', 'Status', ['PAGO', 'PENDENTE']]],
        'columns' => [
            ['absent_name', 'Colab. ausente'], ['date', 'Data', 'date'], ['cover_name', 'FT cobertura'],
            ['amount', 'Valor', 'money'], ['team', 'Equipe'], ['pay_date', 'Pagamento', 'date'],
            ['status', 'Status', 'badge'], ['finance_status', 'Financeiro', 'badge'],
        ],
        'fields' => [
            ['absent_name', 'Colaborador ausente', 'employee'], ['date', 'Data', 'date', 'required' => true],
            ['cover_name', 'Nome do FT (cobertura)', 'employee', 'required' => true], ['amount', 'Valor', 'money'],
            ['team', 'Equipe', 'select', 'options' => $teams], ['pay_date', 'Data de pagamento', 'date'],
            ['status', 'Status', 'select', 'options' => ['PENDENTE', 'PAGO']],
            ['finance_status', 'Financeiro', 'select', 'options' => ['PENDENTE', 'ENVIADO']],
        ],
    ],

    'attendance' => [
        'title' => 'Assiduidade (prêmios)', 'icon' => '🏅', 'model' => \App\Models\AttendanceBonus::class,
        'search' => ['employee_name'], 'order' => ['competence', 'desc'],
        'columns' => [['employee_name', 'Colaborador'], ['competence', 'Competência', 'month'], ['amount', 'Prêmio', 'money']],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true],
            ['competence', 'Competência (1º dia do mês)', 'date', 'required' => true],
            ['amount', 'Valor do prêmio', 'money', 'required' => true],
        ],
    ],

    'lockers' => [
        'title' => 'Armários', 'icon' => '🗄️', 'model' => \App\Models\Locker::class,
        'search' => ['occupant', 'group', 'section'], 'order' => ['slot', 'asc'],
        'filters' => [['group', 'Grupo', ['MASCULINO (seg/limp)', 'FEMININO (seg/limp)', 'TERMINAL']]],
        'columns' => [['slot', 'Nº'], ['group', 'Grupo'], ['section', 'Seção'], ['occupant', 'Ocupante'], ['team', 'Equipe'], ['occupied', 'Situação', 'badge']],
        'fields' => [
            ['group', 'Grupo', 'select', 'options' => ['MASCULINO (seg/limp)', 'FEMININO (seg/limp)', 'TERMINAL'], 'required' => true],
            ['section', 'Seção', 'text'], ['slot', 'Nº do slot', 'number', 'required' => true],
            ['occupant', 'Ocupante (vazio = disponível)', 'text'], ['team', 'Equipe', 'select', 'options' => $teams],
        ],
    ],

    'calendar' => [
        'title' => 'Calendário RH', 'icon' => '📅', 'model' => \App\Models\CalendarTask::class,
        'search' => ['title'], 'order' => ['day', 'asc'],
        'filters' => [['area', 'Área', ['RH', 'Financeiro']]],
        'columns' => [['day', 'Dia do mês'], ['area', 'Área', 'badge'], ['title', 'Tarefa']],
        'fields' => [
            ['day', 'Dia do mês (1–31)', 'number', 'required' => true],
            ['area', 'Área', 'select', 'options' => ['RH', 'Financeiro'], 'required' => true],
            ['title', 'Tarefa', 'text', 'required' => true],
        ],
    ],

    'payroll' => [
        'title' => 'Apontamento de folha', 'icon' => '🧾', 'model' => \App\Models\PayrollEntry::class,
        'search' => ['employee_name', 'job_title', 'notes'], 'order' => ['competence', 'desc'],
        'filters' => [['competence', 'Competência', '@months'], ['team', 'Equipe', ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']]],
        'columns' => [
            ['employee_name', 'Colaborador'], ['team', 'Equipe'], ['competence', 'Compt.', 'month'], ['job_title', 'Cargo'],
            ['night_hours', 'H. noturnas'], ['absence_days', 'Faltas (dias)'], ['partial_absence_hours', 'Faltas parc.'],
            ['overtime_50', 'HE 50%'], ['overtime_100', 'HE 100%'], ['meal_voucher', 'VR', 'money'], ['transport_voucher', 'VT', 'money'],
            ['food_voucher', 'VA', 'money'], ['assiduity_bonus', 'Prêmio assid.', 'money'], ['total_va', 'Total VAs', 'money'],
            ['certificate_dates', 'Atestados'], ['absence_dates', 'Faltas'], ['notes', 'Obs.'],
        ],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true],
            ['team', 'Equipe', 'select', 'options' => ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']],
            ['competence', 'Competência (1º dia do mês)', 'date', 'required' => true], ['job_title', 'Cargo', 'text', 'suggest' => 'positions'],
            ['night_hours', 'Horas noturnas (h:mm)', 'text'], ['absence_days', 'Dias de falta', 'number'],
            ['partial_absence_hours', 'Horas de falta parcial (h:mm)', 'text'], ['overtime_50', 'Horas extras 50% (h:mm)', 'text'],
            ['overtime_100', 'Horas extras 100% (h:mm)', 'text'], ['meal_voucher', 'Vale-refeição informado', 'money'],
            ['transport_voucher', 'Vale-transporte informado', 'money'], ['food_voucher', 'Vale-alimentação informado', 'money'],
            ['assiduity_bonus', 'Prêmio por assiduidade', 'money'], ['function_accumulation', 'Acúmulo de função', 'money'],
            ['total_va', 'Total de VAs (VA + prêmio)', 'money'], ['certificate_days', 'Atestados (dias de trabalho)', 'number'],
            ['certificate_dates', 'Datas dos atestados', 'text'], ['absence_dates', 'Datas das faltas', 'text'], ['notes', 'Observações', 'text'],
        ],
    ],

    'leadership' => [
        'title' => 'Liderança Terminal', 'icon' => '⭐', 'model' => \App\Models\LeadershipBonus::class,
        'search' => ['employee_name'], 'order' => ['competence', 'desc'],
        'filters' => [['competence', 'Competência', '@months']],
        'columns' => [
            ['employee_name', 'Colaborador'], ['job_title', 'Cargo'], ['slot', 'Posição'], ['shift', 'Turno'],
            ['competence', 'Compt.', 'month'], ['lead_days', 'Dias de liderança'], ['amount', 'Adicional (10%)', 'money'],
        ],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true], ['job_title', 'Cargo', 'text'], ['salary', 'Salário', 'money'],
            ['slot', 'Posição (Colab. A–L)', 'text'], ['shift', 'Turno', 'select', 'options' => ['1º', '2º']],
            ['competence', 'Competência (1º dia do mês)', 'date', 'required' => true], ['lead_days', 'Dias de liderança', 'number'],
            ['amount', 'Adicional de liderança (10%)', 'money'],
        ],
    ],

    'inconsistencies' => [
        'title' => 'Inconsistências de ponto', 'icon' => '❓', 'model' => \App\Models\Inconsistency::class,
        'search' => ['employee_name', 'occurrence', 'kind', 'doubt', 'answer'], 'order' => ['date', 'desc'],
        'filters' => [['team', 'Equipe', ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']], ['status', 'Status', '@values']],
        'columns' => [
            ['employee_name', 'Colaborador'], ['team', 'Equipe'], ['shift', 'Turno'], ['date', 'Data', 'date'],
            ['occurrence', 'Ocorrência'], ['kind', 'Tipo'], ['doubt', 'Dúvida'], ['answer', 'Resposta'], ['status', 'Status', 'badge'],
        ],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true], ['team', 'Equipe', 'select', 'options' => ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']],
            ['shift', 'Turno', 'text'], ['date', 'Data', 'date'], ['competence', 'Competência (1º dia do mês)', 'date'],
            ['occurrence', 'Ocorrência', 'text'], ['kind', 'Tipo', 'text'], ['doubt', 'Dúvida (confirmar com líder)', 'textarea'],
            ['notes', 'Observações', 'textarea'], ['answer', 'Resposta', 'textarea'], ['status', 'Status', 'select', 'options' => ['LANÇADO', 'PENDENTE']],
        ],
    ],

    'shift-patterns' => [
        'title' => 'Padrão mensal de escala', 'icon' => '🔄', 'model' => \App\Models\ShiftPattern::class,
        'search' => ['employee_name', 'pattern', 'schedule'], 'order' => ['month', 'desc'],
        'filters' => [['month', 'Mês', '@values'], ['team', 'Equipe', ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']]],
        'columns' => [['employee_name', 'Colaborador'], ['team', 'Equipe'], ['schedule', 'Horário'], ['month', 'Mês'], ['pattern', 'Padrão']],
        'fields' => [
            ['employee_name', 'Colaborador', 'employee', 'required' => true], ['team', 'Equipe', 'select', 'options' => ['SEGURANÇA', 'LIMPEZA', 'TERMINAL']],
            ['schedule', 'Horário', 'text'], ['month', 'Mês (AAAA-MM)', 'text', 'required' => true], ['pattern', 'Padrão (ex.: ÍMPAR 8/7)', 'text', 'required' => true],
        ],
    ],

    'pattern-assignments' => [
        'title' => 'Postos 12x36 (ímpar/par)', 'icon' => '📍', 'model' => \App\Models\PatternAssignment::class,
        'search' => ['position', 'odd_person', 'even_person'], 'order' => ['id', 'asc'],
        'columns' => [['position', 'Posto'], ['odd_person', 'Dias ímpares'], ['even_person', 'Dias pares']],
        'fields' => [['position', 'Posto', 'text', 'required' => true], ['odd_person', 'Dias ímpares', 'employee'], ['even_person', 'Dias pares', 'employee']],
    ],

    'schedule-slots' => [
        'title' => 'Posições do Terminal', 'icon' => '🧭', 'model' => \App\Models\ScheduleSlot::class,
        'search' => ['person', 'slot', 'job_title'], 'order' => ['shift', 'asc'],
        'filters' => [['shift', 'Turno', ['1', '2']]],
        'columns' => [['shift', 'Turno'], ['slot', 'Posição'], ['person', 'Colaborador'], ['job_title', 'Cargo']],
        'fields' => [
            ['shift', 'Turno (1 ou 2)', 'number', 'required' => true], ['slot', 'Posição (A–L, F1–F4)', 'text', 'required' => true],
            ['person', 'Colaborador', 'employee'], ['job_title', 'Cargo', 'text'],
        ],
    ],

    'work-shifts' => [
        'title' => 'Jornadas por cargo', 'icon' => '🕒', 'model' => \App\Models\WorkShift::class,
        'search' => ['name'], 'order' => ['id', 'asc'],
        'columns' => [['name', 'Jornada'], ['entry_time', 'Entrada'], ['exit_time', 'Saída'], ['hours_total', 'Expediente'], ['hours_worked', 'Horas trabalhadas (sem intervalo)']],
        'fields' => [
            ['name', 'Jornada / cargo', 'text', 'required' => true], ['entry_time', 'Entrada', 'time'], ['exit_time', 'Saída', 'time'],
            ['hours_total', 'Expediente (h:mm)', 'text'], ['hours_worked', 'Horas sem intervalo (h:mm)', 'text'],
        ],
    ],
];
