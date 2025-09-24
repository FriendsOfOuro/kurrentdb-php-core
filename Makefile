PHP=docker compose exec php

.PHONY: up
up:
	docker compose up -d --build

.PHONY: down
down:
	docker compose down

.PHONY: logs
logs:
	docker compose logs -f

.PHONY: install
install:
	$(PHP) composer install --no-interaction

.PHONY: cs-fixer-ci
cs-fixer-ci:
	$(PHP) bin/php-cs-fixer fix --dry-run -v --diff

.PHONY: cs-fixer
cs-fixer:
	$(PHP) bin/php-cs-fixer fix -v

.PHONY: test-coverage
test-coverage:
	$(PHP) bin/phpunit --coverage-text --testdox

.PHONY: test
test:
	$(PHP) bin/phpunit --testdox

.PHONY: phpstan
phpstan:
	$(PHP) bin/phpstan analyse -v

.PHONY: rector
rector:
	$(PHP) bin/rector process

.PHONY: bash
bash:
	@$(PHP) bash

.PHONY: benchmark
benchmark:
	$(PHP) php benchmark/read-stream.php
