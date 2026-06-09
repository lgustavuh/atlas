# ===========================================
# ETC Industrial - Atalhos do dia a dia
# Uso: make <comando> ou apenas `make` para ver a lista
# ===========================================

.DEFAULT_GOAL := help
.PHONY: help \
        up down restart down-v build rebuild \
        install update fresh migrate seed \
        test test-filter coverage \
        shell tinker logs logs-app logs-pg logs-redis logs-mail logs-install \
        dev format analyze \
        cache cache-clear cache-prod \
        queue queue-work schedule schedule-work \
        backup backup-db backup-list backup-clean restore-db \
        health version

# ----------------------------------------
# Ajuda
# ----------------------------------------
help: ## Lista todos os comandos disponíveis
	@echo ""
	@echo "  \033[1mETC Industrial - Atalhos do projeto\033[0m"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
	@echo ""

# ----------------------------------------
# Ciclo de vida dos containers
# ----------------------------------------
up: ## Sobe os containers em background
	docker compose up -d
	@echo ""
	@echo "  Sistema:  http://localhost:8000"
	@echo "  Mailpit:  http://localhost:8025"

down: ## Para os containers (preserva volumes/dados)
	docker compose down

restart: ## Reinicia os containers
	docker compose restart

down-v: ## Para containers e REMOVE todos os volumes (APAGA o banco!)
	@echo "ATENCAO: isso vai apagar o banco de dados e arquivos enviados!"
	@read -p "Digite 'APAGAR' para confirmar: " confirma; \
	if [ "$$confirma" = "APAGAR" ]; then \
		docker compose down -v; \
		echo "Volumes removidos."; \
	else \
		echo "Cancelado."; \
	fi

build: ## (Re)constrói as imagens
	docker compose build

rebuild: ## Reconstrói imagens do zero (sem cache)
	docker compose build --no-cache

# ----------------------------------------
# Dependências e atualização
# ----------------------------------------
install: ## Instala dependências (composer + npm)
	docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
	docker compose exec app npm install --silent

update: ## Atualiza sistema após pull: deps + migrations + cache + assets
	docker compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader
	docker compose exec app npm install --silent
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan queue:restart
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan view:clear
	docker compose exec app php artisan route:clear
	docker compose exec app npm run build
	@echo ""
	@echo "  Atualizacao concluida."

# ----------------------------------------
# Banco de dados
# ----------------------------------------
fresh: ## Recria o banco do zero com seeders (CUIDADO: apaga dados)
	@echo "ATENCAO: vai apagar todos os dados do banco!"
	@read -p "Digite 'SIM' para confirmar: " confirma; \
	if [ "$$confirma" = "SIM" ]; then \
		docker compose exec app php artisan migrate:fresh --seed --force; \
	else \
		echo "Cancelado."; \
	fi

migrate: ## Roda migrations pendentes
	docker compose exec app php artisan migrate

seed: ## Roda seeders
	docker compose exec app php artisan db:seed

# ----------------------------------------
# Testes
# ----------------------------------------
test: ## Roda toda a suíte de testes
	docker compose exec app php artisan test

test-filter: ## Roda testes filtrados (uso: make test-filter F=Seguranca)
	docker compose exec app php artisan test --filter=$(F)

coverage: ## Roda testes com cobertura (precisa Xdebug habilitado)
	docker compose exec app php artisan test --coverage

# ----------------------------------------
# Acesso e debug
# ----------------------------------------
shell: ## Abre bash no container da aplicação
	docker compose exec app bash

tinker: ## Abre Tinker (REPL do Laravel)
	docker compose exec app php artisan tinker

logs: ## Logs em tempo real (app + nginx)
	docker compose logs -f --tail=100 app nginx

logs-app: ## Logs apenas do app
	docker compose logs -f --tail=100 app

logs-pg: ## Logs do PostgreSQL
	docker compose logs -f --tail=100 postgres

logs-redis: ## Logs do Redis
	docker compose logs -f --tail=100 redis

logs-mail: ## Logs do Mailpit
	docker compose logs -f --tail=100 mailpit

# ----------------------------------------
# Desenvolvimento
# ----------------------------------------
dev: ## Inicia o build do frontend em modo watch (Vite)
	docker compose exec app npm run dev

format: ## Formata código PHP (Pint)
	docker compose exec app ./vendor/bin/pint

analyze: ## Análise estática (PHPStan)
	docker compose exec app ./vendor/bin/phpstan analyse

# ----------------------------------------
# Cache (produção precisa, dev limpo é mais rápido)
# ----------------------------------------
cache-clear: ## Limpa todos os caches do Laravel
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan view:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan event:clear

cache-prod: ## Gera caches otimizados para produção
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
	docker compose exec app php artisan event:cache

cache: cache-clear ## Alias para cache-clear

# ----------------------------------------
# Filas e agendamentos
# ----------------------------------------
queue-work: ## Inicia worker da fila (foreground - para dev)
	docker compose exec app php artisan queue:work --tries=3 --timeout=120

queue: ## Reinicia workers da fila (recicla após mudanças em Jobs/Mailables)
	docker compose exec app php artisan queue:restart

schedule-work: ## Inicia o scheduler em loop (foreground - para dev)
	docker compose exec app php artisan schedule:work

schedule: ## Lista tarefas agendadas
	docker compose exec app php artisan schedule:list

# ----------------------------------------
# Operação
# ----------------------------------------
health: ## Verifica saúde dos serviços (PG, Redis, App HTTP)
	@printf "PostgreSQL: "
	@docker compose exec -T postgres pg_isready -U etc_user 2>/dev/null | tail -1 || echo "OFFLINE"
	@printf "Redis:      "
	@docker compose exec -T redis redis-cli ping 2>/dev/null || echo "OFFLINE"
	@printf "App HTTP:   "
	@curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000 --max-time 3 || echo "OFFLINE"
	@printf "Containers: "
	@docker compose ps --services --status=running | wc -l | tr -d ' '
	@echo " serviço(s) rodando"

backup: ## Backup completo (banco + arquivos + .env) em backups/
	@mkdir -p backups
	@TS=$$(date +%Y-%m-%d_%H%M%S); \
	TEMP="backups/_temp_$$TS"; \
	FINAL="backups/etcindustrial-backup_$$TS.tar.gz"; \
	mkdir -p $$TEMP; \
	echo "Fazendo dump do banco..."; \
	docker compose exec -T postgres pg_dump -U etc_user --clean --if-exists --no-owner --no-privileges etcindustrial > $$TEMP/database.sql; \
	echo "Copiando arquivos privados..."; \
	[ -d storage/app/private ] && cp -r storage/app/private $$TEMP/storage-private || true; \
	echo "Copiando arquivos publicos..."; \
	[ -d storage/app/public ] && cp -r storage/app/public $$TEMP/storage-public || true; \
	echo "Salvando .env..."; \
	cp .env $$TEMP/.env.backup 2>/dev/null || true; \
	echo "Empacotando..."; \
	tar -czf $$FINAL -C $$TEMP . && \
	rm -rf $$TEMP && \
	echo "Backup: $$FINAL ($$(du -h $$FINAL | cut -f1))"

backup-db: ## Backup somente do banco
	@mkdir -p backups
	@FILE="backups/db-only_$$(date +%Y-%m-%d_%H%M%S).sql"; \
	docker compose exec -T postgres pg_dump -U etc_user --clean --if-exists --no-owner --no-privileges etcindustrial > $$FILE && \
	echo "Backup: $$FILE ($$(du -h $$FILE | cut -f1))"

backup-list: ## Lista backups disponiveis
	@ls -lh backups/*.tar.gz backups/*.sql 2>/dev/null || echo "Nenhum backup encontrado"

backup-clean: ## Remove backups com mais de 30 dias
	@find backups -name "*.tar.gz" -mtime +30 -delete 2>/dev/null || true
	@find backups -name "*.sql" -mtime +30 -delete 2>/dev/null || true
	@echo "Backups antigos removidos"

restore-db: ## Restaura banco a partir de arquivo (uso: make restore-db F=backups/db.sql)
	@test -n "$(F)" || (echo "Uso: make restore-db F=caminho/do/arquivo.sql"; exit 1)
	@test -f "$(F)" || (echo "Arquivo nao encontrado: $(F)"; exit 1)
	@echo "ATENCAO: vai sobrescrever o banco atual!"
	@read -p "Digite 'RESTAURAR' para confirmar: " confirma; \
	if [ "$$confirma" = "RESTAURAR" ]; then \
		docker compose exec -T postgres psql -U etc_user etcindustrial < $(F); \
		docker compose exec -T app php artisan cache:clear; \
		docker compose exec -T app php artisan queue:restart; \
		echo "Restauracao concluida."; \
	else \
		echo "Cancelado."; \
	fi

logs-install: ## Mostra ultimo log de instalacao/update
	@ls -t logs-instalacao/*.log 2>/dev/null | head -1 | xargs cat || echo "Nenhum log encontrado"

version: ## Mostra versões instaladas (PHP, Node, Postgres, Laravel)
	@echo "PHP:       $$(docker compose exec -T app php --version | head -1)"
	@echo "Composer:  $$(docker compose exec -T app composer --version | head -1)"
	@echo "Node:      $$(docker compose exec -T app node --version)"
	@echo "Laravel:   $$(docker compose exec -T app php artisan --version)"
	@echo "Postgres:  $$(docker compose exec -T postgres postgres --version)"
