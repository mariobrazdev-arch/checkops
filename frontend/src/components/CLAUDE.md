# Components

## ui/ — componentes genéricos reutilizáveis
Wrappers e extensões de componentes PrimeVue com identidade CheckOps aplicada.

| Componente | Base PrimeVue | Uso |
|-----------|--------------|-----|
| AppButton.vue | Button | variantes: primary (dourado), ghost, danger |
| AppInput.vue | InputText | label flutuante + erro inline |
| AppBadgeStatus.vue | Tag | status de rotina com cor semântica |
| AppModal.vue | Dialog | modal padrão com header/footer |
| AppTable.vue | DataTable | tabela com paginação e loading |
| AppCard.vue | Card | card com surface escura |
| AppEmptyState.vue | — | estado vazio com ícone e mensagem |

### AppBadgeStatus — cores de status
```vue
<!-- status: pendente | realizada | atrasada | nao_realizada -->
<AppBadgeStatus :status="rotina.status" />
// pendente     → âmbar  (#F59E0B)
// realizada    → verde  (#22C55E)
// atrasada     → vermelho (#EF4444)
// nao_realizada → cinza (#6B7280)
```

## shared/ — componentes específicos do CheckOps
| Componente | Descrição |
|-----------|-----------|
| RotinaCard.vue | Card de rotina do dia (colaborador) — botões SIM/NÃO |
| RotinaDetalhe.vue | Modal de detalhe com foto, metadados, justificativa |
| ConformidadeChart.vue | Gráfico de barras horizontais (PrimeVue Chart) |
| FotoViewer.vue | Visualizador de foto com metadados GPS |
| JustificativaModal.vue | Modal de justificativa (mínimo 20 chars) |

## Regras
- Componentes `ui/` não conhecem o domínio CheckOps
- Componentes `shared/` podem usar stores e services
- Nunca importar axios diretamente num componente
