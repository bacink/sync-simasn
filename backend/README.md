# KGB & PMK ASN Management System

Backend Laravel 13 untuk sistem pencatatan, perhitungan, dan pengesahan KGB & PMK ASN.

## Tech Stack

- **Laravel 13** (PHP 8.2+)
- **Laravel Sanctum** — API Authentication
- **Spatie Permission** — Role-based Access Control
- **MySQL/PostgreSQL** — Database
- **MinIO/S3** — Document Storage (SK KGB)

## Arsitektur

```
Controller (tipis) → Service Layer → Model/Eloquent
                          ↓
              SimAsnService (external API)
              KgbCalculationService (pure logic)
              AuditService (audit trail)
              KgbSnapshotService (data versioning)
```

## Installation

```bash
# Clone repository
cd backend

# Install dependencies
composer install

# Copy environment
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (roles, users, ref gaji)
php artisan db:seed

# Start server
php artisan serve
```

## API Documentation

Import Postman collection dari `postman/KGB_System_API_v1.postman_collection.json`.

## Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@bpsdm.id | password |
| Operator | operator@bpsdm.id | password |
| Verifikator | verifikator@bpsdm.id | password |

## API Endpoints

Base URL: `/api/v1`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | /auth/login | Login |
| GET | /kgb | List KGB |
| POST | /kgb/generate | Generate draft |
| POST | /kgb/{id}/submit | Ajukan KGB |
| POST | /kgb/{id}/verify | Verifikasi KGB |
| POST | /kgb/{id}/approve | Approve KGB |
| GET | /pmk | List PMK |
| POST | /pmk | Create PMK |
| GET | /dashboard/stats | Statistik |

## Environment Variables

| Variable | Description |
|----------|-------------|
| `SIMASN_BASE_URL` | URL SIM-ASN API |
| `SIMASN_API_KEY` | API Key SIM-ASN |
| `AWS_*` | S3/MinIO credentials |
| `KGB_STORAGE_DISK` | Storage disk (s3/local) |

## Testing

```bash
# Run tests
php artisan test

# With coverage
php artisan test --coverage
```

## License

MIT