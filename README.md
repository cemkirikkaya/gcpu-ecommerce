# GCPU

Laravel API + Next.js mağaza ve admin panelinden oluşan e-ticaret monorepo'su.

```
.
├── app/              # Laravel backend (API, ödeme, kargo, auth)
├── frontend/         # Next.js mağaza + admin paneli
├── compose.yaml      # Docker: API + frontend + PostgreSQL + queue
└── tests/            # Pest feature/unit testleri
```

## Özellikler

### Mağaza (müşteri)

- **Katalog:** Arama, kategori/fiyat filtresi, sıralama, sayfalama
- **Ürün:** Varyant seçimi, renk swatch, ürün yorumları, ilgili ürünler
- **Sepet & checkout:** Stok rezervasyonu, taksit seçenekleri (Iyzico)
- **Auth:** E-posta/şifre, Google OAuth, şifre sıfırlama
- **Hesap:** Profil ve şifre güncelleme, adres defteri, sipariş geçmişi
- **Favoriler:** Ürün kaydetme
- **Stok bildirimi:** Stokta olmayan varyantlar için “Stoğa dönünce haber ver” (e-posta)
- **Sipariş:** Durum takibi, fatura PDF indirme, iptal talebi
- **Blog:** `/blog` vitrin ve yazı detayı

### Ödeme

- **Iyzico:** Sandbox / direct ödeme, `IYZICO_FAKE` ile local test
- **Stripe:** Checkout, `STRIPE_FAKE` ile local test

### Kargo (Geliver)

- Ödeme sonrası **otomatik kargo oluşturma** (Geliver API)
- Sipariş durumu **Geliver webhook** ile güncellenir (`shipped` → `delivered`)
- Müşteri sipariş detayında **kargo takip linki**
- Test modu: `GELIVER_FAKE=true` (API’ye gitmeden), `GELIVER_TEST=true` (Geliver test gönderisi)

### Admin (Next.js — `/admin/*`)

Sanctum API; vendor kendi ürün/siparişlerini görür, platform admin hepsini görür.

- Dashboard özeti, düşük stok uyarıları, grafikler
- Ürün CRUD, stok güncelleme, kapak görseli
- Sipariş listesi/detay, kargo bilgisi
- İptal talepleri onay/red
- Blog yazıları (yalnızca platform admin)

### Filament (Laravel — `/admin`)

Ayrı Laravel paneli; ürün/kategori CMS (session auth).

## Gereksinimler

- Docker Desktop
- Git

## Kurulum

```bash
cp .env.example .env
cp frontend/.env.local.example frontend/.env.local

docker compose up -d
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
```

Frontend bağımlılıkları container içinde kurulur:

```bash
docker compose exec frontend npm install
docker compose restart frontend
```

## Adresler

| Servis | URL | Not |
|--------|-----|-----|
| Mağaza + Next.js admin | http://localhost:3000 | Ana arayüz |
| API | http://localhost/api | Sanctum token auth |
| Filament admin | http://localhost/admin | Laravel session auth |

## Demo hesaplar

`migrate --seed` sonrası (varsayılan şifre: `password`):

| Rol | E-posta | Giriş yeri |
|-----|---------|------------|
| Admin | `admin@blog.test` | http://localhost:3000/login → Yönetim |
| Müşteri | `user@blog.test` | http://localhost:3000/login |

## Ortam değişkenleri

Şablonlar: `.env.example`, `frontend/.env.local.example`. Gerçek değerler commit edilmez.

### Zorunlu (local)

| Değişken | Açıklama |
|----------|----------|
| `APP_KEY` | `php artisan key:generate` ile üretilir |
| `NEXT_PUBLIC_API_URL` | Tarayıcı API adresi (`http://localhost/api`) |
| `FRONTEND_URL` | Frontend kök URL (`http://localhost:3000`) |

### Iyzico (ödeme)

| Değişken | Açıklama |
|----------|----------|
| `IYZICO_API_KEY` / `IYZICO_SECRET_KEY` | Sandbox anahtarları |
| `IYZICO_FAKE=true` | Gerçek API çağrısı yapmadan test |
| `IYZICO_DIRECT=true` | Doğrudan ödeme (kart formu checkout'ta) |

### Stripe (ödeme)

| Değişken | Açıklama |
|----------|----------|
| `STRIPE_FAKE=true` | Stripe hesabı olmadan local test |
| `STRIPE_SECRET_KEY` | Gerçek/test entegrasyon için |

Local geliştirmede `STRIPE_FAKE=true` yeterlidir; API anahtarları boş kalabilir.

### Geliver (kargo)

| Değişken | Açıklama |
|----------|----------|
| `GELIVER_API_TOKEN` | [Geliver panel](https://app.geliver.io/apitokens) API token |
| `GELIVER_SENDER_ADDRESS_ID` | Gönderici adres UUID |
| `GELIVER_FAKE=true` | Gerçek API’ye gitmeden sahte kargo (local) |
| `GELIVER_TEST=true` | Gerçek API ile test gönderisi (`test: true`) |
| `GELIVER_AUTO_CREATE_ON_PAYMENT=true` | Ödeme sonrası otomatik kargo |
| `GELIVER_SYNC_STATUS_FROM_WEBHOOK=true` | Webhook ile sipariş durumu senkronu |

Gerçek Geliver testi için `GELIVER_FAKE=false` yapın. Webhook URL:

```
https://<api-adresiniz>/api/webhooks/geliver
```

Yerelde Geliver’in erişebilmesi için ngrok gibi bir tünel gerekir.

### E-posta

Sipariş, kargo, stok bildirimi ve düşük stok mailleri kuyruğa alınır (`QUEUE_CONNECTION=database`). Docker ile `queue` servisi otomatik worker çalıştırır.

Gerçek e-posta kutusuna göndermek için `.env` içinde SMTP ayarlayın:

| Değişken | Örnek (Gmail) |
|----------|----------------|
| `MAIL_MAILER` | `smtp` |
| `MAIL_SCHEME` | `smtp` (port 587) veya `smtps` (port 465) |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | Gmail adresiniz |
| `MAIL_PASSWORD` | [Google uygulama şifresi](https://myaccount.google.com/apppasswords) |
| `MAIL_FROM_ADDRESS` | Gönderen adres |

Değişiklikten sonra:

```bash
docker compose up -d queue
docker compose restart laravel.test
```

Kayıt olurken **gerçek e-posta adresinizi** kullanın; demo `user@blog.test` gerçek bir posta kutusu değildir.

### Google OAuth (opsiyonel)

| Değişken | Açıklama |
|----------|----------|
| `GOOGLE_CLIENT_ID` | Backend JWT doğrulama |
| `GOOGLE_CLIENT_SECRET` | Backend |
| `NEXT_PUBLIC_GOOGLE_CLIENT_ID` | Frontend Google butonu |

Google Cloud Console'da **Authorized JavaScript origins** listesine `http://localhost:3000` ekleyin.

## Yararlı komutlar

Sail kurulduktan sonra `./vendor/bin/sail` kısayolunu kullanabilirsiniz:

```bash
./vendor/bin/sail artisan test              # Pest testleri
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=CatalogSeeder
./vendor/bin/sail exec frontend npm install # frontend paketi ekleme
./vendor/bin/sail restart frontend
```

Log izleme:

```bash
./vendor/bin/sail artisan pail
# veya
tail -f storage/logs/laravel.log
```

Docker olmadan yalnızca frontend:

```bash
cd frontend && npm install && npm run dev
```

## Mimari notlar

- **Next.js admin** (`/admin/*`): Sanctum API — vendor kendi ürün/siparişlerini görür, platform admin hepsini görür.
- **Filament** (`/admin`): Ayrı Laravel paneli; ürün/kategori yönetimi için.
- **Ödeme akışı:** Checkout → sipariş oluştur → Iyzico veya Stripe init → `/payment/result`.
- **Sipariş durumları:** `pending` → `processing` (ödeme) → `shipped` (Geliver kargo) → `delivered` (Geliver webhook).
- **Stok bildirimi:** `stock_alerts` tablosu; stok 0’dan pozitife çıkınca abonelere `BackInStockMail` gider.

## Güvenlik

Gizli bilgiler yalnızca `.env` ve `frontend/.env.local` içinde tutulur. Bu dosyalar `.gitignore`'dadır.

## Test

```bash
./vendor/bin/sail artisan test
# veya
docker compose exec laravel.test php artisan test
```

Test ortamı SQLite `:memory:` kullanır (`phpunit.xml`).
