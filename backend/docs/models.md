# Models & Schema — CheckOps

## empresas
| campo | tipo | obs |
|-------|------|-----|
| id | uuid | PK |
| nome | string | |
| cnpj | string(18) | único |
| telefone | string | nullable |
| email | string | nullable |
| responsavel | string | nullable |
| status | enum(ativo,inativo) | default ativo |
| timestamps | | |

## users
| campo | tipo | obs |
|-------|------|-----|
| id | uuid | PK |
| empresa_id | uuid | FK |
| setor_id | uuid | FK nullable |
| gestor_id | uuid | FK nullable (self) |
| nome | string | |
| email | string | único por empresa |
| matricula | string | nullable |
| cargo | string | nullable |
| perfil | enum(admin,gestor,colaborador) | |
| status | enum(ativo,inativo) | |
| password | string | hashed |
| timestamps | | |

## setores
| campo | tipo | obs |
|-------|------|-----|
| id | uuid | PK |
| empresa_id | uuid | FK |
| gestor_id | uuid | FK users |
| nome | string | |
| descricao | text | nullable |
| status | enum(ativo,inativo) | |
| timestamps | | |

## rotinas
| campo | tipo | obs |
|-------|------|-----|
| id | uuid | PK |
| empresa_id | uuid | FK |
| setor_id | uuid | FK |
| titulo | string | |
| descricao | text | nullable |
| frequencia | enum(diaria,semanal,mensal,turno) | |
| dias_semana | json | nullable [0-6] |
| dias_mes | json | nullable [1-31] |
| horario_previsto | time | |
| foto_obrigatoria | boolean | default false |
| so_camera | boolean | default true |
| justif_obrigatoria | boolean | default false |
| status | enum(ativa,inativa) | |
| data_inicio | date | |
| data_fim | date | nullable |
| timestamps | | |

## rotinas_diarias
| campo | tipo | obs |
|-------|------|-----|
| id | uuid | PK |
| rotina_id | uuid | FK |
| colaborador_id | uuid | FK users |
| data | date | |
| status | enum(pendente,realizada,nao_realizada,atrasada) | |
| data_hora_resposta | timestamp | nullable |
| justificativa | text | nullable |
| foto_url | string | nullable (S3) |
| foto_lat | decimal(10,8) | nullable |
| foto_lng | decimal(11,8) | nullable |
| foto_timestamp | timestamp | nullable |
| foto_device_id | string | nullable |
| reaberta_por | uuid | nullable FK users |
| reaberta_justificativa | text | nullable |
| timestamps | | |
| UNIQUE | (rotina_id, colaborador_id, data) | |

## auditoria
| campo | tipo | obs |
|-------|------|-----|
| id | bigint | PK auto |
| empresa_id | uuid | FK |
| usuario_id | uuid | FK users |
| acao | enum(login,logout,criar,editar,excluir,responder,upload_foto,reabrir) | |
| entidade | string | ex: RotinaDiaria |
| entidade_id | uuid | nullable |
| dados_antes | json | nullable |
| dados_depois | json | nullable |
| ip | string | |
| created_at | timestamp | imutável — sem updated_at |

## Relacionamentos rápidos
- `Empresa` hasMany `User`, `Setor`, `Rotina`
- `User` belongsTo `Empresa`, `Setor`, `User(gestor)`
- `Setor` hasMany `Rotina`, `User`; belongsTo `User(gestor)`
- `Rotina` hasMany `RotinaDiaria`
- `RotinaDiaria` belongsTo `Rotina`, `User(colaborador)`
- `Auditoria` belongsTo `User`, `Empresa`
