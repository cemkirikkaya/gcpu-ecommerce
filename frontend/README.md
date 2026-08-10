# GCPU — Frontend

Next.js mağaza arayüzü ve admin paneli. Laravel API ile konuşur.

Monorepo kurulumu ve demo hesaplar için kök dizindeki [README](../README.md) dosyasına bakın.

## Gereksinimler

- Node.js 20+ (Docker dışında çalıştırırken)
- Çalışan backend API

## Docker ile (önerilen)

Proje kökünden:

```bash
docker compose up -d
docker compose exec frontend npm install
```

Uygulama: http://localhost:3000

Yeni npm paketi eklerken host'ta değil, **frontend container içinde** kurun:

```bash
docker compose exec frontend npm install <paket-adı>
docker compose restart frontend
```

## Docker olmadan

```bash
cp .env.local.example .env.local
npm install
npm run dev
```

## Ortam değişkenleri

| Değişken | Açıklama |
|----------|----------|
| `NEXT_PUBLIC_API_URL` | Tarayıcının eriştiği API adresi |
| `NEXT_PUBLIC_APP_URL` | Frontend URL |
| `NEXT_PUBLIC_MEDIA_URL` | Ürün görselleri için medya kökü |
| `API_INTERNAL_URL` | Docker/SSR içinden API (`http://laravel.test/api`) |
| `NEXT_PUBLIC_GOOGLE_CLIENT_ID` | Google ile giriş butonu (boşsa gizlenir) |

`.env.local` dosyasını commit etmeyin.
