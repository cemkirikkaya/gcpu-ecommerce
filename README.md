# GCPU

E-ticaret monorepo: Laravel API + Next.js mağaza ve admin paneli.

```
.
├── app/              # Laravel backend
├── frontend/         # Next.js frontend
└── compose.yaml      # Docker (API + frontend + PostgreSQL)
```

## Kurulum

```bash
cp .env.example .env
cp frontend/.env.local.example frontend/.env.local
docker compose up -d
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
```

## Adresler

| Servis | URL |
|--------|-----|
| Mağaza / Admin (Next.js) | http://localhost:3000 |
| API | http://localhost/api |
| Filament admin | http://localhost/admin |

## Yararlı komutlar

```bash
docker compose exec laravel.test php artisan test
docker compose exec laravel.test php artisan db:seed --class=CatalogSeeder
cd frontend && npm run dev   # Docker olmadan frontend
```

## GitHub

Tek repo olarak push edin. `.env` ve `frontend/.env.local` commit edilmez.

```bash
git add .
git commit -m "Initial monorepo"
git remote add origin git@github.com:KULLANICI/gcpu.git
git push -u origin main
```

## Güvenlik

Gizli bilgiler yalnızca `.env` ve `frontend/.env.local` içinde tutulur. Şablonlar: `.env.example`, `frontend/.env.local.example`.
