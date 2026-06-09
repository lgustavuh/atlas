# Padrão de Módulo

Este documento descreve a arquitetura estabelecida na **Fase 4 (Colaboradores)** que deve ser replicada em todos os módulos futuros.

## Camadas de cada módulo

Todo módulo CRUD do sistema segue esta estrutura em camadas:

```
app/
├── Models/
│   └── Modulo.php                    # Eloquent + casts + scopes + accessors + activity log
├── Http/
│   ├── Requests/
│   │   └── ModuloRequest.php         # Regras de validação centralizadas
│   └── Controllers/
│       └── ModuloFotoController.php  # Apenas para servir arquivos privados
├── Livewire/
│   └── Modulo/
│       ├── Listar.php                # Listagem com filtros e paginação
│       ├── Formulario.php            # Criar/editar
│       └── Visualizar.php            # Visualização detalhada (read-only)
├── Policies/
│   └── ModuloPolicy.php              # Autorização granular por ação
├── Rules/
│   └── ValidacaoCustomizada.php      # Regras de domínio (CPF, PIS, CEP, etc)
└── Services/
    └── ModuloService.php             # Lógica de negócio, transações, arquivos
```

## Decisões obrigatórias

### 1. Soft delete em vez de "Status='Ativado'"
Sempre usar `softDeletesTz()` na migration e `use SoftDeletes` no model. Filtros: `onlyTrashed()`, `withTrashed()`, `ativos()`.

### 2. Auditoria automática
- Migration: `created_by`, `updated_by`, `deleted_by` (FK para users)
- Model: trait `LogsActivity` configurado com `logFillable()` + `logOnlyDirty()`
- Service preenche `created_by/updated_by` em toda gravação

### 3. Validação em três níveis
1. **Banco**: constraints (`CHECK`, `UNIQUE` parcial, `NOT NULL`)
2. **Rules de domínio** (`app/Rules/`): CPF, PIS, dígitos verificadores
3. **FormRequest**: orquestra as regras

### 4. Service Layer
Toda gravação que envolve mais de uma tabela (ou efeitos colaterais como upload) passa pelo Service, dentro de `DB::transaction()`. O Livewire **não** mexe direto no banco em operações complexas.

### 5. Upload de arquivos
- Validação por `mime` E `mimetypes` (não apenas extensão)
- Storage privado (`storage/app/local/private/`)
- Nome do arquivo = hash SHA-256 do conteúdo (sem revelar nome original ao público)
- Controller dedicado serve o arquivo verificando autorização

### 6. Policy + permissão granular
- Policy registrada no `AppServiceProvider`
- `before()` libera tudo para admin
- Permissões nomeadas `modulo.acao` (`colaboradores.create`, `colaboradores.view-salary`)
- Permissões cadastradas no `RolesAndPermissionsSeeder`

### 7. Componentes Livewire
- Sempre `#[Layout('layouts.app')]` e `#[Title(...)]`
- `mount()` faz a primeira verificação de autorização
- Cada ação verifica autorização específica
- Sucessos via `$this->dispatch('toast', ...)` (notificação visual)
- Filtros persistidos na URL via `#[Url]`

### 8. View pattern
- Header com título + descrição + botões de ação
- Card de filtros (quando aplicável)
- Card com tabela paginada (listagens)
- Modal para confirmações destrutivas
- Componentes reutilizáveis: `<x-card>`, `<x-input>`, `<x-button>`, `<x-modal>`, `<x-badge>`, `<x-alert>`

### 9. Testes obrigatórios
Cada módulo deve ter:
- **Unit**: regras de domínio customizadas (CPF, PIS, etc)
- **Feature**: bloqueio por permissão, CRUD básico, validação principal, soft delete, scopes de busca
- Factory que gera dados realistas (usar `fake('pt_BR')`)

## Como replicar para outro módulo

Para criar o módulo Fornecedores, por exemplo, basta:

1. Copiar `app/Livewire/Colaboradores/` → `app/Livewire/Fornecedores/`
2. Copiar `app/Http/Requests/ColaboradorRequest.php` → `FornecedorRequest.php`
3. Copiar `app/Services/ColaboradorService.php` → `FornecedorService.php`
4. Copiar `app/Policies/ColaboradorPolicy.php` → `FornecedorPolicy.php`
5. Ajustar os campos específicos do fornecedor (CNPJ, dados bancários, contato)
6. Adicionar permissões no `RolesAndPermissionsSeeder`
7. Registrar policy no `AppServiceProvider`
8. Adicionar rotas em `routes/web.php`
9. Adicionar item no menu em `app/View/Components/NavigationMenu.php`
10. Escrever os testes

Isso é o que será feito nas Fases 5-7 e seguintes.

## Anti-padrões a evitar

❌ **Lógica de negócio no Livewire component** — Deve ir no Service
❌ **Validação inline no save()** — Deve ir no FormRequest
❌ **Hard delete** — Sempre soft delete
❌ **Comparar permissões por número** (legado) — Usar `$user->can('permissao.nome')`
❌ **CRUD direto em raw SQL** — Sempre via Eloquent
❌ **Upload sem validação real de mime** — Sempre `mimetypes:` + `finfo`
❌ **Senha em texto** — Cast `'hashed'` no model
❌ **Acessar `$_POST`/`$_GET` direto** — Vir de FormRequest ou propriedades Livewire
