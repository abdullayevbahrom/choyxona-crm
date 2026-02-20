# Choyxona CRM

Choyxona uchun xona, buyurtma, chek va hisobotlarni boshqarish tizimi.

## Texnologiyalar

- PHP 8.3+
- Laravel 12
- MySQL 8 (test/dev uchun SQLite ham ishlaydi)
- Blade + Alpine.js + Tailwind
- DomPDF (chek PDF)

## Asosiy modullar

- Xonalar paneli (`/dashboard`)
- Buyurtma yaratish va boshqarish
- Menyu CRUD (`/menu`)
- Foydalanuvchi boshqaruvi (`/users`, admin-only)
- Chek yaratish, print va PDF (`/bills/{id}`)
- Hisobotlar (`/reports`)
- Role-based access (`admin`, `manager`, `cashier`)
- Activity log (`/activity-logs`, admin-only)

## Tez ishga tushirish (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Queue worker alohida oynada:

```bash
php artisan queue:work --tries=3
```

Scheduler local tekshiruv:

```bash
php artisan schedule:work
```

## Demo foydalanuvchilar

Seeddan keyin quyidagi loginlar yaratiladi (`password`):

- `admin@choyxona.uz`
- `manager@choyxona.uz`
- `cashier@choyxona.uz`

## Muhim endpointlar

- App health (Laravel): `/up`
- Readiness check: `/healthz`
- Dashboard: `/dashboard`

`/healthz` JSON qaytaradi va DB yoki storage muammosi bo'lsa `503` beradi.

## Registration siyosati

- Public registration default o'chirilgan: `ALLOW_PUBLIC_REGISTRATION=false`
- Ichki CRM sifatida foydalanuvchilar admin paneldan (`/users`) boshqariladi.

## Testlar

```bash
php artisan test
```

## Docker (local/prod smoke)

```bash
docker compose build
docker compose up -d
```

App: `http://localhost:8080`

Izoh: compose stack ichida `app`, `worker`, `scheduler`, `db`, `redis` servislar bor.

## CI/CD

- CI workflow: `.github/workflows/ci.yml`
  - PHP dependency install
  - Node build
  - migrate
  - `php artisan test`
- Deploy workflow: `.github/workflows/deploy.yml`
  - `main` branch push yoki manual trigger
  - SSH orqali serverda `deploy/scripts/deploy.sh` ni ishga tushiradi
- Secrets sozlamalari: `deploy/github-secrets.md`

## Production deploy qisqa yo'riqnoma

1. Kodni serverga joylashtiring.
2. `.env` ni production qiymatlar bilan to'ldiring (`APP_ENV=production`, `APP_DEBUG=false`).
   Boshlang'ich nusxa uchun `.env.production.example` dan foydalaning.
3. Dependency va build:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force
npm ci
npm run build
php artisan optimize
```

4. Queue worker va scheduler ni yoqing (namunalar `deploy/` ichida).
5. Web serverni `public/` ga yo'naltiring.

## Deploy konfiguratsiya namunalari

- `deploy/nginx/choyxona.conf`
- `deploy/supervisor/choyxona-worker.conf`
- `deploy/systemd/choyxona-scheduler.service`
- `deploy/systemd/choyxona-scheduler.timer`
- `deploy/logrotate/choyxona`
- `deploy/checklists/release-checklist.md`
- `deploy/github-secrets.md`
- `deploy/scripts/post-deploy-verify.sh`

## Operatsion buyruqlar

- Eski activity loglarni tozalash:

```bash
php artisan activity-logs:prune --days=90
```

- DB backup olish (gzip):

```bash
php artisan backup:database --prune-days=30
```

- Queue worker restart:

```bash
php artisan queue:restart
```

- Runtime service tekshiruvi (queue/scheduler):

```bash
./deploy/scripts/check-runtime-services.sh
```

- Web smoke tekshiruvi (login + dashboard + reports):

```bash
SMOKE_EMAIL=manager@choyxona.uz SMOKE_PASSWORD=password ./deploy/scripts/smoke-web.sh "$APP_URL"
```

- To'liq post-deploy verify (migrate pending + health + runtime + smoke):

```bash
./deploy/scripts/post-deploy-verify.sh "$APP_URL"
```

## Observability

- Har bir javobda `X-Request-ID` header qaytadi.
- Sekin so'rovlar `performance` log kanaliga yoziladi.
- Sozlamalar:
  - `OBS_ENABLED=true|false`
  - `OBS_SLOW_REQUEST_MS=700`
  - `LOG_PERFORMANCE_LEVEL=warning`
  - `LOG_PERFORMANCE_DAYS=14`

## Performance

- Hisobotlar (`/reports`) qisqa muddatli cache bilan ishlaydi.
- Sozlama: `REPORT_CACHE_SECONDS=30`
- Katta hisobotlar uchun kunlik summary jadvallar ishlatiladi (umumiy, xona, kassir kesimida).
- Summary refresh buyrug'i: `php artisan reports:refresh-daily-summaries --days=400`

## Security

- Global response security headerlar yoqilgan:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- Productionda qo'shimcha:
  - `Strict-Transport-Security`
