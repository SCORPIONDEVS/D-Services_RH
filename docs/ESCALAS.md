# Escalas: Equipe → Escala → Jornada e rodízio 12x36

## Cascata no cadastro do funcionário
- **Equipe** define quais **Escalas** aparecem; a **Escala** define quais **Jornadas** aparecem (cadastro "Jornadas por cargo"); ao escolher a Jornada, o horário é preenchido.
- Trocar a Equipe limpa Escala e Jornada; trocar a Escala limpa a Jornada.
- O servidor valida a combinação ao salvar (mensagens em português). `work_schedule` é derivado (`06:00 às 18:00 - Jornada 12 x 36`).
- Gerencie os vínculos em **Escalas**: *Escalas*, *Equipe → Escala* e *Escala → Jornada* (o vínculo com Equipe vazia vale para qualquer equipe que use a escala).
- Funcionário com combinação antiga (texto livre que não casou) fica **pendente de revisão** e pode ser editado normalmente; a validação só passa a valer quando Equipe, Escala ou Jornada são alterados.

## Primeira instalação / migração dos dados
```bash
php artisan hr:backup-schedules                  # CSV + cópia do SQLite em storage/app/backups
php artisan migrate
php artisan db:seed --class=ScheduleLinksSeeder  # vínculos sugeridos: REVISE nas telas acima
php artisan hr:migrate-schedules --dry-run       # relatório dos pendentes, sem gravar
php artisan hr:migrate-schedules                 # vincula só com correspondência exata de horário + tipo
```
Nada é apagado nem reescrito: quem não casa recebe `schedule_review = true` e entra no relatório CSV.

## Rodízio 12x36 (dias ímpares/pares)
O colaborador trabalha **dia sim, dia não, sem reiniciar a cada mês**; por isso a paridade (ÍMPAR/PAR) troca depois de alguns meses de 31 dias. O padrão do mês é `ÍMPAR 8/7`: paridade + dias trabalhados na 1ª e na 2ª quinzena (é o que o cálculo de VT/VR lê).
- **Âncora** = uma data em que o colaborador trabalha; dali em diante vale dia sim, dia não, até existir uma âncora posterior. Um **reinício manual** (como 02/08/2026 na planilha) é só uma nova âncora, em *Escalas → Reinício do rodízio 12x36*.
- **Importar rodízio do cadastro** (tela *Padrão mensal 12x36*, ou `php artisan hr:rotation --import`): cria a âncora de cada ativo em 12X36 a partir do padrão mais recente já cadastrado (ÍMPAR → dia 1, PAR → dia 2). Quem já tem âncora não é alterado.
- **Gerar mês** (mesma tela, ou `php artisan hr:rotation --generate=2027-01`): cria o padrão do mês para ativos em 12X36 com âncora. **Nunca sobrescreve** padrão já lançado. Admissão no meio do mês só conta dias a partir da admissão.
- Regra conferida contra os 44 colaboradores da planilha (mar–dez/2026): todos os meses batem, incluindo o reinício de agosto.
