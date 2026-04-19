# Layouts

## 3 layouts por perfil

### LayoutAdmin.vue
- Sidebar fixa (240px) com logo CheckOps + nav vertical
- Header com nome do usuário + botão logout
- Conteúdo com `<RouterView>`
- Cores: sidebar `var(--color-surface)`, borda `var(--color-border)`

### LayoutGestor.vue
- Mesmo padrão do Admin, sidebar mais enxuta
- Nav: Dashboard, Colaboradores, Rotinas, Validação

### LayoutColaborador.vue
- **Mobile-first** — sem sidebar
- Bottom navigation com 3 tabs: Rotinas, Histórico, Perfil
- Header simples com nome + setor
- Touch targets mínimo 44px

### LayoutAuth.vue
- Container centralizado vertical + horizontal
- Logo CheckOps (dourado no escuro)
- Card de formulário `var(--color-surface)`
- Sem nav

## Logo CheckOps
```vue
<!-- Usar em todos os layouts -->
<span style="color: var(--color-gold); font-weight: 600; letter-spacing: 0.05em">
  CHECK<span style="color: var(--color-text)">OPS</span>
</span>
```
