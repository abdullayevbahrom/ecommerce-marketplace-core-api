DC = docker compose
PHP = $(DC) exec -u www-data app
DB = $(DC) exec -it db
CI = composer install --prefer-dist --no-progress --no-scripts --no-interaction
CIP = composer install --no-progress --no-scripts --no-interaction --no-dev --optimize-autoloader
BIN = php artisan
DMM = $(BIN) migrate --force
CC = $(BIN) optimize:clear

##################
# Docker compose
##################

dc_build:
	@$(DC) build

dc_start:
	@$(DC) start

dc_stop:
	@$(DC) stop

dc_up:
	@$(DC) up -d --build --remove-orphans

dc_ps:
	@$(DC) ps

dc_logs:
	@$(DC) logs -f

dc_down:
	@$(DC) down -v --rmi=local --remove-orphans



##################
# App
##################

refresh:
	@$(PHP) $(CIP)
	@$(PHP) $(DMM)
	@$(PHP) $(CC)

php_bash:
	@$(PHP) bash

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

prod:
	@$(DC) up -d --build --remove-orphans
	@$(PHP) $(CIP)
	@$(PHP) $(CC)

deploy:
	@docker compose pull
	@docker compose up --force-recreate --build -d --remove-orphans
	@docker image prune -f
	@$(PHP) $(DMM)
	@$(PHP) $(CIP)
	@$(PHP) $(CC)

commit:
	cd project && git pull origin test && git push origin test && git switch master && git pull origin master && git switch test && git merge master && git switch master && git merge test && git push origin master && git switch test && git push origin test