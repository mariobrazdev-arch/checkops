# Console Commands (Scheduler)

## Commands existentes

### GerarRotinasDodia.php
**Horário:** `00:01` diariamente
**Função:** Gera registros em `rotinas_diarias` para o dia atual
```
- Busca todas as rotinas ativas
- Filtra por frequência (diária sempre, semanal por dia_semana, mensal por dia_mes)
- Cria RotinaDiaria com status=pendente para cada rotina × colaborador do setor
- Ignora duplicatas (unique rotina_id + colaborador_id + data)
- Loga: "Geradas X rotinas para YYYY-MM-DD"
```

### MarcarAtrasadas.php
**Horário:** A cada 30 minutos (ou via evento)
**Função:** Muda `pendente → atrasada` após horário_previsto + 30min
```
- WHERE status=pendente AND data=hoje AND horario_previsto < NOW() - 30min
- Atualiza em batch
```

### MarcarNaoRealizadas.php
**Horário:** `23:55` diariamente
**Função:** Fecha o dia — muda pendentes/atrasadas para `nao_realizada`
```
- WHERE status IN (pendente, atrasada) AND data=hoje
- Cria registro com justificativa="Não respondida até o fechamento"
- Dispara evento FalhaRegistrada para verificar recorrência
- Loga: "Fechadas X rotinas não realizadas"
```

## Registro no Kernel (app/Console/Kernel.php)
```php
$schedule->command('rotinas:gerar')->dailyAt('00:01');
$schedule->command('rotinas:marcar-atrasadas')->everyThirtyMinutes();
$schedule->command('rotinas:fechar-dia')->dailyAt('23:55');
```

## Padrão de Command
```php
class GerarRotinasDodia extends Command
{
    protected $signature = 'rotinas:gerar {--data= : Data específica YYYY-MM-DD}';
    protected $description = 'Gera rotinas_diarias para o dia';

    public function handle(RotinaDiariaService $service): int
    {
        $data = $this->option('data') ? Carbon::parse($this->option('data')) : today();
        $total = $service->gerarDoDia($data);
        $this->info("Geradas {$total} rotinas para {$data->toDateString()}");
        return Command::SUCCESS;
    }
}
```
> `--data` permite rodar manualmente para uma data específica (útil em debug/reprocessamento)
