# Release Checklist

## Pre-Release

- [ ] `main` branch yashil CI (`.github/workflows/ci.yml`)
- [ ] GitHub Actions secrets kiritilgan (`deploy/github-secrets.md`)
- [ ] `php artisan test` lokalda muammosiz
- [ ] `.env` production qiymatlar tekshirildi (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] DB backup ishga tushdi: `php artisan backup:database --prune-days=30`
- [ ] `storage/` va `bootstrap/cache/` yozish huquqlari to'g'ri
- [ ] Queue worker va scheduler aktiv

## Deploy

- [ ] `./deploy/scripts/deploy.sh` bajarildi
- [ ] Migratsiyalar `--force` bilan muvaffaqiyatli o'tdi
- [ ] Frontend build chiqdi (`public/build` mavjud)
- [ ] `php artisan optimize` muvaffaqiyatli bajarildi

## Post-Release Smoke

- [ ] `/healthz` `200` va `status=ok`
- [ ] Login ishlaydi
- [ ] Dashboard (`/dashboard`) ochiladi
- [ ] Xonada buyurtma ochish va item qo'shish ishlaydi
- [ ] Chek yaratish/print flow ishlaydi
- [ ] Queue'da stuck job yo'q (`php artisan queue:monitor default --max=100`)

## Rollback (agar kerak bo'lsa)

- [ ] `php artisan down`
- [ ] Oxirgi barqaror commitga qaytish
- [ ] `composer install --no-dev`
- [ ] `php artisan migrate --force` (yoki oldindan tayyor rollback rejasi)
- [ ] `php artisan optimize`
- [ ] `php artisan queue:restart`
- [ ] `php artisan up`
