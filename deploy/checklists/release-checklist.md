# Production Release Checklist (Runbook)

## 1. Preflight (Go/No-Go)

- [ ] `main` branch oxirgi commit deploy qilinadigan commit bilan bir xil.
- [ ] CI yashil: lint/test/build.
- [ ] Lokal yakuniy tekshiruvlar:
  - [ ] `vendor/bin/pint --test`
  - [ ] `php artisan test`
  - [ ] `make verify`
- [ ] Environment qiymatlari tekshirildi:
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_TIMEZONE=Asia/Tashkent`
  - [ ] `APP_LOCALE=uz`
  - [ ] `APP_NAME="Choyxona CRM"`
- [ ] Database backup olindi:
  - [ ] `php artisan backup:database --prune-days=30`
- [ ] Runtime xizmatlar bor:
  - [ ] `supervisorctl status 'choyxona-worker:*' choyxona-scheduler`
  - [ ] Yoki Docker bo'lsa: `docker compose ps`
- [ ] Disk va permission tekshirildi:
  - [ ] `storage/` yoziladi
  - [ ] `bootstrap/cache/` yoziladi

## 2. Deploy

- [ ] Maintenance rejimi yoqildi (agar zero-downtime emas bo'lsa):
  - [ ] `php artisan down`
- [ ] Deploy skript ishga tushirildi:
  - [ ] `./deploy/scripts/deploy.sh`
- [ ] DB migratsiyalar muvaffaqiyatli o'tdi:
  - [ ] `php artisan migrate --force`
- [ ] Build/artifacts yangilandi:
  - [ ] `public/build/manifest.json` mavjud
- [ ] Cache optimizatsiya qilindi:
  - [ ] `php artisan optimize`
- [ ] Queue workerlar restart qilindi:
  - [ ] `php artisan queue:restart`

## 3. Post-Deploy Verify

- [ ] Health endpoint yashil:
  - [ ] `curl -fsS "$APP_URL/healthz"`
  - [ ] `status=ok`, `database=true`, `storage=true`
- [ ] Pending migration yo'q:
  - [ ] `php artisan migrate:status --pending --no-ansi`
- [ ] Runtime xizmatlar ishlayapti:
  - [ ] `./deploy/scripts/check-runtime-services.sh`
- [ ] Web smoke o'tdi:
  - [ ] `./deploy/scripts/smoke-web.sh "$APP_URL"`
- [ ] To'liq post-deploy verify o'tdi:
  - [ ] `./deploy/scripts/post-deploy-verify.sh "$APP_URL"`

## 4. Product Smoke (Manual)

- [ ] Login (`/login`) ishlaydi.
- [ ] Dashboard (`/dashboard`) ochiladi.
- [ ] Xonadan buyurtma ochish ishlaydi.
- [ ] Orderga item qo'shish/miqdor o'zgartirish ishlaydi.
- [ ] Chek yaratish (`/bills/{id}`) ishlaydi.
- [ ] Chek print flow orderni `closed`, roomni `empty` qiladi.
- [ ] Reports (`/reports`) filter/export ishlaydi.
- [ ] Background export navbatga tushib, yuklab olinadi.

## 5. Monitoring (1-2 soat kuzatish)

- [ ] `php artisan monitor:system-health` -> `HEALTHY`.
- [ ] Queue backlog oshmayapti:
  - [ ] `php artisan queue:monitor default --max=100`
- [ ] `storage/logs/laravel.log` da yangi kritic xatolar yo'q.
- [ ] Nginx/PHP-FPM loglarda 502/500 spike yo'q.

## 6. Rollback Plan (Agar kerak bo'lsa)

- [ ] `php artisan down`
- [ ] Oxirgi barqaror tag/commit checkout.
- [ ] `composer install --no-dev --prefer-dist`
- [ ] `.env`ni rollback versiyaga moslash.
- [ ] Migratsiya strategiyasi:
  - [ ] backward compatible bo'lsa `php artisan migrate --force`
  - [ ] aks holda oldindan tayyor rollback SQL/script ishlatish
- [ ] `php artisan optimize`
- [ ] `php artisan queue:restart`
- [ ] `php artisan up`
- [ ] `./deploy/scripts/post-deploy-verify.sh "$APP_URL"` qayta tekshiruv.

## 7. Release Close

- [ ] Release note yozildi (nima deploy bo'lgani).
- [ ] Incident bo'lmasa deploy "successful" deb yopildi.
- [ ] Agar issue topilgan bo'lsa postmortem/task ochildi.
