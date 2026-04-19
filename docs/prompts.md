# Templates de Prompt — Claude Code

Como usar: copie o template, substitua os `[COLCHETES]` e cole no Claude Code.
O Claude já tem o contexto do projeto pelos CLAUDE.md — não precisa explicar a stack.

---

## BACKEND — Laravel

### Criar endpoint completo
```
Cria o endpoint [GET|POST|PUT|DELETE] /[perfil]/[recurso] para o perfil [admin|gestor|colaborador].

Gera todos os arquivos necessários:
- [NomeRecurso]Controller (em app/Http/Controllers/[perfil]/)
- Store|Update[NomeRecurso]Request (FormRequest com validações)
- [NomeRecurso]Resource (campos a retornar)
- Policy: adiciona o método [view|create|update|delete] na [NomeRecurso]Policy
- Rota em routes/api.php no grupo do perfil correto

Consulta o schema via MCP postgres antes de criar.
Segue o padrão de Controllers/CLAUDE.md.
```

---

### Criar migration
```
Cria uma migration para [descrição do que precisa].

Consulta via MCP postgres:
- Schema atual da tabela [nome_tabela]
- Índices existentes

Nomeia o arquivo: [timestamp]_[descricao_snake_case].php
Inclui rollback no método down().
```

---

### Criar Command do scheduler
```
Cria o Artisan Command [NomeCommand] com signature [nome:acao].

Função: [descreva o que o command deve fazer]
Horário no scheduler: [00:01 | 23:55 | everyThirtyMinutes | etc]

Segue o padrão de Console/Commands/CLAUDE.md.
Injeta os Services necessários via constructor.
Loga o resultado no final (total de registros afetados).
Registra no Kernel em app/Console/Kernel.php.
```

---

### Criar Service
```
Cria o [NomeService] em app/Services/.

Métodos necessários:
- [nomeMetodo(params)]: [descrição do que faz]
- [nomeMetodo(params)]: [descrição do que faz]

Segue o padrão de Services/CLAUDE.md.
Usa AuditableTrait onde modificar dados.
Lança BusinessException para erros de negócio.
```

---

### Criar Job
```
Cria o Job [NomeJob] em app/Jobs/.

Função: [descrição]
Fila: [default|notifications|reports]
Payload: [lista os dados que o Job recebe]

Segue o padrão de Jobs/CLAUDE.md.
Implementa ShouldQueue.
Trata falhas com try/catch e Log::error.
```

---

### Debug de query lenta
```
Analisa esta query/endpoint que está lento:
[cole a query ou o método do controller]

Consulta via MCP postgres os índices existentes nas tabelas envolvidas.
Sugere índices faltantes e reescreve a query com Eloquent se necessário.
```

---

## FRONTEND — Vue + PrimeVue

### Criar página completa
```
Cria a página [NomePagina].vue em src/pages/[admin|gestor|colaborador]/.

Dados: busca via [nomeService.metodo()] 
Layout: [LayoutAdmin|LayoutGestor|LayoutColaborador]
Componentes PrimeVue: [DataTable|DataView|Card|Dialog — o que fizer sentido]

Cria também o composable use[NomePagina].js em src/composables/ 
com a lógica de busca e estado.

Segue identidade visual de frontend/CLAUDE.md (tokens de cor).
Responsivo: funciona em 375px e 1280px.
```

---

### Criar componente reutilizável
```
Cria o componente [NomeComponente].vue em src/components/[ui|shared]/.

Props: [liste as props com tipos e defaults]
Emits: [liste os eventos emitidos]
Baseado em PrimeVue [nome do componente base, se houver]

Usa tokens CSS de frontend/CLAUDE.md — nunca hex hardcoded.
Segue o padrão de components/CLAUDE.md.
```

---

### Criar store Pinia
```
Cria a store [nome].store.js em src/stores/.

Estado: [liste os campos]
Actions: [liste as actions com o que cada uma faz]
Getters: [liste os getters necessários]

Usa [nomeService] para chamadas de API — nunca axios direto na store.
Segue o padrão de stores/CLAUDE.md.
Persiste [campo] no localStorage se necessário.
```

---

### Criar fluxo completo (página + composable + service)
```
Implementa a história [US-XX] do SPRINTS.md.

Gera:
1. Página: src/pages/[perfil]/[NomePagina].vue
2. Composable: src/composables/use[Nome].js
3. Adição em service: src/services/[recurso].service.js

Consulta docs/api-contracts.md para o contrato da API.
Segue identidade visual de frontend/CLAUDE.md.
Mobile-first (layout colaborador usa bottom nav).
```

---

## FULLSTACK — Back + Front juntos

### Implementar história completa
```
Implementa a história [US-XX]: [título da história]

BACKEND:
- Controller, FormRequest, Resource, Policy, rota
- Consulta schema via MCP postgres

FRONTEND:
- Página, composable, adição no service
- Consulta docs/api-contracts.md para o contrato

Critérios de aceite (do SPRINTS.md):
[cole os critérios da história aqui]

Começa pelo backend, depois o frontend.
```

---

### Revisar antes do PR
```
Revisa os arquivos alterados nesta branch (usa MCP git para ver o diff).

Verifica para cada arquivo:
- Backend: FormRequest com todas as validações? Policy implementada? Auditoria registrada?
- Frontend: tokens CSS corretos? responsivo? erros tratados?
- Geral: segue os padrões dos CLAUDE.md relevantes?

Lista o que está faltando para bater o DoD do SPRINTS.md.
```

---

### Escrever mensagens de commit
```
Lê o git diff atual via MCP git.
Escreve mensagens de commit no padrão:

feat(escopo): descrição curta em português
fix(escopo): descrição
refactor(escopo): descrição

Escopos válidos: auth, empresa, setor, usuario, rotina, rotina-diaria, 
foto, dashboard, relatorio, auditoria, scheduler, pwa
```

---

## Dicas de uso

**Seja específico no escopo** — em vez de "cria o CRUD de rotinas", prefira
"cria o endpoint GET /admin/rotinas com paginação e filtro por setor_id".

**Mencione o arquivo de referência** quando quiser consistência:
"segue o mesmo padrão do AdminSetoresController que já existe"

**Use o MCP explicitamente** quando precisar de dados do banco:
"consulta via MCP postgres os índices da tabela rotinas_diarias antes de sugerir"

**Limite o escopo ao sprint** — se o Claude sugerir algo de sprint futuro, responda:
"isso é Sprint 3, estamos no Sprint 1, remove essa parte"
