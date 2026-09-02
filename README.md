# GCPU

Laravel API + Next.js mağaza ve admin panelinden oluşan e-ticaret monorepo'su.

```
.
├── app/              # Laravel backend (API, ödeme, kargo, auth)
├── frontend/         # Next.js mağaza + admin paneli
├── compose.yaml      # Docker: API + frontend + PostgreSQL + queue + scheduler
└── tests/            # Pest feature/unit testleri
```

## Özellikler

### Mağaza (müşteri)

- **Ana sayfa:** Hero banner, ürün carousel, promosyon alanları
- **Katalog:** Arama, **autocomplete önerileri** (`/api/products/search/suggest`), kategori sayfaları (`/categories/[slug]`), fiyat filtresi, sıralama, sayfalama
- **Ürün:** Varyant seçimi, renk swatch, **çoklu görsel galerisi** (kaydırılabilir), ürün yorumları (doğrulanmış alış), **ilgili ürünler** (cross-sell)
- **Sepet & checkout:** Stok rezervasyonu, **kupon kodu**, taksit seçenekleri (Iyzico)
- **Auth:** E-posta/şifre, Google OAuth, şifre sıfırlama
- **Hesap:** Profil ve şifre güncelleme, adres defteri, sipariş geçmişi
- **Favoriler:** Ürün kaydetme
- **Stok bildirimi:** Stokta olmayan varyantlar için “Stoğa dönünce haber ver” (e-posta)
- **Sipariş:** Durum takibi, **tahmini teslimat (ETA)**, kargo takip linki, fatura PDF indirme, iptal talebi, teslimat sonrası **iade/değişim talebi** (Geliver iade etiketi, stok geri yükleme, ödeme iadesi)
- **Blog:** `/blog` vitrin ve yazı detayı

### Ödeme

- **Iyzico:** Sandbox / direct ödeme, `IYZICO_FAKE` ile local test
- **Stripe:** Checkout, `STRIPE_FAKE` ile local test

### Kargo (Geliver)

- Ödeme sonrası **otomatik kargo oluşturma** (Geliver API)
- Sipariş durumu **Geliver webhook** ile güncellenir (`shipped` → `delivered`); webhook kaçırılırsa **API senkronu** (`geliver:sync-shipments`, scheduler ile dakikada bir)
- Müşteri sipariş listesi ve detayında **kargo takip linki** ve **tahmini teslimat tarihi (ETA)**
- Onaylanan iade taleplerinde **Geliver iade etiketi** ve takip bilgisi; değişimde yeni gönderi oluşturulur
- Test modunda placeholder takip URL’leri için `GELIVER_TRACKING_PAGE_BASE` fallback’i
- Test modu: `GELIVER_FAKE=true` (API’ye gitmeden), `GELIVER_TEST=true` (Geliver test gönderisi)

### Admin (Next.js — `/admin/*`)

Sanctum API; vendor kendi ürün/siparişlerini görür, platform admin hepsini görür.

- Dashboard özeti, düşük stok uyarıları, grafikler, **arama analitiği** (popüler arama terimleri)
- Ürün CRUD, stok güncelleme, **çoklu ürün görseli** (yükle, sil, kapak seç), **CSV toplu içe aktarma / fiyat-stok güncelleme**
- Sipariş listesi/detay, kargo bilgisi ve ETA, **manuel sipariş durumu**, manuel kargo senkronu
- Kupon yönetimi (yüzde / sabit tutar, min. sepet, kullanım limiti)
- İptal talepleri onay/red
- İade/değişim talepleri onay/red/teslim alma (stok geri yükleme, iade ödemesi)
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
| `NEXT_PUBLIC_MEDIA_URL` | Ürün görselleri kökü (`http://localhost`) — `/storage/...` URL’leri için |
| `FRONTEND_URL` | Frontend kök URL (`http://localhost:3000`) |

Frontend `.env.local` içinde ayrıca `API_INTERNAL_URL` (Docker/SSR içinden API) tanımlanır; ayrıntılar için `frontend/.env.local.example` dosyasına bakın.

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
| `GELIVER_AUTO_SYNC_FROM_API=true` | Scheduler ile Geliver API’den kargo durumu çekme |
| `GELIVER_TRACKING_PAGE_BASE` | Müşteri kargo takip sayfası tabanı (fallback: `https://app.geliver.io/tracking`) |
| `GELIVER_DEFAULT_WEIGHT` | Varsayılan paket ağırlığı (kg) |
| `GELIVER_DEFAULT_LENGTH` / `WIDTH` / `HEIGHT` | Varsayılan paket ölçüleri (cm) |

Gerçek Geliver testi için `GELIVER_FAKE=false` yapın. Webhook URL:

```
https://<api-adresiniz>/api/webhooks/geliver
```

Yerelde Geliver’in erişebilmesi için ngrok gibi bir tünel gerekir.

### Mağaza ayarları

| Değişken | Açıklama |
|----------|----------|
| `SHOP_NAME` | Mağaza adı (ana sayfa, e-postalar) |
| `SHOP_RESERVATION_MINUTES` | Sepette stok rezervasyon süresi (varsayılan: 15) |
| `SHOP_RETURN_WINDOW_DAYS` | Teslimat sonrası iade/değişim penceresi (varsayılan: 14) |
| `SHOP_LOW_STOCK_THRESHOLD` | Admin düşük stok uyarı eşiği (varsayılan: 5) |

### E-posta

Sipariş, kargo, stok bildirimi, iade/değişim ve düşük stok mailleri kuyruğa alınır (`QUEUE_CONNECTION=database`). Docker ile `queue` servisi otomatik worker çalıştırır; `scheduler` servisi rezervasyon temizliği ve Geliver senkronunu çalıştırır.

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
./vendor/bin/sail artisan db:seed --class=ProductImageSeeder  # demo görseller (mevcut kapakları değiştirir)
./vendor/bin/sail artisan reservations:clear                  # süresi dolmuş sepet rezervasyonlarını temizle
./vendor/bin/sail artisan geliver:sync-shipments              # Geliver kargo durumlarını API'den senkronize et
./vendor/bin/sail exec frontend npm install # frontend paketi ekleme
./vendor/bin/sail restart frontend
```

Manuel yüklediğin ürün görselleri `storage/app/public/catalog/products/` altında kalıcıdır; projeyi kapatıp açmak bunları silmez. `migrate:fresh --seed` veya `ProductImageSeeder` çalıştırmak kapak görsellerini değiştirebilir.

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
- **Sipariş durumları:** `pending` → `processing` (ödeme) → `shipped` (Geliver kargo) → `delivered` (Geliver webhook/API senkronu). Admin panelinden manuel durum güncellemesi de desteklenir.
- **Zamanlanmış görevler:** `reservations:clear` (süresi dolmuş sepet rezervasyonları), `geliver:sync-shipments` (yoldaki kargolar). Docker `scheduler` servisi `schedule:work` ile çalıştırır.
- **Ürün silme:** Soft delete — admin’den silinen ürünler `deleted_at` ile DB’de kalır; vitrin ve arama dışında tutulur (geçmiş sipariş bütünlüğü için).
- **Ürün görselleri:** `images` tablosu + `storage/app/public/catalog/products/`; API `image_url` (kapak) ve `images[]` (galeri) döner.
- **Arama:** `GET /api/products/search/suggest?q=...` autocomplete; popüler aramalar `GET /api/products/search/popular`.
- **Cross-sell:** `GET /api/products/{id}/cross-sell` — aynı kategoriden ilgili ürünler.
- **Stok bildirimi:** `stock_alerts` tablosu; stok 0’dan pozitife çıkınca abonelere `BackInStockMail` gider.
- **Kuponlar:** Sepette kod uygulama; admin panelinden yüzde veya sabit tutar kupon tanımı.
- **İade/değişim:** Teslimat sonrası `SHOP_RETURN_WINDOW_DAYS` içinde talep; admin onayı → Geliver iade etiketi → teslim alınca stok geri yükleme ve Iyzico/Stripe iadesi veya değişim gönderisi.
- **Toplu ürün:** Admin CSV ile ürün içe aktarma veya fiyat/stok toplu güncelleme (`/admin/products/bulk`).

## Güvenlik

Gizli bilgiler yalnızca `.env` ve `frontend/.env.local` içinde tutulur. Bu dosyalar `.gitignore`'dadır.

## Test

```bash
./vendor/bin/sail artisan test
# veya
docker compose exec laravel.test php artisan test
```

Test ortamı SQLite `:memory:` kullanır (`phpunit.xml`).
