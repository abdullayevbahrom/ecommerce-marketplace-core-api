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

deploy:
	@$(DC) pull shop
	@docker rm -f shop || true
	@$(DC) up --force-recreate --build -d --remove-orphans shop
	@$(SHOP) composer install --optimize-autoloader --no-interaction --no-dev --prefer-dist --no-progress --ignore-platform-reqs --no-scripts
	@docker image prune -f

.PHONY: deploy