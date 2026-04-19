# MCPs — Como usar no CheckOps

## Backend (.claude/mcp.json)
3 servidores ativos: `postgres`, `filesystem`, `git`

## Frontend (.claude/mcp.json)
2 servidores ativos: `filesystem`, `git`

---

## Pré-requisitos
Node.js 18+ instalado (para o `npx` funcionar).

---

## Ajustar a connection string do Postgres

Edite `backend/.claude/mcp.json` com suas credenciais reais:
```
postgresql://USUARIO:SENHA@HOST:PORTA/NOME_DO_BANCO
```

Exemplo produção local:
```
postgresql://postgres:senha123@localhost:5432/checkops_dev
```

---

## O que cada MCP faz no dia a dia

### postgres (só no backend)
Claude consulta o schema real do banco sem você precisar explicar.

**Exemplos de prompts que ficam mais baratos:**
- "cria a migration para adicionar o campo `turno` em `rotinas`"
- "que índices existem na tabela `rotinas_diarias`?"
- "cria um scope no model que filtre por empresa_id"

Sem esse MCP você precisaria colar o schema toda vez. Com ele, o Claude vê diretamente.

### filesystem (back e front)
Claude lê os arquivos do projeto antes de criar novos, garantindo consistência.

**Exemplos:**
- "cria um controller no mesmo padrão dos outros controllers existentes"
- "quais composables já existem? preciso de um novo para paginação"
- "veja o AdminRotinasController e cria o GestorRotinasController seguindo o mesmo padrão"

### git (back e front)
Claude vê diff, histórico e branches.

**Exemplos:**
- "o que mudou desde o último commit? escreve as mensagens"
- "estou na branch sprint-2, quais arquivos foram alterados?"
- "faz um resumo das mudanças para o PR"

---

## Ativar no VS Code

1. Instale a extensão **Claude Code** no VS Code
2. Abra a pasta `backend/` OU `frontend/` (não o monorepo inteiro)
3. O Claude Code detecta `.claude/mcp.json` automaticamente
4. Na primeira vez ele pede permissão para rodar cada servidor — aceite

> Abrir `backend/` e `frontend/` como workspaces separados no VS Code
> garante que cada projeto use seu próprio `mcp.json`.

---

## Verificar se os MCPs estão rodando

No Claude Code (VS Code), clique no ícone de ferramentas — deve aparecer:
- `postgres` → tools: query, list_tables, describe_table
- `filesystem` → tools: read_file, list_directory, search_files
- `git` → tools: git_log, git_diff, git_status

---

## Dica de economia de tokens

Com os MCPs ativos, **remova do seu prompt** qualquer coisa que o Claude
já pode ver sozinho:

| Não precisa mais falar | Porque o MCP já fornece |
|------------------------|------------------------|
| "a tabela rotinas tem os campos..." | postgres lê direto |
| "o controller de admin segue o padrão X..." | filesystem lê os existentes |
| "mudei os arquivos A, B e C..." | git vê o diff |
