.DEFAULT_GOAL := help
DOCKER_COMPOSE = docker compose
APP = $(DOCKER_COMPOSE) exec publicgraph-web

##
## Docker
## ------

.PHONY: up
up: ## Start all containers
	$(DOCKER_COMPOSE) up -d --build

.PHONY: down
down: ## Stop all containers
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: shell
shell: ## Open a shell in the publicgraph-web container
	$(DOCKER_COMPOSE) exec publicgraph-web bash

.PHONY: logs
logs: ## Tail logs from all containers
	$(DOCKER_COMPOSE) logs -f

.PHONY: logs-app
logs-app: ## Tail logs from the publicgraph-web container only
	$(DOCKER_COMPOSE) logs -f publicgraph-web

##
## Symfony
## -------

.PHONY: install
install: ## Install Composer dependencies
	$(APP) composer install --no-interaction

.PHONY: cc
cc: ## Clear Symfony cache
	$(APP) php bin/console cache:clear

.PHONY: migration
migration: ## Generate a new Doctrine migration
	$(APP) php bin/console make:migration

.PHONY: migrate
migrate: ## Run pending migrations
	$(APP) php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: fixtures-dev
fixtures-dev: ## Load dev dataset
	$(APP) php bin/console doctrine:fixtures:load --no-interaction --group=dev

.PHONY: fixtures-test
fixtures-test: ## Load minimal test dataset
	$(APP) php bin/console doctrine:fixtures:load --no-interaction --group=test

##
## Quality
## -------

.PHONY: test
test: ## Run PHPUnit test suite
	$(APP) php bin/phpunit

.PHONY: lint
lint: ## Run php-cs-fixer + PHPStan
	$(APP) vendor/bin/php-cs-fixer fix --diff
	$(APP) vendor/bin/phpstan analyse

.PHONY: cs-fix
cs-fix: ## Fix code style only
	$(APP) vendor/bin/php-cs-fixer fix

.PHONY: phpstan
phpstan: ## Run PHPStan only
	$(APP) vendor/bin/phpstan analyse

##
## Database
## --------

.PHONY: db-create
db-create: ## Create the database
	$(APP) php bin/console doctrine:database:create --if-not-exists

.PHONY: db-drop
db-drop: ## Drop the database (DESTRUCTIVE)
	$(APP) php bin/console doctrine:database:drop --force --if-exists

.PHONY: db-reset
db-reset: db-drop db-create migrate fixtures-dev ## Reset DB and reload dev fixtures

##
## Help
## ----

.PHONY: help
help: ## Show this help
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m## /[33m/'
