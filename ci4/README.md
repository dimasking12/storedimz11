# DIMZSTORE - CodeIgniter 4

Folder ini berisi versi CI4 dari aplikasi DIMZSTORE. Berjalan **berdampingan**
dengan kode PHP lama di root (`/store.php`, `/admin.php`, `/api/*.php`, dst).
File lama TIDAK diubah, jadi website tetap online selama proses migrasi.

## Apa yang sudah dimigrasi

- **Modul Store (License Store)**
  - `Store::index` -> menggantikan `store.php`
  - `StoreApi::*`  -> menggantikan `api/store.php` untuk endpoint:
    - `GET  /store/api/products`
    - `POST /store/api/validate-voucher`
    - `POST /store/api/create-order`     *(perlu login)*
    - `POST /store/api/create-extend`    *(perlu login)*
    - `GET  /store/api/check-status`

Modul lain (admin, rental, redeem, history, dashboard, login, bot) belum
dimigrasi dan tetap dilayani oleh file lama.

## Struktur Folder

```
ci4/
├── app/
│   ├── Config/          # konfigurasi (DB, Routes, Filters, Security)
│   ├── Controllers/     # Store, StoreApi, BaseController
│   ├── Models/          # ProductModel, UserModel, OrderModel, VoucherModel
│   ├── Filters/         # AuthFilter, SecurityHeadersFilter
│   ├── Helpers/         # legacy_session_helper.php
│   ├── Libraries/       # PaymentService, TelegramNotifier
│   └── Views/           # store/index.php, layouts/main.php, errors/
├── public/              # FRONT CONTROLLER - ini docroot
│   ├── index.php
│   └── .htaccess
├── writable/            # cache, logs, session (auto-created)
├── .env.example         # template environment variable
├── .gitignore
├── .htaccess            # redirect /ci4/* -> /ci4/public/*
├── composer.json
├── spark                # CLI runner
└── README.md
```

## Setup di Hosting (cPanel)

### 1. Install dependency
Buka **cPanel > Terminal**, lalu:

```bash
cd ~/public_html/botdimas/ci4
composer install --no-dev --optimize-autoloader
```

> Kalau composer belum tersedia, instal dulu via:
> `curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer`
> atau hubungi admin hosting.

### 2. Buat file `.env`

```bash
cp .env.example .env
nano .env
```

Isi nilai berikut (sesuai data Anda):

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://vip1120.site/botdimas/ci4/public/'

database.default.hostname = localhost
database.default.database = vipc7892_Dimz_db
database.default.username = vipc7892_Dimas1120
database.default.password = <PASSWORD_DB_BARU>

payment.apiKey      = <API_KEY_ARIE_PAY_BARU>
payment.merchantCode = DIMZ1945

telegram.botToken    = <BOT_TOKEN_BARU>
telegram.adminChatId = 6201552432
```

### 3. Set permission writable

```bash
chmod -R 775 writable
```

### 4. Akses dari browser
Buka `https://vip1120.site/botdimas/ci4/public/store`. Halaman store CI4 akan
muncul dengan UI yang sama persis seperti `/store.php` lama.

## Coexistence dengan Legacy

Sesi PHP **dishare** antara file lama dan CI4 lewat
`app/Helpers/legacy_session_helper.php`. Itu artinya user yang sudah login
di `/login.php` (legacy) langsung dianggap login juga di `/ci4/public/store`.

Cookie session pakai nama `PHPSESSID` (sama dengan PHP default).

## PENTING - Rotate Credentials

File `config.php` lama menyimpan password DB, BOT_TOKEN, dan API_KEY secara
plaintext dan **sudah ter-commit ke git**. Setelah deploy CI4:

1. **Ganti password database** di cPanel.
2. **Generate ulang BOT_TOKEN** Telegram via @BotFather (`/revoke`).
3. **Minta API key baru** dari Arie Pay.
4. Update nilainya di `ci4/.env` (TIDAK di-commit).
5. Update juga `config.php` lama supaya legacy tetap jalan.
6. Jangan lupa: hapus history git lama jika repo public.

## Perintah Berguna

```bash
php spark routes              # lihat semua route CI4
php spark serve               # dev server lokal di :8080
php spark cache:clear         # bersihkan cache
tail -f writable/logs/log-*.php   # lihat log realtime
```

## Roadmap Migrasi Berikutnya (kapan saja)

Modul yang siap dimigrasi pakai pola yang sama:

1. **Auth** (`login.php`, `register.php`, `logout.php`, `profile.php`)
2. **Admin Panel** (`admin.php`, `api/admin.php`)
3. **Rental** (`rental.php`, terkait `api/store.php`)
4. **History & Redeem** (`history.php`, `redeem.php`)
5. **Bot Webhook** (`bot.php`) - paling rumit, hindari migrasi sampai semua
   modul lain stabil di CI4.
