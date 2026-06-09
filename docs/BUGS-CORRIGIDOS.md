# Bugs corrigidos durante a Fase 9 (validação end-to-end)

Esta fase consistiu em colocar o sistema para rodar de verdade pela primeira vez (PHP 8.3 + PostgreSQL 16) e iterar até zerar os erros. Saímos de **42 falhas iniciais** para **0 falhas, 132 testes passando**.

Documento aqui os bugs encontrados para referência futura e para que ninguém repita os mesmos.

## Bugs do framework / setup

### B1. `reset()` é método reservado no Livewire

**Sintoma:** Fatal error ao subir a aplicação:
```
Declaration of App\Livewire\Auth\ResetPassword::reset(): void must be compatible
with Livewire\Component::reset(...$properties)
```

**Causa:** Criei um método `reset()` no componente `ResetPassword` para redefinir a senha, mas `reset()` é usado pelo Livewire para resetar propriedades do componente.

**Correção:** Renomeei para `redefinirSenha()` no componente, na view (`wire:submit`) e no teste (`->call('redefinirSenha')`).

### B2. Timezone do PostgreSQL ficava UTC mesmo com o PHP em America/Sao_Paulo

**Sintoma:** Usuários bloqueados conseguiam autenticar. O accessor `is_locked` retornava `false` mesmo com `locked_until` no futuro.

**Causa raiz:** O Laravel enviava timestamps sem informar o timezone na conexão. O PostgreSQL interpretava como UTC, mas o `now()` do PHP estava em SP. Resultado: cada timestamp ficava 3 horas no passado em relação ao que deveria.

**Correção:** Adicionar `'timezone' => 'America/Sao_Paulo'` na conexão `pgsql` em `config/database.php`:

```php
'pgsql' => [
    // ...
    'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
],
```

### B3. Diretórios criados por engano com expansão de chave

**Sintoma:** Existiam diretórios chamados literalmente `{app`, `storage/app/{public,private}`, `storage/framework/{views,cache`.

**Causa:** O shell do ambiente onde o projeto foi gerado não expande chaves (`{a,b}`). O comando `mkdir -p storage/app/{public,private}` criou um diretório com nome `{public,private}` em vez de dois diretórios separados.

**Correção:** Removidos os diretórios-lixo. **Lição:** sempre usar `mkdir` separado em vez de expansão de chaves quando o ambiente não garante suporte a essa sintaxe do bash.

## Bugs de modelo / fillable

### B4. `MassAssignmentException` no User

**Sintoma:** 16 testes falhando com:
```
Add fillable property [created_by, updated_by] to allow mass assignment on [App\Models\User]
```

**Causa:** O model `User` usava `$fillable` (lista branca) que não incluía `created_by`, `updated_by`, `last_login_at`, `last_login_ip`, `failed_login_attempts`, `locked_until`. Outros models usam `$guarded` (lista negra) e não tiveram esse problema.

**Correção:** Expandido o `$fillable` do User para incluir todos os campos de auditoria e segurança que de fato existem na migration.

### B5. `MassAssignmentException` com `foto` no Colaborador

**Sintoma:** Ao salvar Colaborador via Formulário:
```
Add [foto] to fillable property to allow mass assignment on [App\Models\Colaborador]
```

**Causa:** A regra de validação `'foto' => [...]` no `ColaboradorRequest` colocava o `UploadedFile` em `$validados['foto']`. Depois, o Service repassava para `Colaborador::create($dados)`, e o Eloquent reclamava porque `foto` não é coluna da tabela.

**Correção:** No `ColaboradorService::criar()` e `::atualizar()`, fazer `unset($dados['foto'])` antes do `create`/`update`. A foto vira `foto_path` via `processarFoto()`.

### B6. CHECK constraint violation com string vazia em enum nullable

**Sintoma:** Ao criar Colaborador sem preencher campos opcionais:
```
ERROR: new row violates check constraint "colaboradores_banco_tipo_conta_check"
```

**Causa:** O Formulário inicializa propriedades como `public string $banco_tipo_conta = ''`. Quando o usuário não preenche, vai como `''` para o banco. Mas o campo é `enum('corrente','poupanca','salario')` nullable: aceita esses 3 valores OU `null`, mas **não** `''`.

**Correção:** Adicionado helper `stringVaziaParaNull()` no `ColaboradorService` que converte todas as strings vazias para `null` antes de salvar.

## Bugs de validação

### B7. `Rule::unique` falha quando valor tem máscara

**Sintoma:** Ao criar segundo Fornecedor/Colaborador com mesmo CPF/CNPJ, o sistema dava `UniqueConstraintViolationException` em vez do erro de validação esperado.

**Causa:** O Livewire mantém o CPF/CNPJ com máscara (`111.444.777-35`). O banco armazena apenas dígitos (`11144477735`) via mutator. A regra `Rule::unique` compara strings literalmente, então não encontrava duplicata e o INSERT falhava no banco.

**Correção:** Limpar a máscara antes da validação no método `save()` / `salvar()`:
```php
$this->cpf = preg_replace('/[^0-9]/', '', $this->cpf) ?? '';
```

Aplicado em: `Fornecedores\Gerenciar`, `Colaboradores\Formulario` (CPF e PIS).

### B8. `diffInMonths()` retorna `float`, não `int`

**Sintoma:** ViewException ao visualizar Colaborador:
```
Return value must be of type ?int, float returned
```

**Causa:** Versões recentes do Carbon retornam `float` em `diffInMonths()`. O accessor `tempoEmpresaMeses` declarava `?int` como retorno.

**Correção:** Cast explícito para `int`:
```php
return (int) $this->data_admissao->diffInMonths($fim);
```

### B9. `addError + return` não funciona com `assertHasErrors`

**Sintoma:** Teste de Férias com `dias_gozo + dias_abono > 30` não capturava o erro.

**Causa:** O Livewire `addError()` adiciona ao bag local mas pode ser limpo em fluxos subsequentes. O padrão Laravel testável é lançar `ValidationException`.

**Correção:** Trocado por:
```php
throw ValidationException::withMessages([
    'dias_gozo' => "Total de dias (gozo + abono) excede 30. Atual: {$total}.",
]);
```

### B10. Hook `updated*` sobrescrevia input do usuário

**Sintoma:** Em Férias, quando o teste setava `dias_gozo=28` e depois `abono_pecuniario=true`, o `dias_gozo` voltava para 20 silenciosamente.

**Causa:** O hook `updatedAbonoPecuniario(true)` forçava `dias_gozo=20` e `dias_abono=10` cegamente.

**Correção:** Só sugerir valores padrão se os campos ainda estiverem no default (`30/0`):
```php
if ($value && $this->dias_gozo === 30 && (int) $this->dias_abono === 0) {
    $this->dias_abono = 10;
    $this->dias_gozo = 20;
}
```

**Lição secundária:** isso era um bug de UX, não só de teste. Em produção, o usuário que digitasse 25 dias e depois marcasse abono ia ver os 25 virarem 20 sem explicação.

## Bugs de configuração

### B11. Activity Log não gravava no Colaborador

**Sintoma:** Teste `registra activity log na criação` falhava com `count = 0`.

**Causa:** `getActivitylogOptions()` usava `->logFillable()`, mas o model `Colaborador` usa `$guarded` (não tem `$fillable`).

**Correção:** Trocado para `->logAll()`:
```php
return LogOptions::defaults()
    ->logAll()
    ->logExcept(['updated_at', 'created_at', 'foto_path'])
    ->logOnlyDirty()
    ->dontSubmitEmptyLogs()
    ->useLogName('colaborador');
```

### B12. Visualizador tinha permissão demais

**Sintoma:** Teste esperava `403` em `UserList` para role `visualizador`, recebia `200`.

**Causa:** O seeder dava **todas** as permissões `.view-any` para o role `visualizador`, incluindo `users.view-any` e `roles.view-any` (administrativas).

**Correção:** Excluir explicitamente `users.*` e `roles.*` do role visualizador:
```php
$viewPermissions = Permission::where(function ($q) {
        $q->where('name', 'LIKE', '%.view')
          ->orWhere('name', 'LIKE', '%.view-any');
    })
    ->where('name', 'NOT LIKE', 'users.%')
    ->where('name', 'NOT LIKE', 'roles.%')
    ->get();
```

## Bugs de teste / falsos positivos

### B13. `assertDontSee` falhava por causa de `<option>` no `<select>` de filtro

**Sintoma:** Teste buscava "Maria" e esperava que "João Pedro" não aparecesse na tela. Mas o `<select>` de filtro de colaboradores listava **todos** os colaboradores como `<option>`.

**Correção:** Refatorado o teste para verificar a coleção `viewData('advertencias')` em vez de fazer string match no HTML completo. Comportamento do código está correto; o teste é que estava verificando a coisa errada.

### B14. Teste usava role errado para testar bloqueio

**Sintoma:** Teste `bloqueia acesso à listagem` setava role `visualizador` e esperava `403`, mas visualizador justamente tem `colaboradores.view-any` (é role de consulta).

**Correção:** Trocado o role do teste para `colaborador`, que de fato não tem essa permissão.

## Bugs silenciosos

### B15. `try/catch` engolindo exceções em desenvolvimento

**Sintoma:** Em vários `Livewire`, o método de salvar tinha `try/catch (\Throwable)` que logava e mostrava um toast genérico. Em testes e em desenvolvimento, isso escondia os bugs reais.

**Correção:** Mantido o catch para produção (UX melhor), mas re-lançando em `testing`/`local`:

```php
} catch (\Throwable $e) {
    \Log::error('Erro ao salvar', ['erro' => $e->getMessage()]);
    if (app()->environment('testing', 'local')) {
        throw $e;
    }
    $this->dispatch('toast', type: 'error', message: 'Erro ao salvar.');
}
```

Aplicado em: `Colaboradores\Formulario`, `PedidosCompra\Formulario`.

## Discrepâncias de schema

### B16. Campos inventados vs. schema real

**Sintoma:** No primeiro draft do Fornecedor usei `contato_principal`, `banco_nome`, `banco_tipo_conta` que não existiam no schema.

**Causa:** Não verifiquei a migration antes de criar o model/Livewire/view.

**Correção:** Comparado todos os campos do model com a migration e ajustado para os nomes reais (`contato_nome`, `contato_cargo`; remoção de `banco_nome` e `banco_tipo_conta` que de fato não existem na tabela `fornecedores`).

**Lição:** sempre verificar o schema da migration antes de criar código que escreve naquela tabela.

## Lições gerais

1. **Sistemas não-executados acumulam bugs invisíveis.** Mesmo com revisão cruzada cuidadosa, são 16 bugs reais encontrados em algumas horas de execução. Vale rodar cedo.

2. **`$guarded` vs `$fillable`.** O projeto mistura os dois. `$guarded` é mais permissivo (qualquer atributo passa exceto os listados) e `$fillable` é mais restritivo (só os listados passam). Quando trocou um pelo outro, esquecer de atualizar `logFillable()` foi fácil.

3. **Timezone é a maldição silenciosa.** Sempre configurar timezone explicitamente em todas as camadas (PHP, app, banco, conexão). E sempre escrever pelo menos um teste que dependa de comparação temporal (`isFuture`, etc).

4. **String vazia ≠ NULL em campos enum.** Lição para sempre lembrar: converter `''` para `null` antes de salvar em campos opcionais com CHECK constraint.

5. **Try/catch genérico mascara bugs.** Use re-throw em ambientes não-produtivos.

6. **Hooks `updated*` do Livewire devem ser delicados.** Não sobrescrever input do usuário cegamente.
