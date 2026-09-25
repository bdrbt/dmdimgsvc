.PHONY: help up down test test-pest test-k6 swagger clear-cache restart rebuild

help: ## Shows available subcommands
	@cat $(MAKEFILE_LIST) | grep -E '^[a-zA-Z_-]+:.*?## .*$$' | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

up: ## Run docker stack
	docker compose up -d

down: ## Remove docker stack
	docker compose down --remove-orphans

test: test-pest test-k6 ## Run all tests

test-pest: ## Run pest tests locally
	./vendor/bin/pest

test-k6: rebuild ## Run k6 integration tests
	docker compose --profile testing run --rm -e BASE_URL="http://web:80" k6

swagger: ## Generate OpenAPI/Swagger documentation
	docker compose exec app php artisan l5-swagger:generate

clear-cache: ## Clear all caches
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan cache:clear

rebuild: ## Rebuild app container image
	docker compose up -d --build
