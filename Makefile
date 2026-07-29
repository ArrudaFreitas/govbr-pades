IMAGE  ?= localhost/govbr-pades-dev:7.4
PODMAN ?= podman

RUN = $(PODMAN) run --rm -v $(CURDIR):/app -w /app $(IMAGE)

.DEFAULT_GOAL := help
.PHONY: help image install update lint fix stan test coverage check shell

help: ## List the available targets
	@grep -hE '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "} {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

image: ## Build the development image
	$(PODMAN) build -t $(IMAGE) -f Containerfile .

install: image ## Install the dependencies from composer.lock
	$(RUN) composer install --no-interaction

update: image ## Update the dependencies and rewrite composer.lock
	$(RUN) composer update --no-interaction

lint: ## Check PSR-12 (does not modify files)
	$(RUN) php vendor/bin/phpcs

fix: ## Automatically fix PSR-12 violations
	$(RUN) php vendor/bin/phpcbf

stan: ## Run the PHPStan static analysis
	$(RUN) php vendor/bin/phpstan analyse --no-progress

test: ## Run the PHPUnit suite
	$(RUN) php vendor/bin/phpunit

coverage: ## Run the suite with a coverage report
	$(RUN) php vendor/bin/phpunit --coverage-text

check: lint stan test ## Run lint, static analysis and tests

shell: ## Open a shell inside the container
	$(PODMAN) run --rm -it -v $(CURDIR):/app -w /app $(IMAGE) bash
