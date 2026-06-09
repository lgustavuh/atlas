-- Extensões úteis para o sistema
-- Executado automaticamente pelo Postgres na primeira inicialização

-- UUID v4 nativo (sem precisar de extensão em PG13+)
-- Mas mantemos a extensão para uuid_generate_v4() caso queiramos
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Remoção de acentos (útil para buscas)
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Trigram para buscas fuzzy ("parecido com")
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Citext para email/usuário case-insensitive
CREATE EXTENSION IF NOT EXISTS "citext";
