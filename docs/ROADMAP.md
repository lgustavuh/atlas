# Roadmap do Projeto

Plano completo de reconstrução do sistema Atlas.

## Princípios

1. **Segurança desde o dia 1** — não é "vamos arrumar depois"
2. **Pequenas entregas testáveis** — cada fase termina com algo rodando
3. **Padrões consistentes** — todos os módulos seguem a mesma estrutura
4. **Documentação inline** — código auto-explicativo + comentários onde necessário

---

## Fase 0 — Estrutura do Projeto ✅

**Status:** Concluída

**Entregas:**
- Docker Compose com PHP 8.3, PostgreSQL 16, Redis 7, Nginx, Mailpit
- Dockerfile customizado para a aplicação
- Configuração de PHP, Nginx e segurança HTTP
- `.env.example` com todas as variáveis
- `composer.json` e `package.json` com dependências escolhidas
- README com instruções de setup

---

## Fase 1 — Schema do Banco e Migrations ✅

**Status:** Concluída

**Objetivo:** Modelar todas as tabelas dos módulos principais com integridade referencial, índices apropriados e suporte a soft delete.

**Entregas:**
- Migrations para: usuários, perfis, permissões, colaboradores, cargos, localidades, departamentos, classificações, advertências, atestados, férias, alertas, fornecedores, materiais, pedidos de compra, veículos, etc.
- Foreign keys com `ON DELETE` apropriado
- Índices em campos de busca e foreign keys
- Soft delete onde apropriado (Colaborador, Fornecedor, etc — em vez do "Status='Desativado'" do legado)
- Auditoria automática (created_by, updated_by, deleted_by)
- Seeders mínimos: admin inicial, perfis padrão, países/estados/cidades do Brasil

**Diferenças importantes do legado:**
- Permissões deixam de ser ~30 colunas na tabela usuário e passam a ser registros relacionais (spatie/laravel-permission)
- "Status='Ativado'/'Desativado'" vira soft delete nativo do Laravel
- IDs em formato `bigint` em vez do legado misturado
- Senhas com hash Argon2id, nunca SHA-1

---

## Fase 2 — Autenticação e Autorização ✅

**Status:** Concluída

**Objetivo:** Sistema de login seguro, recuperação de senha, gestão de perfis e permissões granulares.

**Entregas:**
- Tela de login com rate limiting (5 tentativas/min)
- "Esqueci minha senha" com token expirando em 60min
- Verificação de email opcional
- Logout seguro (invalida sessão)
- Gestão de usuários (CRUD)
- Gestão de perfis (Admin, Gestor, RH, Visualizador, etc)
- Atribuição de permissões por perfil
- Middleware de autorização por módulo
- Tela de troca de senha (usuário logado)
- Política de senha forte (mínimo 12 caracteres, complexidade)

---

## Fase 3 — Layout Base e Dashboard ✅

**Status:** Concluída

**Objetivo:** Interface administrativa moderna, responsiva e acessível.

**Entregas:**
- Layout principal com sidebar colapsável
- Header com perfil do usuário e logout
- Breadcrumbs automáticos
- Sistema de notificações (toast)
- Dashboard com cards de resumo por módulo (respeitando permissões)
- Tema claro/escuro (opcional)
- Responsivo (mobile-first)
- Componentes reutilizáveis Livewire (tabela paginada, modal, formulário)

---

## Fase 4 — Módulo Colaboradores (REFERÊNCIA) ✅

**Status:** Concluída

**Objetivo:** Estabelecer o padrão de módulo que será replicado para todos os outros.

Este módulo é o mais complexo do sistema, com ~50 campos. Vai servir de exemplo do "jeito certo de fazer".

**Entregas:**
- Listagem com filtros (nome, CPF, cargo, status), paginação e busca
- Cadastro multi-step (Dados Pessoais → Endereço → Profissional → Documentos)
- Edição com histórico de alterações (activity log)
- Soft delete (desativar/reativar)
- Upload de foto com redimensionamento automático e validação real de tipo
- Validação de CPF, PIS, datas, etc
- Exportação para Excel
- Geração de ficha em PDF
- Form Requests dedicados
- Policies de autorização
- Testes automatizados (unit + feature)

---

## Fase 5 — Módulos Cargos + Localidade ✅

**Status:** Concluída

**Objetivo:** Aplicar o padrão estabelecido em módulos menores e relacionados ao Colaborador.

**Entregas:**
- CRUD de Cargos
- CRUD de Países / Estados / Cidades (com seeder pré-populado do Brasil)
- CRUD de Departamentos / Classificações
- Integração com módulo Colaborador (selects relacionados)

---

## Fase 6 — Módulos Advertências + Atestados ✅

**Status:** Concluída

**Objetivo:** Estabelecer o padrão de módulos com upload de arquivos e workflow de aprovação.

**Entregas:**
- CRUD de Advertências (com possibilidade de anexar documento)
- CRUD de Atestados (upload obrigatório do atestado)
- Validação real de arquivo (mime type real, não extensão)
- Armazenamento seguro fora do document root
- Download autorizado (verifica permissão antes de servir o arquivo)
- Histórico por colaborador

---

## Fase 7 — Férias + Roadmap dos demais módulos ✅

**Status:** Concluída

**Objetivo:** Módulo de Férias (fechando o ciclo principal de RH) + documento técnico final mostrando como replicar o padrão para os módulos restantes.

**Entregas:**
- Módulo Férias completo (workflow programada → aprovada → em_gozo → concluída)
- Regras CLT aplicadas (mínimo 5 dias gozo, máximo 30, abono até 10)
- Cálculo automático de período aquisitivo baseado em admissão
- Documento `docs/MODULOS-RESTANTES.md` com roadmap dos 18 módulos restantes

**Módulos a implementar (depois da Fase 7):**

### Grupo RH
- Férias
- Documentação do Funcionário
- Recrutamento e Seleção
- Lista de Cipeiros

### Grupo Compras/Materiais
- Fornecedores
- Materiais
- Grupos de Materiais
- Pedidos de Compra (workflow complexo: liberação → aprovação → entrega)

### Grupo Patrimônio
- Veículos
- Manutenção de Veículos
- Transporte e Hospedagem

### Grupo Suporte
- Repúblicas
- Obras
- Profissionais (externos)

### Grupo Admin
- Alertas Administrativos
- Logs de Modificações (já vem com activity log)
- Compartilhamento de Arquivos
- Documentos Padronizados / Biblioteca
- Busca Rápida (busca global no sistema)

---

## Estimativa de Esforço

Esses números assumem 1 desenvolvedor focado:

| Fase | Esforço |
|------|---------|
| 0    | ✅ Concluída |
| 1    | 2-3 dias |
| 2    | 3-5 dias |
| 3    | 2-3 dias |
| 4    | 5-7 dias (módulo de referência) |
| 5    | 2-3 dias |
| 6    | 3-4 dias |
| 7    | 1 dia (documento) |
| Demais módulos | ~2 dias cada × 25 módulos = ~50 dias |

**Total estimado:** ~70-80 dias úteis (3-4 meses).

---

## Decisões Técnicas Registradas

### Por que Laravel?
- Transição natural saindo de PHP procedural
- Ecossistema maduro no Brasil
- Eloquent elimina classe inteira de bugs (SQL Injection)
- Comunidade ativa, documentação excelente

### Por que PostgreSQL?
- Tipagem mais rigorosa que MySQL
- Suporte superior a JSON, full-text search
- `citext` para emails case-insensitive nativos
- Melhor para queries analíticas

### Por que Livewire em vez de Vue/React?
- Para um sistema interno administrativo, SPA é overhead
- Mantém a estatelessness do PHP, mais simples de raciocinar
- Sem necessidade de API REST separada
- Curva de aprendizado menor para quem vem de Blade

### Por que Docker?
- Ambiente idêntico entre dev/prod
- Setup do dev em uma máquina nova: ~10 minutos
- Sem "funciona na minha máquina"
- Deploy reproduzível

### Por que spatie/laravel-permission?
- Padrão de facto para autorização no Laravel
- Resolve elegantemente o problema das ~30 colunas `NivelXxx` do legado
- Suporte a perfis e permissões diretas
- Cache automático
