# AGENTS.md

## Loyiha konteksti
- Nomi: `Choyxona CRM`
- Stack: `Laravel 12`, `PHP 8.4`, `MySQL 8`, `Blade + Alpine.js`, `Tailwind`
- Asosiy ish muhiti: `docker compose`
- Muhim URL: `http://localhost:8080`

## Tez start
- Konteynerlarni ko'tarish: `docker compose up -d --build`
- Servislar holati: `docker compose ps`
- Loglar: `docker compose logs --tail=200 app web worker scheduler`
- Health check: `curl -fsS http://localhost:8080/healthz`

## Standart tekshiruv oqimi
- Kod formati: `vendor/bin/pint --test`
- Testlar: `php artisan test`
- E2E smoke + runtime: `make verify`
- Prodga yaqin tekshiruv ketma-ketligi:
  1. `vendor/bin/pint --test`
  2. `php artisan test`
  3. `docker compose up -d --build app worker scheduler web`
  4. `make verify`

## Frontend va assetlar
- Build: `npm run build`
- Vite manifest: `public/build/manifest.json`
- Muhim: `web` konteyner `./public`ni serve qiladi. Agar UI/CSS eski qolsa:
  1. `npm run build`
  2. `docker compose up -d --build app web`
- Screenshot test (Playwright, local): `storage/app/ui-screenshots/`

## Docker bo'yicha muhim eslatmalar
- `app` image ichidan ishlaydi, shuning uchun Blade/PHP o'zgarishlari uchun odatda rebuild kerak:
  - `docker compose up -d --build app worker scheduler`
- `web` nginx upstream dynamic resolver bilan sozlangan (`deploy/docker/nginx/default.conf`), IP o'zgarishida 502 kamayadi.
- Agar 502 chiqsa:
  1. `docker compose ps`
  2. `docker compose logs --tail=200 web app`
  3. `docker compose up -d --force-recreate web`

## Kodlash qoidalari (shu repo uchun)
- Validation'larni imkon qadar `FormRequest`larda saqla.
- Controller'larni semiz qilma, biznes mantiqni `app/Services`ga joylashtir.
- Katta exportlar uchun streaming/chunk yondashuvdan foydalan.
- Soft-delete o'rniga texnik topshiriqdagi `is_active`/`status` qoidalariga amal qil.
- Bir xonada bitta ochiq buyurtma qoidasi saqlansin (DB + Service + UI darajalarda).

## Responsiveness checklist
- Har yangi sahifa `px-4 sm:px-6 lg:px-8` container bilan boshlansin.
- Filter/form bloklari `grid-cols-1` dan boshlab `sm/xl` breakpoint bilan ochilsin.
- Jadval bo'lsa:
  - wrapper: `overflow-x-auto`
  - table: `min-w-[...] w-full`
- Action buttonlar mobilda `w-full`, desktopda `w-auto`.

## Branding
- Logo component: `resources/views/components/application-logo.blade.php`
- Navbar brand: `resources/views/layouts/navigation.blade.php`
- Favicon: `public/favicon.svg`
- Layout head fayllari:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/guest.blade.php`
  - `resources/views/components/layouts/app.blade.php`

## Locale va timezone
- Kutilgan qiymatlar:
  - `APP_TIMEZONE=Asia/Tashkent`
  - `APP_LOCALE=uz`
- `make verify` chiqishida timezone/locale ni doim tekshir.

## Deploydan oldin minimal checklist
1. `git status` toza.
2. `vendor/bin/pint --test` yashil.
3. `php artisan test` yashil.
4. `make verify` yashil.
5. 390px viewportda kamida `dashboard`, `orders/history`, `menu`, `reports` screenshot tekshirildi.

## Commit qoidasi
- Commitlar kichik va ma'noli bo'lsin (feature/fix/chore scope bilan).
- Tavsiya etilgan author:
  - `abdullayevbahrom <fergusuz1313@gmail.com>`
- Namuna:
  - `git commit --author="abdullayevbahrom <fergusuz1313@gmail.com>" -m "feat(ui): ..."`
- Majburiy ish tartibi:
  1. Har bir patchdan keyin alohida commit qilinadi.
  2. Har bir patchdan keyin `vendor/bin/pint --test` ishlatiladi.
  3. Har bir patchdan keyin `php artisan test` (to'liq test suite) ishlatiladi.
  4. Har bir patchdan keyin `make verify-rebuild` ishlatiladi.
  5. Har bir patchdan keyin docker ichida `php artisan test` (to'liq test suite) ishlatiladi.

## Tez-tez ishlatiladigan buyruqlar
- `php artisan migrate --force`
- `php artisan optimize:clear`
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- `docker compose restart app web`

## Known pitfalls
- `app` rebuild qilinmasa Blade o'zgarishi ko'rinmasligi mumkin.
- `manifest` mismatch bo'lsa CSS/JS 404 bo'ladi.
- `make verify` paytida servis endi start bo'layotgan bo'lsa vaqtincha 502 chiqishi mumkin; 5-10 soniyadan keyin qayta urinish kerak.
