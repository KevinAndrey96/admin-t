.PHONY: env key up down logs credentials e2e install test analyse lint fix check

COMPOSER = docker run --rm -v "$(CURDIR)":/app -w /app composer:2

env: ## Creates .env with a fresh APP_KEY
	@test -f .env || { cp .env.example .env && sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:$$(openssl rand -base64 32)|" .env && rm -f .env.bak && echo '.env created'; }

key: ## Prints a new APP_KEY
	@echo "base64:$$(openssl rand -base64 32)"

up: env ## Panel + demo WooCommerce store
	docker compose up -d --build --wait panel
	@$(MAKE) --no-print-directory credentials

down:
	docker compose down

logs:
	docker compose logs -f panel

credentials: ## Sign-in data for the demo store
	@docker compose logs --no-log-prefix store-setup | grep -E '^(Panel|WordPress admin):' | tail -n 2

e2e: ## Full flow against the running demo store
	bash docker/e2e.sh

install:
	$(COMPOSER) composer install

test:
	$(COMPOSER) vendor/bin/phpunit

analyse:
	$(COMPOSER) vendor/bin/phpstan analyse --memory-limit=512M

lint:
	$(COMPOSER) vendor/bin/php-cs-fixer check --diff

fix:
	$(COMPOSER) vendor/bin/php-cs-fixer fix

check: lint analyse test
