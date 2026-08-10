# GCPU

Laravel API + Next.js mağaza ve admin panelinden oluşan e-ticaret monorepo'su.

```
.
├── app/              # Laravel backend (API, ödeme, auth)
├── frontend/         # Next.js mağaza + admin paneli
├── compose.yaml      # Docker: API + frontend + PostgreSQL
└── tests/            # Pest feature/unit testleri
```

## Özellikler

- **Mağaza:** Katalog, ürün detayı, sepet, checkout, sipariş takibi
- **Katalog:** Arama, kategori/fiyat filtresi, sıralama, sayfalama
- **Auth:** E-posta/şifre kayıt ve giriş, Google OAuth (ID token)
- **Hesap:** Adres defteri (CRUD, varsayılan adres)
- **Ödeme:** Iyzico (sandbox/direct) ve Stripe (fake mod ile local test)
- **Admin (Next.js):** Ürün/stok yönetimi, sipariş listesi, sipariş durumu güncelleme
- **Filament:** Laravel tarafında ürün/kategori/stok CMS (`/admin`)

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

### Google OAuth (opsiyonel)

| Değişken | Açıklama |
|----------|----------|
| `GOOGLE_CLIENT_ID` | Backend JWT doğrulama |
| `GOOGLE_CLIENT_SECRET` | Backend |
| `NEXT_PUBLIC_GOOGLE_CLIENT_ID` | Frontend Google butonu |

Google Cloud Console'da **Authorized JavaScript origins** listesine `http://localhost:3000` ekleyin. Testing modundaysanız giriş yapacağınız Gmail'i test kullanıcısı olarak ekleyin.

Frontend paketleri container'a kurulurken:

```bash
docker compose exec frontend npm install @react-oauth/google   # gerekirse
```

## Yararlı komutlar

Sail kurulduktan sonra `./vendor/bin/sail` kısayolunu kullanabilirsiniz:

```bash
./vendor/bin/sail artisan test              # 75+ Pest testi
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
- **Sipariş durumları:** `pending` → `processing` (ödeme) → `shipped` → `delivered` (admin panelden güncellenir).

## Güvenlik

Gizli bilgiler yalnızca `.env` ve `frontend/.env.local` içinde tutulur. Bu dosyalar `.gitignore`'dadır.

## Test

```bash
./vendor/bin/sail artisan test
# veya
docker compose exec laravel.test php artisan test
```

Test ortamı SQLite `:memory:` kullanır (`phpunit.xml`).
