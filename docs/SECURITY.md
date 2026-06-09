# Decisões de Segurança

Documento que registra **por que** cada controle de segurança foi escolhido, comparando com as vulnerabilidades identificadas no sistema legado.

---

## 1. Senhas

**Legado:** `sha1($_POST['text_senha'])` — SHA-1 sem salt.
**Novo:** Argon2id (padrão Laravel 11) com salt único por senha.

```php
// Laravel cuida automaticamente:
$user->password = Hash::make($plainPassword);
Hash::check($plainPassword, $user->password);
```

**Por quê Argon2id:**
- Vencedor da Password Hashing Competition (2015)
- Resistente a ataques GPU/ASIC
- Configurável em custo de memória e tempo
- Sucessor recomendado do bcrypt

---

## 2. SQL Injection

**Legado:** Concatenação direta de `$_POST` em strings SQL.
**Novo:** Eloquent ORM + Query Builder com bindings PDO.

```php
// Impossível injetar SQL aqui:
$colaborador = Colaborador::where('cpf', $request->cpf)->first();

// Mesmo em raw queries, usar bindings:
DB::select('SELECT * FROM colaborador WHERE cpf = ?', [$cpf]);
```

**Política:** Raw queries só com bindings, revisados em code review.

---

## 3. XSS (Cross-Site Scripting)

**Legado:** Não havia escape de output.
**Novo:** Blade escapa tudo automaticamente.

```blade
{{ $colaborador->nome }}        {{-- Escapado --}}
{!! $conteudo_html_confiavel !!} {{-- NÃO escapado, usar com cautela --}}
```

**Política:** `{!! !!}` só para conteúdo gerado pelo próprio sistema (PDFs, relatórios). Nunca para input do usuário.

---

## 4. CSRF (Cross-Site Request Forgery)

**Legado:** Sem proteção alguma.
**Novo:** Token CSRF em todos os forms (automático no Blade via `@csrf`).

```blade
<form method="POST">
    @csrf
    {{-- ... --}}
</form>
```

Middleware `VerifyCsrfToken` rejeita requisições POST/PUT/DELETE sem token válido.

---

## 5. Autenticação

**Legado:** Login sem rate limiting, base64 no usuário (ofuscação inútil), credenciais hardcoded no código.
**Novo:**
- Rate limiting: 5 tentativas/minuto por IP+email
- Login throttle progressivo
- Credenciais em `.env`, nunca no código
- Lockout automático com unlock por administrador
- Notificação de novo login em IP diferente (opcional)

---

## 6. Sessões

**Legado:** `session_start()` sem configuração.
**Novo:**

```php
// config/session.php
'lifetime' => 120,                    // 2 horas
'expire_on_close' => false,
'encrypt' => true,                    // Sessões criptografadas
'driver' => 'redis',                  // Sessão em Redis (não em arquivo)
'cookie' => 'etc_session',
'http_only' => true,                  // JS não acessa cookie
'secure' => true,                     // Apenas HTTPS (em produção)
'same_site' => 'lax',                 // Proteção CSRF adicional
```

Mais: `session_regenerate_id()` automático após login (previne session fixation).

---

## 7. Upload de Arquivos

**Legado:**
- Validação só por extensão do nome
- Arquivo salvo em diretório web-accessible
- Nome do arquivo preservado

**Novo:**
- Validação por **conteúdo real** via `finfo` (mime type real)
- Whitelist explícita de tipos: pdf, jpg, png, docx
- Tamanho máximo: 30MB (configurável)
- Nome do arquivo é renomeado para hash (`{uuid}.{ext}`)
- Armazenamento em `storage/app/private/` (fora do public)
- Download autorizado via controller: verifica permissão antes de servir
- Scanning antivírus via ClamAV (futuro, em produção)

```php
// Exemplo de validação rigorosa:
$request->validate([
    'arquivo' => [
        'required',
        'file',
        'max:30720', // 30MB
        'mimes:pdf,jpg,jpeg,png,docx',     // Validação por mime real
        'mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
]);
```

---

## 8. Autorização Granular

**Legado:** ~30 colunas `NivelXxx` na tabela usuário, comparações numéricas espalhadas.
**Novo:** Sistema relacional com `spatie/laravel-permission`.

```php
// Definição:
$role = Role::create(['name' => 'gestor_rh']);
$role->givePermissionTo('colaborador.view', 'colaborador.create');

// Verificação:
$user->can('colaborador.create');  // boolean
// Ou via policy:
$this->authorize('create', Colaborador::class);
// Ou via middleware na rota:
Route::middleware('can:colaborador.create')->...
```

---

## 9. Headers HTTP

Configurados no Nginx (`security-headers.conf`):

- `X-Frame-Options: SAMEORIGIN` — bloqueia clickjacking
- `X-Content-Type-Options: nosniff` — bloqueia MIME sniffing
- `Referrer-Policy: strict-origin-when-cross-origin` — não vaza URLs
- `Content-Security-Policy` — restringe origens de scripts/estilos
- `Permissions-Policy` — bloqueia APIs não usadas (câmera, mic, geo)
- `Strict-Transport-Security` — força HTTPS (ativar em produção)

---

## 10. Logs e Auditoria

**Legado:** Tinha módulo "LogsModificacoes" mas dependia de cada developer lembrar de gravar.
**Novo:** `spatie/laravel-activitylog` registra automaticamente:

- Quem criou/editou/excluiu qual recurso
- O que mudou (diff de campos)
- Quando aconteceu
- IP do usuário
- User-Agent

Auditoria completa sem código boilerplate.

---

## 11. Backups

**Legado:** Inexistente (provavelmente).
**Novo:** `spatie/laravel-backup`:

- Backup automático do banco + uploads
- Diário, com retenção de 30 dias
- Notificação em caso de falha
- Pode enviar para S3, FTP, local

---

## 12. Credenciais e Segredos

**Legado:** Senha do banco em `configuracoes.php` versionado.
**Novo:**
- Tudo no `.env` (nunca versionado)
- `.env.example` documenta as variáveis sem valores reais
- Em produção: variáveis injetadas pelo orquestrador (Docker secrets, AWS Parameter Store, etc)
- Usuário do banco com permissões mínimas (não `root`)

---

## 13. HTTPS

**Política:** Obrigatório em qualquer ambiente que não seja localhost.

- Certificado via Let's Encrypt (gratuito)
- Renovação automática
- Redirect HTTP → HTTPS no Nginx
- HSTS habilitado após validação

---

## 14. Atualizações

**Política:**
- `composer audit` e `npm audit` rodam no CI/CD
- Dependências atualizadas mensalmente
- Patches de segurança aplicados em até 7 dias
- Laravel sempre na versão LTS ou na anterior mais recente

---

## O que ainda precisamos definir

- [ ] WAF na frente do Nginx? (Cloudflare, AWS WAF)
- [ ] 2FA (TOTP via Google Authenticator)?
- [ ] Política de retenção de logs (LGPD)
- [ ] Pseudonimização de dados sensíveis em ambientes não-produção
- [ ] Política de backup off-site
