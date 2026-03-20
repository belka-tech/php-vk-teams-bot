PHP_SERVICE = php82

.PHONY: up down build shell install test phpstan

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

shell:
	docker compose exec $(PHP_SERVICE) sh

install:
	docker compose exec $(PHP_SERVICE) composer install

test:
	docker compose exec $(PHP_SERVICE) vendor/bin/phpunit

phpstan:
	docker compose exec $(PHP_SERVICE) vendor/bin/phpstan analyse

setup: build up install
