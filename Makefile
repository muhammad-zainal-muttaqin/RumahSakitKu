# SIMRS - Sistem Informasi Manajemen Rumah Sakit
# Makefile for development commands

.PHONY: help install analyze format format-check test test-coverage test-unit test-feature \
        migrate migrate-fresh migrate-rollback seed db-reset serve queue horizon \
        cache-clear cache-warm optimize clear-all lint security-check \
        pint pint-fix docs-env lint-staged husky-install

# Default target
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
RED := \033[31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)SIMRS Development Commands$(NC)"
	@echo "=========================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

# ============================================
# Installation & Setup
# ============================================
install: ## Install Composer dependencies
	@echo "$(BLUE)Installing Composer dependencies...$(NC)"
	composer install

install-prod: ## Install Composer dependencies (production - no dev)
	@echo "$(BLUE)Installing Composer dependencies (production)...$(NC)"
	composer install --no-dev --optimize-autoloader --no-interaction

setup: ## Initial project setup
	@echo "$(BLUE)Setting up project...$(NC)"
	cp .env.example .env
	composer install
	php artisan key:generate
	php artisan storage:link
	@echo "$(GREEN)Setup complete! Please configure your .env file and run 'make migrate'$(NC)"

# ============================================
# Code Quality & Static Analysis
# ============================================
analyze: ## Run PHPStan static analysis
	@echo "$(BLUE)Running PHPStan static analysis...$(NC)"
	vendor/bin/phpstan analyse --memory-limit=2G --no-progress

analyze-verbose: ## Run PHPStan with verbose output
	@echo "$(BLUE)Running PHPStan (verbose)...$(NC)"
	vendor/bin/phpstan analyse --memory-limit=2G -v

analyze-generate-baseline: ## Generate PHPStan baseline
	@echo "$(BLUE)Generating PHPStan baseline...$(NC)"
	vendor/bin/phpstan analyse --memory-limit=2G --generate-baseline

format: ## Run PHP CS Fixer to fix code style
	@echo "$(BLUE)Running PHP CS Fixer...$(NC)"
	vendor/bin/php-cs-fixer fix --allow-risky=yes

format-check: ## Check code style without fixing (dry-run)
	@echo "$(BLUE)Checking code style...$(NC)"
	vendor/bin/php-cs-fixer fix --dry-run --diff

pint: ## Run Laravel Pint (code style check)
	@echo "$(BLUE)Running Laravel Pint...$(NC)"
	vendor/bin/pint --test

pint-fix: ## Run Laravel Pint and fix issues
	@echo "$(BLUE)Running Laravel Pint (fix mode)...$(NC)"
	vendor/bin/pint

lint: format-check analyze ## Run all linting checks

lint-staged: ## Run linting on staged files only (for pre-commit hooks)
	@echo "$(BLUE)Running linting on staged files...$(NC)"
	git diff --cached --name-only --diff-filter=ACM | grep "\.php$$" | xargs -I {} vendor/bin/php-cs-fixer fix {} --dry-run --diff || true
	git diff --cached --name-only --diff-filter=ACM | grep "\.php$$" | xargs -I {} vendor/bin/phpstan analyse {} --memory-limit=512M || true

security-check: ## Run security audit on dependencies
	@echo "$(BLUE)Running security audit...$(NC)"
	composer audit

# ============================================
# Testing
# ============================================
test: ## Run PHPUnit tests
	@echo "$(BLUE)Running tests...$(NC)"
	php artisan test

test-parallel: ## Run PHPUnit tests in parallel
	@echo "$(BLUE)Running tests in parallel...$(NC)"
	php artisan test --parallel

test-coverage: ## Run tests with code coverage
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	php artisan test --coverage --coverage-clover=coverage.xml

test-coverage-html: ## Run tests with HTML coverage report
	@echo "$(BLUE)Running tests with HTML coverage...$(NC)"
	php artisan test --coverage --coverage-html=storage/app/coverage

test-unit: ## Run unit tests only
	@echo "$(BLUE)Running unit tests...$(NC)"
	php artisan test --filter=Unit

test-feature: ## Run feature tests only
	@echo "$(BLUE)Running feature tests...$(NC)"
	php artisan test --filter=Feature

test-dusk: ## Run Dusk browser tests
	@echo "$(BLUE)Running Dusk tests...$(NC)"
	php artisan dusk

# ============================================
# Database
# ============================================
migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	@echo "$(YELLOW)Dropping all tables and re-running migrations...$(NC)"
	php artisan migrate:fresh

migrate-rollback: ## Rollback last batch of migrations
	@echo "$(YELLOW)Rolling back migrations...$(NC)"
	php artisan migrate:rollback

migrate-status: ## Show migration status
	@echo "$(BLUE)Migration status:$(NC)"
	php artisan migrate:status

seed: ## Run database seeders
	@echo "$(BLUE)Seeding database...$(NC)"
	php artisan db:seed

fresh: migrate-fresh seed ## Reset database with seeders

db-reset: migrate-fresh seed ## Alias for 'fresh'

db-backup: ## Backup database
	@echo "$(BLUE)Creating database backup...$(NC)"
	php artisan backup:run --only-db || echo "$(YELLOW)Backup package not installed. Install spatie/laravel-backup$(NC)"

# ============================================
# Development Server
# ============================================
serve: ## Start Laravel development server
	@echo "$(GREEN)Starting development server on http://localhost:8000$(NC)"
	php artisan serve --host=0.0.0.0 --port=8000

serve-public: ## Start development server accessible from network
	@echo "$(GREEN)Starting development server on http://0.0.0.0:8000$(NC)"
	php artisan serve --host=0.0.0.0 --port=8000

queue: ## Start queue worker
	@echo "$(BLUE)Starting queue worker...$(NC)"
	php artisan queue:work --sleep=3 --tries=3 --timeout=90

queue-listen: ## Start queue listener
	@echo "$(BLUE)Starting queue listener...$(NC)"
	php artisan queue:listen --sleep=3 --tries=3 --timeout=90

horizon: ## Start Laravel Horizon
	@echo "$(BLUE)Starting Laravel Horizon...$(NC)"
	php artisan horizon

schedule-run: ## Run scheduled tasks (for testing)
	@echo "$(BLUE)Running scheduled tasks...$(NC)"
	php artisan schedule:run

# ============================================
# Caching & Optimization
# ============================================
cache-clear: ## Clear all caches
	@echo "$(BLUE)Clearing caches...$(NC)"
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan event:clear
	php artisan clear-compiled

cache-warm: ## Warm up caches
	@echo "$(BLUE)Warming up caches...$(NC)"
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache
	php artisan event:cache

optimize: cache-clear cache-warm ## Clear and warm caches

route-list: ## List all registered routes
	@echo "$(BLUE)Listing routes...$(NC)"
	php artisan route:list --except-vendor

clear-all: ## Clear all caches and compiled files
	@echo "$(YELLOW)Clearing everything...$(NC)"
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan event:clear
	php artisan clear-compiled
	php artisan optimize:clear
	composer dump-autoload

# ============================================
# Filament Specific
# ============================================
filament-cache: ## Clear Filament cache
	@echo "$(BLUE)Clearing Filament cache...$(NC)"
	php artisan filament:cache-components

filament-clear: ## Clear Filament components
	@echo "$(BLUE)Clearing Filament components...$(NC)"
	php artisan filament:clear-components

filament-upgrade: ## Upgrade Filament
	@echo "$(BLUE)Upgrading Filament...$(NC)"
	php artisan filament:upgrade
	composer dump-autoload
	php artisan filament:cache-components

# ============================================
# Docker
# ============================================
docker-up: ## Start Docker containers
	@echo "$(BLUE)Starting Docker containers...$(NC)"
	docker-compose up -d

docker-down: ## Stop Docker containers
	@echo "$(YELLOW)Stopping Docker containers...$(NC)"
	docker-compose down

docker-build: ## Build Docker containers
	@echo "$(BLUE)Building Docker containers...$(NC)"
	docker-compose build

docker-shell: ## Access Docker shell
	@echo "$(BLUE)Accessing Docker shell...$(NC)"
	docker-compose exec app bash

docker-logs: ## Show Docker logs
	@echo "$(BLUE)Showing Docker logs...$(NC)"
	docker-compose logs -f

# ============================================
# CI/CD
# ============================================
ci: install format-check analyze test ## Run CI pipeline locally

cd-deploy: ## Deploy to production (requires proper setup)
	@echo "$(GREEN)Deploying to production...$(NC)"
	php artisan down
	git pull origin main
	composer install --no-dev --optimize-autoloader
	php artisan migrate --force
	php artisan optimize
	php artisan filament:upgrade
	php artisan queue:restart
	php artisan up
	@echo "$(GREEN)Deployment complete!$(NC)"

# ============================================
# Git Hooks
# ============================================
husky-install: ## Install Husky git hooks
	@echo "$(BLUE)Installing Husky...$(NC)"
	npx husky install
	npx husky add .husky/pre-commit "make lint-staged"

# ============================================
# Documentation
# ============================================
docs-env: ## Generate .env documentation
	@echo "$(BLUE)Generating environment documentation...$(NC)"
	@echo "## Environment Variables" > docs/ENVIRONMENT.md
	@echo "" >> docs/ENVIRONMENT.md
	@echo "Generated on $$(date)" >> docs/ENVIRONMENT.md
	@echo "" >> docs/ENVIRONMENT.md
	@cat .env.example >> docs/ENVIRONMENT.md

# ============================================
# Utility
# ============================================
logs: ## Show application logs
	@echo "$(BLUE)Showing logs...$(NC)"
	tail -f storage/logs/laravel.log

tinker: ## Start Laravel Tinker
	@echo "$(BLUE)Starting Tinker...$(NC)"
	php artisan tinker

model-show: ## Show model information (requires model name)
	@read -p "Enter model name: " model; \
	php artisan model:show $$model

ide-helper: ## Generate IDE helper files
	@echo "$(BLUE)Generating IDE helper files...$(NC)"
	php artisan ide-helper:generate
	php artisan ide-helper:meta
	php artisan ide-helper:models --nowrite
