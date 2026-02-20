APP_URL ?= http://localhost:8080
SMOKE_EMAIL ?= manager@choyxona.uz
SMOKE_PASSWORD ?= password
PRUNE_DAYS ?= 30

.PHONY: help up down restart rebuild ps logs verify smoke runtime test backup restore-smoke backup-list backup-prune

help:
	@echo "Targets:"
	@echo "  make up       - Docker compose ni ko'tarish"
	@echo "  make down     - Docker compose ni to'xtatish"
	@echo "  make restart  - Docker compose restart"
	@echo "  make rebuild  - Docker image qayta build + up"
	@echo "  make ps       - Container holatini ko'rsatish"
	@echo "  make logs     - Asosiy servis loglarini ko'rsatish"
	@echo "  make verify   - Docker post-deploy verify"
	@echo "  make smoke    - Web smoke check"
	@echo "  make runtime  - Runtime process check"
	@echo "  make test     - Feature sanity testlar"
	@echo "  make backup   - DB backup olish (container ichida)"
	@echo "  make restore-smoke - Backup archive/dump smoke check"
	@echo "  make backup-list - Backup fayllar ro'yxati"
	@echo "  make backup-prune - Eski backup fayllarni tozalash"

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

rebuild:
	docker compose up -d --build

ps:
	docker compose ps

logs:
	docker compose logs --tail=120 app worker scheduler db redis

verify:
	./deploy/scripts/post-deploy-verify.sh --docker "$(APP_URL)"

smoke:
	SMOKE_EMAIL="$(SMOKE_EMAIL)" SMOKE_PASSWORD="$(SMOKE_PASSWORD)" ./deploy/scripts/smoke-web.sh "$(APP_URL)"

runtime:
	./deploy/scripts/check-runtime-services.sh --docker

test:
	php artisan test --testsuite=Feature --filter='HealthCheckTest|LocalizationValidationTest|ReportBackgroundExportTest'

backup:
	./deploy/scripts/backup-restore-smoke.sh --docker --backup-only

restore-smoke:
	./deploy/scripts/backup-restore-smoke.sh --docker

backup-list:
	docker compose exec -T app sh -lc "ls -lah storage/app/backups/databases || true"

backup-prune:
	docker compose exec -T app sh -lc "find storage/app/backups/databases -type f -name 'db-*.sql.gz' -mtime +$(PRUNE_DAYS) -print -delete"
