# Destaque de férias na lista de funcionários

Na lista de **Funcionários** (somente ativos):
- **Verde**: está em férias hoje (início e fim lançados no cadastro, com hoje entre os dois, inclusive).
- **Laranja**: férias **vencendo** (o prazo de gozo termina em até 90 dias) ou **vencidas** (prazo já passou). A etiqueta no nome diz qual dos casos e a data.

Regras
- Período aquisitivo = 12 meses a partir da admissão (termina um dia antes do aniversário); o gozo vai até 12 meses depois. Os períodos se repetem a cada ano.
- Só a última férias fica lançada no cadastro; férias com início **depois** do fim de um período contam como gozadas para ele e para os anteriores. Férias futuras já agendadas também tiram o alerta.
- Mantenha as datas de férias do cadastro atualizadas: sem isso, quem já tirou férias aparece como vencendo/vencida.
- A janela de 90 dias pode ser mudada gravando a configuração `vacation_alert_days` (tabela `settings`).
- Em férias tem prioridade sobre vencendo.
