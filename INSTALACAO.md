# Instalação do Atlas

Sistema de gestão administrativa/RH reescrito em Laravel 11, executando em PHP 8.3 + PostgreSQL 16.

## Pré-requisitos

- **PHP 8.3+** com extensões: `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `intl`, `pdo`
- **PostgreSQL 16+**
- **Composer 2.x**
- **Node.js 18+** e npm
- **Redis 7+** (opcional, recomendado para produção)

## Instalação rápida (Docker)

O modo recomendado para desenvolvimento. Tudo já está orquestrado no `docker-compose.yml`.

```bash
# Linux/Mac
./instalar.sh

# Windows
instalar.bat
```

Após a instalação, acesse `http://localhost:8080` com:
- **Email:** `admin@atlas.local`
- **Senha:** `Admin@123456`

**Troque a senha no primeiro acesso.**

## Instalação manual

Útil se você não vai usar Docker.

### 1. Banco de dados

Crie o banco e habilite as extensões **obrigatórias**:

```sql
CREATE DATABASE atlas;
CREATE USER etcuser WITH PASSWORD 'sua_senha_aqui';
GRANT ALL PRIVILEGES ON DATABASE atlas TO etcuser;
ALTER DATABASE atlas OWNER TO etcuser;

\c atlas
CREATE EXTENSION IF NOT EXISTS citext;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;
GRANT ALL ON SCHEMA public TO etcuser;
```

**Por que essas três extensões?**
- `citext` — emails case-insensitive nativamente
- `pg_trgm` — busca por similaridade (índices trigram para nome, CPF, etc)
- `unaccent` — buscas que ignoram acentos ("João" encontra "Joao")

Para o ambiente de testes, **repita o mesmo processo para o banco `atlas_test`**.

### 2. Dependências

```bash
composer install
npm install
npm run build
```

### 3. Configuração

```bash
cp .env.example .env
php artisan key:generate
```

Edite `.env` com suas credenciais de banco (mantenha `DB_CONNECTION=pgsql`).

### 4. Banco

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 5. Servidor

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Acesse `http://127.0.0.1:8000`.

## Configurações importantes

### Timezone

A conexão `pgsql` em `config/database.php` define `'timezone' => 'America/Sao_Paulo'`. **Isso é necessário** porque sem essa configuração o PostgreSQL interpreta os timestamps enviados pelo Laravel como UTC, gerando uma diferença de 3 horas em todos os campos `datetime`.

Para mudar o timezone:
```env
# .env
DB_TIMEZONE=America/Sao_Paulo
```

### Cidades brasileiras completas

Por padrão, o seeder popula apenas as 28 capitais. Para baixar todas as ~5.570 cidades do IBGE:

```bash
php artisan etc:importar-cidades-ibge
```

(Use `--force` para reimportar do zero.)

## Estrutura do projeto

```
atlas-novo/
├── app/
│   ├── Livewire/          # Componentes (telas dinâmicas)
│   ├── Models/            # Eloquent models
│   ├── Policies/          # Autorização por modelo
│   ├── Services/          # Lógica de negócio
│   ├── Rules/             # Validações (CPF, CNPJ, PIS, senha forte)
│   └── Http/Requests/     # FormRequests
├── database/
│   ├── migrations/        # 12 migrations (schema completo)
│   ├── seeders/           # Permissões, roles, geografia, admin
│   └── factories/         # Para testes
├── resources/
│   ├── views/             # Blade templates
│   ├── css/               # Tailwind
│   └── js/                # Alpine.js + Livewire
├── routes/web.php         # Rotas
└── tests/                 # 132 testes Pest passando
```

## Executando os testes

```bash
php artisan test
```

Resultado esperado: **132 passed (264 assertions)**.

## Módulos implementados

✅ Autenticação (login, reset de senha, lockout)
✅ Autorização (132 permissões granulares, 7 perfis)
✅ Colaboradores (60+ campos, foto, endereço, dependentes)
✅ Cargos, Departamentos, Classificações
✅ Geografia (Brasil + extensível)
✅ Advertências (verbal/escrita/suspensão)
✅ Atestados (workflow de aprovação)
✅ Férias (regras CLT — período aquisitivo, gozo, abono)
✅ Fornecedores (PF/PJ com validação CNPJ)
✅ Materiais (controle de estoque, alerta de mínimo)
✅ Grupos de Materiais (hierarquia)
✅ Pedidos de Compra (workflow de 2 aprovações, itens dinâmicos, recebimento parcial)

## Módulos pendentes

Veja `docs/MODULOS-RESTANTES.md` para o roadmap completo dos ~10 módulos restantes (Veículos, Manutenções, Obras, Biblioteca, etc).

## Troubleshooting

### "Please provide a valid cache path"

A pasta `storage/framework/views` não existe ou não tem permissão de escrita. Crie:

```bash
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions
mkdir -p storage/app/public storage/app/private storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### "could not find driver" no Postgres

Falta a extensão PHP `pdo_pgsql`. Instale:

```bash
# Ubuntu/Debian
sudo apt-get install php8.3-pgsql

# Windows: descomente extension=pdo_pgsql no php.ini
```

### "FATAL: database does not exist"

Crie o banco principal **E** o de testes (com as 3 extensões em cada).

### Timestamps com 3 horas de diferença

Verifique se `config/database.php` na conexão `pgsql` tem `'timezone' => 'America/Sao_Paulo'`.

## Segurança

⚠️ **Antes de subir em produção:**

1. Troque a senha do admin (`Admin@123456`)
2. Gere uma nova `APP_KEY` (`php artisan key:generate`)
3. Configure `APP_ENV=production` e `APP_DEBUG=false`
4. Use HTTPS (configure proxy reverso)
5. Configure `SESSION_SECURE_COOKIE=true`
6. Configure backups regulares (Spatie Laravel Backup já está incluso — `php artisan backup:run`)
7. Defina senhas fortes para o usuário do banco
