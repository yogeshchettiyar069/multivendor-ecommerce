# Convenience wrappers around docker compose. Run `make help` for the list.
.DEFAULT_GOAL := help
.PHONY: help up down stop build fresh logs shell test pint stan seed key

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

up: ## Build and start the full stack (detached)
	docker compose up --build -d

down: ## Stop and remove containers
	docker compose down

stop: ## Stop containers without removing them
	docker compose stop

build: ## Rebuild images
	docker compose build

fresh: ## Wipe volumes, rebuild, migrate + seed from scratch
	docker compose down -v
	docker compose up --build -d

logs: ## Tail application and web logs
	docker compose logs -f app web

shell: ## Open a shell in the app container
	docker compose exec app sh

test: ## Run the test suite (installs dev deps in the container on first run)
	docker compose exec \
		-e APP_ENV=testing -e MONGODB_DATABASE=ecommerce_testing \
		-e CACHE_STORE=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync \
		-e MAIL_MAILER=array -e BCRYPT_ROUNDS=4 \
		app sh -c "touch .env && composer install --no-interaction -q && php artisan test"

pint: ## Run Laravel Pint (code style)
	docker compose exec app ./vendor/bin/pint

stan: ## Run PHPStan static analysis
	docker compose exec app ./vendor/bin/phpstan analyse

seed: ## Seed demo data
	docker compose exec app php artisan db:seed --force

key: ## Generate an APP_KEY value
	docker compose run --rm app php artisan key:generate --show
