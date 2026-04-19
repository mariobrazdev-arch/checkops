# API Contracts — CheckOps v1

Base URL: `/api/v1`
Auth: `Authorization: Bearer {token}` (Sanctum)

## Auth
```
POST /auth/login          → { token, user: UserResource }
POST /auth/logout         → 204
POST /auth/esqueci-senha  → 204
POST /auth/redefinir-senha → 204
GET  /auth/me             → UserResource
PUT  /auth/perfil         → UserResource
```

## UserResource
```json
{
  "id": "uuid",
  "nome": "string",
  "email": "string",
  "perfil": "admin|gestor|colaborador",
  "empresa_id": "uuid",
  "setor_id": "uuid|null",
  "status": "ativo|inativo"
}
```

## Admin — Rotinas
```
GET    /admin/rotinas           → paginated RotinaResource[]
POST   /admin/rotinas           → RotinaResource
PUT    /admin/rotinas/{id}      → RotinaResource
DELETE /admin/rotinas/{id}      → 204
GET    /admin/rotinas/{id}/preview → ProximasGeracoes[]
```

## Colaborador — Rotinas do dia
```
GET  /colaborador/rotinas/hoje         → RotinaDiariaResource[]
POST /colaborador/rotinas/{id}/sim     → RotinaDiariaResource
POST /colaborador/rotinas/{id}/nao     → RotinaDiariaResource
GET  /colaborador/rotinas/historico    → paginated (filtros: data_inicio, data_fim, status)
```

### POST /sim — body
```json
{
  "foto_base64": "data:image/jpeg;base64,...",
  "foto_timestamp": 1234567890000,
  "foto_device_id": "string",
  "foto_lat": -25.4284,
  "foto_lng": -49.2733
}
```

## RotinaDiariaResource
```json
{
  "id": "uuid",
  "rotina": { "id": "uuid", "titulo": "string", "foto_obrigatoria": true },
  "data": "2026-04-17",
  "status": "pendente|realizada|atrasada|nao_realizada",
  "horario_previsto": "09:00",
  "data_hora_resposta": "ISO8601|null",
  "justificativa": "string|null",
  "foto_url": "signed_url|null",
  "foto_lat": "decimal|null",
  "foto_lng": "decimal|null"
}
```

## Dashboard gestor
```
GET /gestor/dashboard?periodo=hoje|semana|mes&setor_id=uuid
→ {
    resumo: { total, concluidas, pendentes, nao_realizadas, atrasadas },
    conformidade_colaboradores: [{ colaborador, percentual }],
    rotinas_criticas: [{ rotina, falhas }],
    justificativas_frequentes: [{ texto, count }]
  }
```

## Paginação padrão
```json
{
  "data": [...],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 95 }
}
```
