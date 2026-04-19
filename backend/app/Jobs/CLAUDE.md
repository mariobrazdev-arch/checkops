# Jobs (Filas Redis)

Operações assíncronas despachadas pelos Services ou Events.

## Jobs existentes

### EnviarPushNotification.php
**Fila:** `notifications`
**Disparo:** `NotificacaoService::pushPendente()`
```php
// Payload: user_id, titulo, corpo, url
// Usa: Web Push (vapid) via biblioteca webpush-php
// Fallback: EnviarEmailNotificacao se push falhar
```

### EnviarEmailNotificacao.php
**Fila:** `notifications`
```php
// Usa Laravel Mail + Mailable genérico
// Fallback de push ou notificação direta por e-mail
```

### GerarRelatorioCsv.php
**Fila:** `reports` (separada para não bloquear outras filas)
**Disparo:** `AdminRelatoriosController::exportar()`
```php
// Payload: filtros[], usuario_id (para notificar quando pronto)
// Gera CSV em chunks (evita memory overflow)
// Salva no S3, notifica usuário com link temporário
```

### VerificarFalhasRecorrentes.php
**Fila:** `default`
**Disparo:** Event `FalhaRegistrada` (ao fechar dia)
```php
// Verifica se rotina tem 3+ falhas consecutivas
// Se sim → dispara EnviarPushNotification para o gestor
```

## Padrão de despacho
```php
// No Service — nunca no Controller
GerarRelatorioCsv::dispatch($filtros, $user->id)->onQueue('reports');
EnviarPushNotification::dispatch($user, $titulo, $corpo)->delay(now()->addSeconds(5));
```

## Configuração de filas
```
QUEUE_CONNECTION=redis
// Filas: default, notifications, reports
// Supervisor: 3 workers para default, 2 para notifications, 1 para reports
```
