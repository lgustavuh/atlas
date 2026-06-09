# Atlas — Deploy no Railway + Supabase

Guia passo a passo para colocar o **Atlas** rodando online em **uns 15 minutos**, usando Railway (app) + Supabase (banco).

> **Estado atual do projeto Supabase**
> Já criei o projeto pra você. Os dados estão prontos pra usar:
>
> - **Nome**: `atlas`
> - **Project ref**: `rnrmmjpuxzmjgeqzwofu`
> - **Região**: `sa-east-1` (São Paulo)
> - **Organização**: `Cisfy`
> - Extensões `citext`, `pg_trgm`, `unaccent` já instaladas
> - Painel: <https://supabase.com/dashboard/project/rnrmmjpuxzmjgeqzwofu>
>
> Senha do banco: você precisa **resetar a senha no painel** (eu não tenho acesso a ela).

---

## Etapa 1 — Pegar a connection string do Supabase

1. Abra o painel: <https://supabase.com/dashboard/project/rnrmmjpuxzmjgeqzwofu>
2. Vá em **Project Settings → Database**
3. Na seção **Database password**, clique em **Reset database password** e copie a senha gerada (anote num lugar seguro!)
4. Role até **Connection pooling** e copie os valores marcados como **Transaction pooler** (porta `6543`). Vai parecer assim:

   ```
   Host:     aws-0-sa-east-1.pooler.supabase.com
   Port:     6543
   Database: postgres
   User:     postgres.rnrmmjpuxzmjgeqzwofu
   Password: <a senha que você acabou de resetar>
   ```

   **Por que pooler em vez do banco direto?** O pooler aguenta mais conexões simultâneas. O Laravel + Livewire abre uma conexão por request, então a porta direta (`5432`) estoura rápido. A porta `6543` (Transaction mode) é a recomendada pra apps.

---

## Etapa 2 — Subir o código pro GitHub

O Railway só faz deploy a partir de um repo GitHub conectado.

1. Crie um repo novo no seu GitHub (privado é ideal). Pode chamar de `atlas`.
2. Suba todo o conteúdo desta pasta pra esse repo:

   ```bash
   cd atlas/                  # pasta extraída do zip
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin https://github.com/SEU_USUARIO/atlas.git
   git push -u origin main
   ```

3. Confirme que estes arquivos estão no repo (são os essenciais pro deploy):
   - `Dockerfile.production`
   - `railway.json`
   - `.env.railway.example`
   - `docker/production/` (pasta com nginx.conf, supervisord.conf, entrypoint.sh)

---

## Etapa 3 — Criar projeto no Railway

1. Abra <https://railway.app/new> e clique em **"Deploy from GitHub repo"**
2. Autorize o Railway a ver seu repo `atlas`
3. Selecione o repo. O Railway vai detectar o `Dockerfile.production` automaticamente
4. Não inicie o deploy ainda — primeiro configure as variáveis (etapa 4)

---

## Etapa 4 — Configurar variáveis de ambiente

No painel do projeto Railway: aba **Variables → RAW Editor**. Cole o bloco abaixo, depois **substitua os 3 valores marcados**:

```env
APP_NAME=Atlas
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

# ALTERAR 1: gere com `openssl rand -base64 32` e coloque "base64:" na frente
# Exemplo: base64:aTbcDeFgHiJkLmNoPqRsTuVwXyZ0123456789+/=AAAA
APP_KEY=

# Railway preenche automaticamente APP_URL depois do primeiro deploy.
# Por enquanto pode deixar em branco — depois cole o domínio gerado.
APP_URL=

# --- Banco (Supabase Transaction Pooler) ---
DB_CONNECTION=pgsql
DB_HOST=aws-0-sa-east-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.rnrmmjpuxzmjgeqzwofu
# ALTERAR 2: cole a senha que você resetou no Supabase
DB_PASSWORD=
DB_CHARSET=utf8
DB_PREPARES=false

# --- Cache, sessão, filas (sem Redis: usa o próprio Postgres) ---
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=sync

# --- Logs (stdout, Railway captura) ---
LOG_CHANNEL=stderr
LOG_LEVEL=info

# --- Storage efêmero (uploads somem em redeploys; ok pra teste) ---
FILESYSTEM_DISK=local

# --- No PRIMEIRO deploy, ALTERAR 3 destes pra true. Depois volte pra false. ---
DB_RUN_MIGRATIONS=true
SEED_DATABASE=true

# --- Email: por enquanto loga em vez de enviar ---
MAIL_MAILER=log
MAIL_FROM_ADDRESS=sistema@atlas.app
MAIL_FROM_NAME=Atlas

# --- Segurança ---
LOGIN_MAX_ATTEMPTS=5
LOGIN_DECAY_MINUTES=1
PASSWORD_RESET_TIMEOUT=60
REQUIRE_EMAIL_VERIFICATION=false
```

**Como gerar `APP_KEY`** (Linux/Mac):

```bash
echo "base64:$(openssl rand -base64 32)"
```

Cole o resultado em `APP_KEY=`.

**No Windows (PowerShell):**

```powershell
$bytes = New-Object byte[] 32
[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
"base64:$([Convert]::ToBase64String($bytes))"
```

---

## Etapa 5 — Habilitar domínio público

Ainda no painel do Railway:

1. Vá em **Settings → Networking → Public Networking**
2. Clique em **Generate Domain** — vai gerar algo como `atlas-production-abc1.up.railway.app`
3. Volte na aba **Variables** e atualize `APP_URL` com esse endereço completo (com `https://`)

---

## Etapa 6 — Deploy!

Clique em **Deploy** no painel. O Railway vai:

1. Clonar o repo do GitHub
2. Buildar o `Dockerfile.production` (uns 4–6min na primeira vez — composer + npm + Docker layers)
3. Subir o container, que vai:
   - Rodar migrations no Supabase
   - Rodar seeders (admin, perfis, geografia)
   - Cachear config/route/view
   - Iniciar nginx + php-fpm via supervisord

Acompanhe os logs em tempo real na aba **Deployments → View Logs**.

---

## Etapa 7 — Primeiro acesso

Quando o deploy terminar (status verde), abra o domínio público (`https://atlas-production-abc1.up.railway.app`).

Vai aparecer a tela de login do Atlas. Entre com:

- **Email**: `admin@atlas.local`
- **Senha**: `Admin@123456`

> **Troque essa senha imediatamente** em **Perfil → Trocar senha**.

---

## Etapa 8 — Após o primeiro deploy bem-sucedido

Volte nas Variables do Railway e mude estas duas pra `false` (pra evitar rodar de novo a cada deploy):

```env
DB_RUN_MIGRATIONS=false
SEED_DATABASE=false
```

Salve. Os próximos deploys vão ser muito mais rápidos.

---

## Solução de problemas

**"Connection refused" no log:** revise `DB_PASSWORD` (senha do Supabase) e `DB_HOST` (deve terminar em `pooler.supabase.com`). Se acabou de resetar a senha, espere 30 segundos antes do próximo deploy.

**"SSL connection required":** se acontecer, adicione `DB_SSLMODE=require` nas Variables.

**Build falha em `npm ci`:** confira que `package-lock.json` está no repo. Se não estiver, rode `npm install` localmente e commit do arquivo.

**Página em branco / 500 com `APP_DEBUG=false`:** mude temporariamente `APP_DEBUG=true`, redeploy, e veja a stack trace. Volte pra `false` depois.

**"Class not found" / OPcache antigo:** clique em **Redeploy** no Railway (force rebuild).

**Storage de uploads some:** comportamento esperado no plano free do Railway. Cada redeploy zera o filesystem. Pra produção real precisa configurar S3/R2/Supabase Storage como disk.

---

## Próximos passos (depois de validar o teste)

Quando confirmar que está tudo funcionando, considere:

1. **Domínio próprio** (Railway → Settings → Networking → Custom Domain)
2. **Storage persistente** com Cloudflare R2 (R2 é S3-compatível e tem free tier generoso)
3. **SMTP real** (SendGrid, Resend, Mailgun) — trocar `MAIL_MAILER=log` por `smtp`
4. **Backup automático do banco** (Supabase tem na aba Database → Backups, mas só no Pro)
5. **Worker pra filas** (rodar `php artisan queue:work` num segundo serviço Railway)
