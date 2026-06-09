# Roadmap dos módulos restantes

Este documento descreve o trabalho restante para concluir 100% do sistema. Todos os módulos abaixo seguem o **padrão estabelecido na Fase 4** (ver `docs/PADRAO-MODULO.md`).

## Visão geral

Já foram entregues **8 módulos completos** (Users, Roles, Colaboradores, Cargos, Departamentos, Classificações, Advertências, Atestados, Férias). Faltam **18 módulos** distribuídos em 4 grupos.

| Grupo | Módulos | Esforço estimado |
|-------|---------|------------------|
| Compras / Materiais | Fornecedores, Materiais, Grupos de Materiais, Pedidos de Compra | 8-10 dias |
| Patrimônio | Veículos, Manutenções, Transporte e Hospedagem | 5-7 dias |
| RH operacional | Documentação do Funcionário, Recrutamento (Vagas/Candidatos), Repúblicas | 6-8 dias |
| Administrativo | Obras, Alertas, Biblioteca, Compartilhamentos | 5-7 dias |
| **Total** | **18 módulos** | **24-32 dias úteis** |

---

## Grupo 1: Compras e Materiais

### Fornecedores

**Complexidade:** Média
**Estimativa:** 2 dias

Replica o padrão de Colaboradores (página de listagem + formulário + visualização), mas com menos campos e sem foto. CNPJ/CPF tem validação igual ao CPF (`app/Rules/Cpf.php` já existe; criar `Cnpj.php` análogo).

Campos importantes: pessoa física/jurídica, dados bancários (já no schema), homologação (flag), avaliação (1-5).

**Pontos de atenção:**
- Soft delete obrigatório (fornecedor inativo continua histórico de compras)
- Não permitir excluir se tem pedidos de compra associados
- Buscar por nome, CNPJ ou CPF
- Filtro por homologado/não-homologado

### Grupos de Materiais

**Complexidade:** Baixa
**Estimativa:** 1 dia

CRUD inline (modal), igual a Cargos. Permite hierarquia (grupo pai), igual a Departamentos — reaproveitar lógica anti-ciclo.

### Materiais

**Complexidade:** Média
**Estimativa:** 2 dias

Replica padrão de Cargos com mais campos. Destaque: controle de estoque.

**Campos especiais:**
- `unidade_medida` (UN, KG, M, L, CX, etc) — usar enum estrito
- `estoque_atual/minimo/maximo` (decimais 15,4 — precisão de 4 casas, pode haver fracionamento)
- `localizacao_estoque` (texto livre tipo "Almoxarifado A - Prateleira 3")

**Pontos de atenção:**
- Alerta quando estoque abaixo do mínimo (badge visual na listagem)
- Busca por nome e código (índice trigram já existe no schema)
- Histórico de movimentação é uma feature futura, não escopo desta fase

### Pedidos de Compra

**Complexidade:** ALTA (é o módulo mais complexo do sistema depois de Colaboradores)
**Estimativa:** 3-4 dias

Tem a máquina de estados mais elaborada do sistema:

```
rascunho
  → aguardando_liberacao
    → liberado (1ª aprovação)
      → aguardando_aprovacao
        → aprovado (2ª aprovação)
          → enviado_fornecedor
            → parcialmente_recebido
              → recebido
        → rejeitado
  → cancelado
```

**Estrutura:**
- Formulário em página própria (não modal) com **itens dinâmicos** (Livewire — adicionar/remover linhas de produto)
- Cálculo automático: subtotal por item, total geral, descontos, frete
- Histórico de aprovações registrado em tabela separada (`pedido_compra_aprovacoes` já no schema)
- Permissões granulares: `pedidos-compra.liberar` (1ª etapa), `pedidos-compra.aprovar` (2ª etapa)
- Recebimento parcial: cada item registra `quantidade_recebida` separadamente

**Reuso:**
- Workflow já implementado em Atestados serve de molde
- `DocumentoUploadService` para anexar notas fiscais
- Componentes `<x-modal>`, `<x-card>`, `<x-badge>` já prontos

**Recomendação:** dividir em vários Livewire menores: `Listar`, `Formulario`, `Visualizar`, `FluxoAprovacao` (componente embutido no Visualizar).

---

## Grupo 2: Patrimônio

### Veículos

**Complexidade:** Média
**Estimativa:** 2 dias

CRUD com alguns campos sensíveis (Renavam, Chassi, Apólice de seguro). Padrão similar a Colaboradores mas sem foto-dependência crítica.

**Validações úteis:**
- Placa: regex `/^[A-Z]{3}-?\d[A-Z\d]\d{2}$/` (aceita formato antigo AAA-1234 e Mercosul AAA1A23)
- Renavam: 9 a 11 dígitos numéricos
- Chassi: 17 caracteres (alfanumérico, sem I/O/Q)

**Cards especiais no dashboard:**
- Veículos com licenciamento vencendo nos próximos 30 dias
- Veículos em manutenção atualmente

### Manutenções

**Complexidade:** Baixa-Média
**Estimativa:** 1-2 dias

CRUD inline com filtros por veículo. Sempre vinculado a um veículo. Service `DocumentoUploadService` para anexar comprovantes/notas.

**Recurso interessante:**
- Ao registrar manutenção, atualizar o `km_atual` do veículo
- Próxima manutenção (data/km) gera lembrete no dashboard

### Transporte e Hospedagem

**Complexidade:** Baixa
**Estimativa:** 2 dias

CRUD direto. Schema já existe. Vinculado a colaborador e opcionalmente a obra.

---

## Grupo 3: RH Operacional

### Documentação do Funcionário

**Complexidade:** Baixa
**Estimativa:** 2 dias

Reuso direto do padrão de Atestados (upload + visualização). Diferença: é um repositório, sem workflow de aprovação. Apenas categorias (identificação, comprovante de residência, certidão, etc).

**Recurso útil:**
- Alerta de documentos vencendo (campo `data_validade` já no schema)

### Recrutamento (Vagas + Candidatos)

**Complexidade:** Média
**Estimativa:** 3 dias

Dois CRUDs relacionados.

**Vagas:** padrão similar a Colaboradores. Status (rascunho, aberta, em_seleção, preenchida, cancelada).

**Candidatos:** vinculado a uma vaga. Upload de currículo. Workflow simples (inscrito → triagem → entrevista → aprovado/rejeitado/contratado). Pontuação 0-100.

### Repúblicas

**Complexidade:** Baixa-Média
**Estimativa:** 2 dias

CRUD de repúblicas + gestão de ocupações (quem mora onde). Histórico de entrada/saída.

**Validação importante:** não permitir adicionar ocupante além da capacidade.

---

## Grupo 4: Administrativo

### Obras

**Complexidade:** Média
**Estimativa:** 2 dias

CRUD com campos de orçamento, datas previstas, responsável. Status (planejamento, em_andamento, pausada, concluída, cancelada).

### Alertas Administrativos

**Complexidade:** Baixa
**Estimativa:** 2 dias

CRUD de alertas + sistema de destinatários (N:N com colaboradores).

**Recurso interessante:**
- Banner persistente no topo de TODAS as páginas quando há alertas críticos não lidos do usuário corrente
- Marcar como visualizado ao clicar

### Biblioteca

**Complexidade:** Baixa-Média
**Estimativa:** 2 dias

Reuso direto de `DocumentoUploadService`. CRUD com categorização por áreas (N:N com `biblioteca_areas`). Contador de downloads.

### Compartilhamentos

**Complexidade:** Baixa
**Estimativa:** 1 dia

Mais simples que Biblioteca: upload livre + prazo de expiração. Job para limpar expirados (já temos suporte a queues via Redis).

---

## Features transversais ainda pendentes

Estes não são módulos, são funcionalidades que cruzam vários módulos:

### Exportação Excel (todos os listagens)

**Estimativa:** 2-3 dias (todos juntos)

`maatwebsite/excel` já está no `composer.json`. Criar um trait `Exportavel` que adiciona método `exportarExcel()` aos componentes Livewire de listagem. Reaplicar onde fizer sentido.

### Geração de PDFs

**Estimativa:** 2-3 dias

`barryvdh/laravel-dompdf` já está no `composer.json`. Documentos típicos:
- Ficha de colaborador completa
- Aviso de férias (modelo CLT)
- Termo de advertência
- Ordem de compra
- Comprovante de recebimento

### Tela de Auditoria

**Estimativa:** 1 dia

Listagem read-only do `activity_log` com filtros (por usuário, por modelo, por período). Permissão `audit.view-any`.

### Notificações por email

**Estimativa:** 2-3 dias

Eventos importantes do sistema:
- Atestado pendente de aprovação (notifica gestor RH)
- Férias aprovadas/rejeitadas (notifica colaborador)
- Pedido de compra em sua etapa (notifica aprovador)
- Documento próximo do vencimento

### Importação em massa de colaboradores

**Estimativa:** 1-2 dias

Upload de planilha XLSX, validação linha a linha, importação em batch com relatório de erros.

### Dashboard customizado por perfil

**Estimativa:** 2 dias

Hoje todos veem os mesmos cards (filtrados por permissão). Pode-se permitir cada usuário escolher quais cards quer ver, ordem, etc.

---

## Como replicar o padrão (resumo prático)

Para qualquer novo módulo, seguir 9 passos:

1. **Migration** já existe (todos no schema da Fase 1)
2. **Model** com `casts`, `scopes` de busca, accessors e `LogsActivity`
3. **Factory** com `fake('pt_BR')` para testes
4. **Policy** com `before()` para admin, métodos por ação
5. **Registrar policy** em `app/Providers/AppServiceProvider.php`
6. **Livewire component** (Listar + Formulario + Visualizar OU CRUD inline)
7. **View Blade** usando `<x-card>`, `<x-input>`, `<x-button>`, `<x-modal>`, `<x-badge>`
8. **Adicionar rotas** em `routes/web.php`
9. **Permissões** já estão criadas no `RolesAndPermissionsSeeder` (`modulo.acao`)
10. **Adicionar ao menu** em `app/View/Components/NavigationMenu.php`
11. **Testes** (factory + feature CRUD)

A maioria dos arquivos pode ser **copiada de um módulo similar** e ajustada. Cargos/Departamentos servem de molde para módulos simples; Colaboradores para complexos; Atestados para módulos com upload + aprovação.

---

## Ordem recomendada de implementação

Se o esforço fosse priorizado por **valor de negócio**, esta seria a ordem:

1. **Fornecedores + Materiais** (4 dias) — base do módulo de compras
2. **Pedidos de Compra** (3-4 dias) — workflow completo de compras
3. **Veículos + Manutenções** (3-4 dias) — controle de frota
4. **Obras** (2 dias) — vincular tudo (colaboradores, transporte) a projetos
5. **Documentação do Funcionário** (2 dias) — completa o RH digital
6. **Biblioteca + Compartilhamentos** (3 dias) — repositórios de arquivos
7. **Alertas Administrativos** (2 dias) — comunicação interna
8. **Recrutamento** (3 dias) — quando precisar contratar
9. **Repúblicas + Transporte** (4 dias) — para obras com colaboradores fora do município
10. **Features transversais** (exportação, PDFs, auditoria, emails) à medida que demandam

---

## Conclusão

O **núcleo do sistema está pronto**: autenticação, autorização, RH completo (colaboradores, cargos, departamentos, advertências, atestados, férias), geografia. O padrão arquitetural está estabelecido e validado.

Os módulos restantes são **trabalho de replicação**, não de design. Um desenvolvedor familiarizado com o padrão consegue produzir 1-2 módulos por semana.

**Estado em 27/05/2026:** ~55% concluído. Núcleo + RH + Geografia.
**Para 100%:** ~25-30 dias úteis adicionais de desenvolvimento.
