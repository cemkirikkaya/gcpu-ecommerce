# GCPU — Frontend

Next.js mağaza arayüzü ve admin paneli. Laravel API ile konuşur.

## Gereksinimler

- Node.js 20+
- Çalışan backend API

## Kurulum

```bash
cp .env.local.example .env.local
npm install
npm run dev
```

Uygulama: http://localhost:3000

## Docker ile (monorepo kökünden)

Proje kökündeki `compose.yaml` frontend servisini otomatik başlatır:

```bash
docker compose up -d
```

## Ortam değişkenleri

| Değişken | Açıklama |
|----------|----------|
| `NEXT_PUBLIC_API_URL` | Tarayıcının eriştiği API adresi |
| `NEXT_PUBLIC_APP_URL` | Frontend URL |
| `API_INTERNAL_URL` | Docker içinden API (SSR) |

## Güvenlik

`.env.local` dosyasını commit etmeyin. Gerçek API adresleri ve gizli anahtarlar yalnızca yerel ortam dosyasında kalmalıdır.
