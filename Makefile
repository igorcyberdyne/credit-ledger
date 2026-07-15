############################################################
# GLOBAL SETTINGS
############################################################

SHELL := /bin/sh
.DEFAULT_GOAL := help

V ?= 0
Q = $(if $(filter 1,$(V)),,@)

# Disable colors in CI
NO_COLOR ?= 0
ifeq ($(CI),1)
    NO_COLOR=1
endif

ifeq ($(NO_COLOR),1)
BLUE =
YEL  =
GRN  =
RES  =
else
BLUE = \033[34;1m
YEL  = \033[93;1m
GRN  = \033[32;1m
RES  = \033[0m
endif

ARROW = $(BLUE)▶$(RES)
SUB   = $(YEL)|--▶$(RES)

############################################################
# ENV-AWARE BUILD DIR (CI or local)
############################################################

ifeq ($(CI),1)
    BUILD_DIR = buildx/ci
else
    BUILD_DIR = buildx
endif

$(BUILD_DIR):
	@mkdir -p $(BUILD_DIR)

# -------- 🔧 VARIABLES --------
PROJECT_NAME = ekolo-transfer
DC = docker compose
ENV ?= dev
TARGET_DB ?= dev
PHP_CONTAINER = php
NODE_CONTAINER = node
DATABASE_CONTAINER = database

############################################################
# TOOLS autodetection
############################################################

# Detect Docker
HAS_DOCKER := $(shell command -v docker >/dev/null 2>&1 && echo yes)
HAS_DOCKER_COMPOSE := $(shell command -v docker compose >/dev/null 2>&1 && echo yes)

# Detect if php container is running
PHP_CONTAINER_STATUS := $(shell $(DC) ps --services --filter "status=running" | grep -w $(PHP_CONTAINER) >/dev/null 2>&1 && echo running)

# Determine PHP command priority: Docker → Symfony CLI → local PHP
ifeq ($(HAS_DOCKER),yes)
	ifeq ($(HAS_DOCKER_COMPOSE),yes)
		ifeq ($(PHP_CONTAINER_STATUS),running)
			PHP = $(DC) exec $(PHP_CONTAINER) php
			COMPOSER = $(DC) exec $(PHP_CONTAINER) composer
			CONSOLE = $(DC) exec $(PHP_CONTAINER) php bin/console
		endif
	endif
endif

# If Docker not available or container not running → fallback to local (Symfony CLI)
ifeq ($(PHP),)
	HASSF := $(shell command -v symfony >/dev/null 2>&1 && echo yes)

	PHP      = $(if $(HASSF),symfony php,php)
	COMPOSER = $(if $(HASSF),symfony composer,composer)
	CONSOLE  = $(if $(HASSF),symfony console,php bin/console)
endif


############################################################
# LOCAL TESTS — BASIC
############################################################

.PHONY: test
test: local-requirements symfony-lint test-kernel test-lost-dev-code test-all phpunit dump-env-dev ## Full testing suite

############################################################
# CI TARGETS (GitHub Actions)
############################################################

.PHONY: ci
ci: local-requirements symfony-lint test-kernel test-lost-dev-code test-all-ci generate-keypair phpunit ## Full CI pipeline
# test-kernel --> test-composer

.PHONY: test-all
test-all: test-schema test-routing test-orphan-tests test-cs-fixer ## Full advanced testing suite

.PHONY: test-all-ci
test-all-ci: test-schema test-routing test-orphan-tests test-cs-fixer ## Full advanced testing suite
# test-migrations <--> test-schema


############################################################
# REQUIREMENTS
############################################################

.PHONY: local-requirements
local-requirements: ## Show installed tools versions
	$Q $(PHP) --version
	$Q $(PHP) vendor/bin/php-cs-fixer --version
	$Q $(COMPOSER) --version


# -------- 🧪 TESTS & QUALITÉ --------
############################################################
# CODE QUALITY
############################################################

.PHONY: cs-fixer
cs-fixer: ## Run php-cs-fixer
	@echo "$(ARROW) Applying CS Fixer…"
	$Q $(PHP) vendor/bin/php-cs-fixer fix

.PHONY: test-cs-fixer
test-cs-fixer: ## Dry-run CS Fixer
	@echo "$(ARROW) Testing CS Fixer…"
	$Q $(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --no-ansi

.PHONY: generate-keypair
generate-keypair:
	@echo "$(ARROW) Generate keypair"
	$Q $(CONSOLE) lexik:jwt:generate-keypair

############################################################
# CODE QUALITY REPORTS
############################################################

.PHONY: phpunit-report
phpunit-report: $(BUILD_DIR) ## Generate HTML coverage report
	$Q APP_ENV=test $(PHP) vendor/bin/phpunit --coverage-html $(BUILD_DIR)/coverage
	@echo "$(GRN)Coverage HTML: $(BUILD_DIR)/coverage/index.html$(RES)"

############################################################
# SYMFONY LINT
############################################################

.PHONY: symfony-lint
symfony-lint: ## Lint Symfony configuration
	@echo "$(ARROW) Linting Symfony…"
	$Q $(CONSOLE) lint:container
	$Q $(CONSOLE) lint:yaml config

.PHONY: test-kernel
test-kernel: ## Check Symfony kernel boot
	@echo "$(ARROW) Checking Symfony kernel…"
	$Q $(CONSOLE) list >/dev/null

.PHONY: test-lost-dev-code
test-lost-dev-code: ## Detect var_dump, dump, die
	@echo "$(ARROW) Checking for debug leftovers…"
	$Q if grep -rnw src tests \
    		--exclude='dump.sql' \
    		-e 'var_dump' -e ' dump' -e 'die'; then \
    		echo "$(YEL)Debug code found!$(RES)"; exit 1; \
    	else \
    		echo "$(GRN)Nothing found.$(RES)"; \
    	fi


############################################################
# TESTS — ADVANCED SUITE
############################################################

.PHONY: test-migrations
test-migrations: ## Detect migration divergence
	@echo "$(ARROW) Checking migrations…"
	$Q $(CONSOLE) doctrine:migrations:diff --dry-run >/dev/null 2>&1 && \
		echo "$(GRN)No pending migration.$(RES)" || \
		(echo "$(YEL)Migration divergence detected!$(RES)"; exit 1)

.PHONY: test-schema
test-schema: ## Validate Doctrine schema
	@echo "$(ARROW) Validating schema…"
	$Q $(CONSOLE) doctrine:schema:validate --skip-sync > /dev/null

.PHONY: test-fixtures
test-fixtures: ## Validate fixtures syntax (YAML + PHP)
	@echo "$(ARROW) Checking fixtures…"
	$Q find src/DataFixtures -type f \
		\( -name '*.yaml' -o -name '*.yml' -o -name '*.json' -o -name '*.php' \) \
		-print0 | while IFS= read -r -d '' file; do \
			case "$$file" in \
				*.php) \
					echo "Lint PHP: $$file"; \
					php -l "$$file" || exit 1 ;; \
				*) \
					echo "Lint YAML: $$file"; \
					$(CONSOLE) lint:yaml "$$file" || exit 1 ;; \
			esac; \
		done

.PHONY: test-routing
test-routing: ## Validate routing
	@echo "$(ARROW) Checking routes…"
	$Q $(CONSOLE) debug:router > /dev/null

.PHONY: test-twig
test-twig: ## Check twig syntax
	@echo "$(ARROW) Checking Twig…"
	$Q $(CONSOLE) lint:twig templates

.PHONY: test-orphan-tests
test-orphan-tests: ## Vérifie tous les fichiers PHPUnit *Test.php
	@echo "→ Deep checking PHPUnit test files…"
	$Q find tests -type f -name '*Test.php' -print0 | while IFS= read -r -d '' file; do \
		echo "Checking $$file"; \
		class=$$(grep -Eo 'class[[:space:]]+[A-Za-z0-9_]+' "$$file" | awk '{print $$2}'); \
		if [ -z "$$class" ]; then \
			echo "  ✗ No Test class declared"; exit 1; \
		fi; \
		filename=$$(basename "$$file" .php); \
		if [ "$$filename" != "$$class" ]; then \
			echo "  ✗ Filename does not match class name ($$filename != $$class)"; exit 1; \
		fi; \
		if ! grep -qE "(public function test|#\\[Test\\])" "$$file"; then \
			echo "  ✗ No test methods found (test* or #[Test])"; exit 1; \
		fi; \
		echo "  ✓ OK"; \
	done
	@echo "All PHPUnit test files are valid."

############################################################
# PHPUnit
############################################################

.PHONY: phpunit
phpunit: cache-and-log-remove ## Run PHPUnit
	@echo "$(ARROW) Running PHPUnit…"
	$Q APP_ENV=test SYMFONY_DEPRECATIONS_HELPER=disabled \
		$(PHP) vendor/bin/phpunit $(if $(filter),--filter "$(filter)") $(if $(path),"$(path)")

.PHONY: phpunit-with-coverage
phpunit-with-coverage: cache-and-log-remove ## PHPUnit + coverage
	@echo "$(ARROW) Running PHPUnit with coverage…"
	$Q rm -rf var/storage/test/*
	$Q APP_ENV=test SYMFONY_DEPRECATIONS_HELPER=disabled \
		$(PHP) vendor/bin/phpunit $(if $(filter),--filter "$(filter)") $(if $(path),"$(path)") \
		--coverage-clover coverage.xml


# -------- ⚙️ BUILD & RUN --------

deploy-prod:
	@echo "🔧 Deploy symfony project for prod env"
	@$(MAKE) cache-and-log-remove
	@$(MAKE) npm-build-remove
	@$(MAKE) vendor-remove
	@$(MAKE) composer-install-prod
	@$(MAKE) dump-env-prod
	@$(MAKE) cache-and-log-remove
	@$(MAKE) npm-prod

deploy-dev:
	@echo "🔧 Deploy symfony project for dev env"
	@$(MAKE) cache-and-log-remove
	@$(MAKE) npm-build-remove
	@$(MAKE) vendor-remove
	@$(MAKE) composer-install
	@$(MAKE) dump-env-dev
	@$(MAKE) cache-and-log-remove
	@$(MAKE) npm-prod

build:
	@echo "🔧 Build des conteneurs Docker..."
	@$(MAKE) vendor-remove
	@$(MAKE) cache-and-log-remove
	$(DC) build
	@$(MAKE) up
	@$(MAKE) composer-install

build-with-fixtures-remake: clean build-with-fixtures
build-with-fixtures:
	@echo "🔧 Build et initialisation de la db avec des fixtures"
	@$(MAKE) build
	@$(MAKE) migrations-remove
	@$(MAKE) migration
	@$(MAKE) migrate
	@$(MAKE) fixtures
	@$(MAKE) migrations-remove
	@$(MAKE) cache-clear

build-with-dump-remake: clean build-with-dump
build-with-dump:
	@echo "🔧 Build et création de la db avec un dump"
	@$(MAKE) build
	@$(MAKE) migrations-remove
	@$(MAKE) migration
	@$(MAKE) migrate
	@$(MAKE) import-dump
	@$(MAKE) anonymize-database
	@$(MAKE) migrations-remove
	@$(MAKE) cache-clear

up:
	@echo "🚀 Démarrage des services Docker..."
	$(DC) up -d

restart:
	@echo "♻️ Redémarrage des services Docker..."
	$(DC) down --remove-orphans && $(DC) up -d

reset-db-with-fixtures:
	@echo "♻️ Refaire la base de données $(ENV) avec les fixtures..."
	@$(MAKE) cache-and-log-remove
	-@$(MAKE) ddd
	@$(MAKE) ddc
	@$(MAKE) migrate
	@$(MAKE) fixtures
	@$(MAKE) cache-and-log-remove

reset-db-with-dump:
	@echo "♻️ Refaire la base de données avec un dump..."
	@$(MAKE) cache-and-log-remove
	@$(MAKE) ddd
	@$(MAKE) ddc
	@$(MAKE) migrations-remove
	@$(MAKE) migration
	@$(MAKE) migrate
	@$(MAKE) import-dump
	@$(MAKE) anonymize-database
	@$(MAKE) migrations-remove
	@$(MAKE) cache-and-log-remove

.PHONY: reset-db-with-fixtures-test
reset-db-with-fixtures-test:
	@$(MAKE) dump-env-test
	@$(MAKE) reset-db-with-fixtures ENV=test TARGET_DB=test

.PHONY: reset-migration-with-db-fixtures
reset-migration-with-db-fixtures:
	@if [ "$(SKIP_MIGRATION)" != "1" ]; then \
		$(MAKE) migrations-remove; \
	fi
	-@$(MAKE) ddd
	@$(MAKE) ddc
	@if [ "$(SKIP_MIGRATION)" != "1" ]; then \
		$(MAKE) migration; \
	fi
	@$(MAKE) migrate
	@$(MAKE) fixtures
	@$(MAKE) cache-and-log-remove

.PHONY: reset-migration-with-db-fixtures-test
reset-migration-with-db-fixtures-test:
	@$(MAKE) dump-env-test
	@$(MAKE) reset-migration-with-db-fixtures ENV=test TARGET_DB=test SKIP_MIGRATION=1

sh:
	$(DC) exec $(PHP_CONTAINER) sh

bash:
	$(DC) exec $(PHP_CONTAINER) bash

down:
	@echo "🛑 Arrêt des conteneurs..."
	$(DC) down --remove-orphans

clean:
	@echo "🧹 Suppression complète (containers, images, volumes)..."
	$(DC) down --rmi all --volumes --remove-orphans
	docker system prune -f

logs:
	@echo "📜 Logs Docker..."
	$(DC) logs -f

ps:
	$(DC) ps

bash-db:
	@echo "mysql -uekolo_transfer -pekolo_transfer ekolo_transfer"
	$(DC) exec $(DATABASE_CONTAINER) bash

import-dump:
	@echo "Import complete data"
	$(DC) exec -T $(DATABASE_CONTAINER) mysql -uekolo_transfer -pekolo_transfer ekolo_transfer < 'migrations/dump.sql'

rebuild: clean build up

# -------- 📦 DEPENDANCES --------

composer-install:
	@echo "📦 Installation des dépendances PHP..."
	$(DC) exec $(PHP_CONTAINER) composer install --no-interaction --prefer-dist
	# $(DC) exec $(PHP_CONTAINER) php vendor/bin/requirements-checke

composer-install-prod:
	@echo "📦 Installation des dépendances PHP pour prod..."
	$(DC) exec $(PHP_CONTAINER) composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

composer-update:
	@echo "⬆️  Mise à jour des dépendances PHP..."
	$(DC) exec $(PHP_CONTAINER) composer update

dump-env:
	@echo "🔐 Dump de l'env $(ENV) de Symfony..."
	$(DC) exec $(PHP_CONTAINER) composer dump-env $(ENV)

dump-env-prod:
	@$(MAKE) dump-env ENV=prod

dump-env-dev:
	@$(MAKE) dump-env ENV=dev

dump-env-test:
	@$(MAKE) dump-env ENV=test

npm-install:
	@echo "📦 Installation des dépendances JS (via npm)..."
	$(DC) run --rm $(NODE_CONTAINER) npm install

npm-dev:
	@echo "🏗️ Compilation Webpack (dev)..."
	$(DC) run --rm $(NODE_CONTAINER) npm run dev

npm-prod:
	@echo "🏗️ Compilation Webpack (prod)..."
	$(DC) run --rm $(NODE_CONTAINER) npm run build

npm-watch:
	@echo "👀 Watcher Webpack en cours..."
	$(DC) run --rm $(NODE_CONTAINER) npm run watch

npm-run:
	@$(DC) run --rm $(NODE_CONTAINER) npm run $(cmd)

# -------- 🧰 SYMFONY --------

console:
	$Q $(CONSOLE) -e $(ENV)

sf: console

ddd:
	$Q $(CONSOLE) doctrine:database:drop --force -e $(ENV)

ddc:
	$Q $(CONSOLE) doctrine:database:create -e $(ENV)

ds:
	$Q $(CONSOLE) doctrine:migrations:status -e $(ENV)

sync-migration:
	@echo "🔄 Synchronisation du metadata storage..."
	$Q $(CONSOLE) doctrine:migrations:sync-metadata-storage -e $(ENV)

regenerate-entity:
	@echo "Regénère les getters et setters des entités..."
	$Q $(CONSOLE) make:entity App --regenerate --overwrite -e $(ENV)

migrations-date:
	@echo "✅ Vérification de l'état des migrations..."
	$Q $(CONSOLE) doctrine:migrations:up-to-date -e $(ENV)

migration:
	$Q $(CONSOLE) make:migration -n -e $(ENV)

migrate:
	$Q $(CONSOLE) doctrine:migrations:migrate -n -e $(ENV)

fixtures:
	$Q $(CONSOLE) doctrine:fixtures:load -n -e $(ENV)

cache-clear:
	@echo "🔥 Netoyage du cache..."
	$Q $(CONSOLE) cache:clear -e $(ENV)

cache-and-log-remove:
	@echo "🔥 Supprimer le dossier de log et de cache..."
	rm -rf var/cache
	rm -rf var/log

npm-build-remove:
	@echo "🔥 Supprimer le build des assets..."
	rm -rf public/build

vendor-remove:
	@echo "🔥 Supprimer le dossier vendor..."
	rm -rf vendor

data-remove:
	@echo "🔥 Supprimer le contenu du dossier data..."
	rm -rf data

migrations-remove:
	@echo "🔥 Supprimer les fichiers dans /migrations/*php"
	rm -f migrations/*.php

cache-warmup:
	@echo "🔥 Réchauffage du cache..."
	$Q $(CONSOLE) cache:warmup -e $(ENV)

anonymize-database:
	@echo "🔥 Anonymize database..."
	$Q $(CONSOLE) app:anonymize-database -e $(ENV)

# -------- 📨 MESSENGER --------

worker:
	@echo "📨 Lancement du consumer Messenger (async)..."
	$Q $(CONSOLE) messenger:consume async --no-interaction --memory-limit=2048M --sleep=1 -e $(ENV)

worker-start:
	@echo "🚀 Démarrage du worker Messenger..."
	$(DC) up -d --build worker

worker-stop:
	@echo "🛑 Arrêt du worker Messenger..."
	$(DC) stop worker

worker-logs:
	@echo "📜 Logs du worker Messenger..."
	$(DC) logs -f worker

worker-restart: worker-stop worker-start

cs-fix:
	$(DC) exec $(PHP_CONTAINER) ./vendor/bin/php-cs-fixer fix src --verbose

# -------- 🧾 AIDE --------

help:
	@echo "📖 Commandes disponibles :"
	@grep -E '^[a-zA-Z0-9_-]+:?' Makefile | cut -d ":" -f1 | sort | uniq | awk '{ printf "  \033[36m%-20s\033[0m\n", $$1 }'
