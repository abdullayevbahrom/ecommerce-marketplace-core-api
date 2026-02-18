DC = docker compose
SHOP = $(DC) exec -u www-data shop
DB = $(DC) exec db

dc_up:
	@$(DC) up -d --build --remove-orphans shop
	@sleep 60
	@$(SHOP) composer install --optimize-autoloader --no-interaction --no-dev --prefer-dist --no-progress --ignore-platform-reqs --no-scripts
dc_ps:
	@$(DC) ps

dc_logs:
	@$(DC) logs -f

dc_down:
	@$(DC) down -v --rmi=local --remove-orphans 

bash:
	@$(DC) exec -it -u www-data shop bash

db_bash:
	@$(DB) bash

php_ci:
	@$(PHP) $(CI)

composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(PHP) composer $(c)

bc: ## Run artisan, pass the parameter "c=" to run a given command, example: make bc c='make:entity'
	@$(eval c ?=)
	@$(PHP) $(BIN) $(c)

restart:
	@$(DC) down --rmi=local --remove-orphans
	@$(DC) up -d --build --remove-orphans
	@$(PHP) $(CC)
	@$(PHP) composer install
	@$(PHP) bash
