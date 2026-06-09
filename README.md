# Atlas — Sistema de Gestão (v2)

Reescrita moderna do sistema legado em PHP procedural (2013) para um stack atual com foco em segurança, manutenibilidade e produtividade.

## Stack

- **PHP 8.3** + **Laravel 11**
- **PostgreSQL 16**
- **Livewire 3** + **Tailwind CSS 3** + **Alpine.js**
- **Redis 7** (sessões, cache, filas)
- **Docker** + **Docker Compose** para desenvolvimento

## Pré-requisitos

- Docker Desktop (Windows/Mac) ou Docker Engine + Docker Compose (Linux)
- Git
- ~2GB de espaço livre

**Não é necessário instalar PHP, Composer, Node ou Postgres na sua máquina.** Tudo roda em containers.

## Instalação automática (recomendada)

Para evitar fazer o setup manual abaixo, use os scripts de instalação automatizada:

**Windows** (Docker Desktop instalado):
```cmd
instalar.bat
```

**Linux/Mac**:
```bash
chmod +x instalar.sh
./instalar.sh
```

Esses scripts cuidam de:
- Validar Docker, espaço em disco, porta 8000 livre
- Criar `.env` com senhas aleatórias seguras (32 chars)
- Preparar diretórios de `storage/app/private/*`
- Construir imagens Docker
- Instalar dependências PHP + Node
- Rodar migrations + seeders
- Importar cidades do IBGE
- Gerar APP_KEY
- Compilar assets
- Validar instalação final (APP_KEY, senha não-default, HTTP respondendo)

### Scripts auxiliares (depois da instalação)

| Windows | Linux/Mac | Alternativa Make | O que faz |
|---------|-----------|------------------|-----------|
| `iniciar.bat` | `./iniciar.sh` | `make up` | Sobe os containers e abre o browser |
| `parar.bat` | `./parar.sh` | `make down` | Para os containers (preserva dados) |
| `parar.bat --tudo` | `./parar.sh --tudo` | `make down-v` | Para e **apaga** todos os volumes |
| `logs.bat [serviço]` | `./logs.sh [serviço]` | `make logs` | Logs em tempo real |
| `atualizar.bat` | `./atualizar.sh` | `make update` | Pull → composer → npm → migrate → cache |
| `backup.bat` | `./backup.sh` | `make backup` | Backup completo (banco + arquivos + .env) |
| `backup.bat --listar` | `./backup.sh --listar` | `make backup-list` | Lista backups disponíveis |
| `restaurar.bat ARQ` | `./restaurar.sh ARQ` | `make restore-db F=ARQ` | Restaura de um backup |
| `diagnosticar.bat` | `./diagnosticar.sh` | — | Diagnostica problemas comuns |

## Setup inicial manual (primeira vez)

Caso prefira fazer manualmente em vez de usar os scripts:

### 1. Clone e prepare o ambiente

```bash
git clone <repo-url> atlas
cd atlas
cp .env.example .env
```

**Importante:** edite o `.env` e troque pelo menos `APP_KEY` (será gerada no passo 4), `DB_PASSWORD` e `REDIS_PASSWORD` antes de qualquer ambiente que não seja seu localhost.

### 2. Suba os containers

```bash
docker compose up -d --build
```

Isso vai construir as imagens (~5 min na primeira vez) e subir 5 serviços:

| Serviço     | Porta local | O que é                                  |
|-------------|-------------|------------------------------------------|
| `app`       | —           | PHP-FPM 8.3 (não exposta diretamente)   |
| `nginx`     | 8000        | Servidor web — acesse http://localhost:8000 |
| `postgres`  | 5432        | Banco de dados                           |
| `redis`     | 6379        | Cache e sessões                          |
| `mailpit`   | 8025        | Interface web para testar emails (http://localhost:8025) |

### 3. Instale o Laravel dentro do container

A primeira vez, você precisa instalar o Laravel e as dependências:

```bash
# Instala o Laravel + dependências do composer.json
docker compose exec app composer install

# Instala dependências do frontend
docker compose exec app npm install
```

### 4. Configure a aplicação

```bash
# Gera a APP_KEY (chave de criptografia da aplicação)
docker compose exec app php artisan key:generate

# Cria as tabelas no banco
docker compose exec app php artisan migrate

# Popula dados iniciais (usuário admin, perfis, etc)
docker compose exec app php artisan db:seed

# Compila os assets de frontend
docker compose exec app npm run build
```

### 5. Acesse o sistema

Abra http://localhost:8000

**Credenciais padrão (TROCAR EM PRODUÇÃO):**
- Email: `admin@atlas.local`
- Senha: `Admin@123456`

## Comandos do dia a dia

```bash
# Subir o ambiente
docker compose up -d

# Derrubar o ambiente
docker compose down

# Ver logs
docker compose logs -f app
docker compose logs -f nginx

# Entrar no container PHP
docker compose exec app bash

# Rodar migrations
docker compose exec app php artisan migrate

# Rodar testes
docker compose exec app php artisan test

# Modo desenvolvimento do frontend (hot reload)
docker compose exec app npm run dev
```

## Estrutura do projeto

```
atlas/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Controladores HTTP
│   │   ├── Middleware/        # Middlewares (auth, permissões, etc)
│   │   └── Requests/          # Form Requests (validação)
│   ├── Livewire/              # Componentes Livewire (UI reativa)
│   ├── Models/                # Modelos Eloquent (acesso ao banco)
│   ├── Policies/              # Políticas de autorização
│   └── Services/              # Lógica de negócio
├── database/
│   ├── migrations/            # Versionamento do schema
│   ├── seeders/               # Dados iniciais
│   └── factories/             # Fábricas para testes
├── resources/
│   ├── views/                 # Templates Blade
│   ├── css/                   # CSS (Tailwind)
│   └── js/                    # JS (Alpine)
├── routes/
│   ├── web.php                # Rotas web
│   └── auth.php               # Rotas de autenticação
├── tests/                     # Testes automatizados
├── docker/                    # Configurações Docker
│   ├── nginx/
│   ├── php/
│   └── postgres/
├── docker-compose.yml
├── Dockerfile
├── .env.example
└── README.md
```

## Decisões de segurança aplicadas

- **Senhas:** hash com Argon2id (padrão Laravel 11)
- **CSRF:** token obrigatório em todas as requisições POST/PUT/DELETE
- **XSS:** Blade escapa tudo por padrão (`{{ }}`)
- **SQL Injection:** impossível usando Eloquent ORM com bindings
- **Rate limiting:** 5 tentativas de login por minuto por IP
- **Headers de segurança:** CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
- **Sessões:** cookies `httpOnly`, `secure` (em produção), `SameSite=Lax`
- **Variáveis sensíveis:** apenas em `.env`, nunca no código
- **Autorização granular:** policies por recurso, permissões por módulo via spatie/laravel-permission

## Próximas fases

Veja `docs/ROADMAP.md` para o plano completo e `docs/MODULOS-RESTANTES.md` para o roadmap dos módulos pendentes.

- [x] Fase 0 — Estrutura do projeto e ambiente Docker
- [x] Fase 1 — Schema do banco e migrations
- [x] Fase 2 — Autenticação e autorização
- [x] Fase 3 — Layout base e dashboard
- [x] Fase 4 — Módulo Colaboradores (referência)
- [x] Fase 5 — Cargos, Departamentos, Classificações e Geografia
- [x] Fase 6 — Advertências e Atestados
- [x] Fase 7 — Férias + roadmap dos demais módulos
- [x] Fase 8 — Compras (Fornecedores, Materiais, Grupos, Pedidos de Compra)
- [x] **Fase 9 — Validação end-to-end: sistema rodando, 132 testes passando**
- [x] **Fase 10 — Veículos e Manutenções (156 testes passando)**
- [x] **Fase 11 — Obras (167 testes passando)**
- [x] **Fase 12 — Biblioteca (180 testes passando)**
- [x] **Fase 13 — Alertas Administrativos (194 testes passando)**
- [x] **Fase 14 — Recrutamento: Vagas + Candidatos (219 testes passando)**
- [x] **Fase 15 — Transporte e Hospedagem (231 testes passando)**
- [x] **Fase 16 — Repúblicas (249 testes passando) — TODOS os módulos de domínio implementados!**
- [x] **Fase 17 — Tela de Auditoria (260 testes passando)**
- [x] **Fase 18 — Exportação Excel (278 testes passando)**
- [x] **Fase 19 — Geração de PDFs (289 testes passando)**
- [x] **Fase 20 — Notificações por e-mail (304 testes passando) — PROJETO COMPLETO!**

## Estado de validação

Sistema validado em PostgreSQL 16 + PHP 8.3 com a suíte completa de testes:

```
Tests:    326 passed (679 assertions)
Duration: ~195s
```

## 🔒 Segurança

O sistema passou por auditoria de segurança em 7 categorias críticas, com correções aplicadas:

- ✅ **Mass assignment**: trait `AuditaUsuario` sobrescreve automaticamente `created_by`/`updated_by`/`deleted_by` no momento do save, impedindo forjar autoria via input.
- ✅ **Path traversal**: detectado pelo Flysystem + dupla sanitização de nome de arquivo em `DocumentoUploadService`.
- ✅ **Header injection no Content-Disposition**: nome sanitizado + codificação RFC 5987 (`filename*=UTF-8''...`) no `DocumentoController`.
- ✅ **Validação de uploads**: `mimes:` + `mimetypes:` (extensão + MIME real via `finfo`) em todos os pontos de upload.
- ✅ **Rate limit**: login (existente) + forgot-password (3 tentativas / 15min por email+IP).
- ✅ **Security headers globais**: middleware aplica X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP e HSTS (em HTTPS).
- ✅ **Vazamento de exception**: erros de sistema viram mensagem genérica para o usuário + log detalhado interno.

Bugs encontrados e corrigidos durante a Fase 9 estão documentados em `docs/BUGS-CORRIGIDOS.md`.

## 📡 Monitoramento e operação

O sistema expõe dois endpoints de health check:

| Endpoint | Tipo | Uso |
|----------|------|-----|
| `GET /up` | Raso | Load balancer / liveness probe. Verifica só que o framework subiu. ~1ms. |
| `GET /health` | Completo | Monitoramento externo (UptimeRobot, Datadog). Verifica banco, cache, Redis e storage. ~50ms. |

**Resposta de `/health` (200 OK):**
```json
{
  "status": "ok",
  "timestamp": "2026-05-31T13:48:35-03:00",
  "version": "unknown",
  "environment": "production",
  "checks": {
    "app":      { "ok": true, "message": "Framework operacional" },
    "database": { "ok": true, "message": "PostgreSQL respondendo", "latency_ms": 4 },
    "cache":    { "ok": true, "message": "Cache (redis) OK", "latency_ms": 2 },
    "redis":    { "ok": true, "message": "Redis respondendo", "latency_ms": 1 },
    "storage":  { "ok": true, "message": "Storage gravável" }
  }
}
```

Quando algum componente falha, retorna `503 Service Unavailable` com `status: degraded` e descrição do problema.

Em produção, mensagens de erro escondem detalhes (só mostra o tipo da exception); em dev mostra detalhes completos.

## 🚀 Deploy em produção

Para servidores Linux, use o `deploy.sh` em vez do `instalar.sh`:

```bash
chmod +x deploy.sh
./deploy.sh
```

O script de deploy:
1. **Valida `.env`**: APP_ENV=production, APP_DEBUG=false, DB_PASSWORD trocada, APP_KEY presente
2. **Backup automático**: dump em `backups/pre-deploy_YYYY-MM-DD_HHMMSS.sql` (mantém 30 dias)
3. **Modo manutenção**: ativa antes das migrations, desativa ao final (trap garante reativação em caso de erro)
4. **Composer `--no-dev`**: instala só dependências de produção
5. **Migrations forçadas**: `--force` (evita prompt interativo)
6. **Assets de produção**: `npm run build` com minificação
7. **Cache otimizado**: gera `config:cache`, `route:cache`, `view:cache`, `event:cache`
8. **Recicla workers**: `queue:restart` (carregam código novo)
9. **Health check final**: confirma que `/up` retorna 200, PG/Redis OK, workers ativos

### Cron de produção

Para que o scheduler e a fila funcionem 24/7, configure no servidor:

```cron
# Scheduler (notificações de vencimento)
* * * * * cd /opt/atlas && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Ou rode em background:
```bash
docker compose exec -d app php artisan schedule:work
docker compose exec -d app php artisan queue:work --tries=3 --timeout=120
```

Para alta disponibilidade, use **systemd** com unit files dedicados (`etc-queue.service`, `etc-scheduler.service`).

## Troubleshooting

**"Permission denied" ao acessar arquivos**
Problema clássico em Linux. Execute:
```bash
sudo chown -R $USER:$USER .
```

**Porta 8000 já em uso**
Edite `docker-compose.yml` e mude `8000:80` para `8001:80`.

**Postgres não conecta**
Verifique se o container subiu: `docker compose ps`. Se ele reinicia em loop, veja os logs: `docker compose logs postgres`.

## Suporte

Sistema mantido por: STI / Prefeitura Municipal de Itaú de Minas
