# KGB & PMK System - Project Context

This project is a specialized system for managing **Kenaikan Gaji Berkala (KGB)** (Periodic Salary Increases) and **Peninjauan Masa Kerja (PMK)** (Work Period Review) for civil servants (ASN) in Karawang Regency. It integrates with the external **SIM-ASN** system to provide a streamlined, automated, and auditable workflow.

## 🏗️ Architecture & Technology Stack

### Backend (API)
- **Framework:** Laravel 13 (PHP 8.3+)
- **Authentication:** Laravel Sanctum (Token-based)
- **Architecture:** Service Layer Pattern
  - Business logic is strictly contained within `app/Services/`
  - Controllers are kept thin, delegating all operations to services.
  - **DTOs:** Used for data consistency and validation (`app/DTOs/`).
- **Integration:** External SIM-ASN API for employee data synchronization.
- **Testing:** Pest PHP (`tests/Feature/` and `tests/Unit/`).

### Frontend (Client)
- **Framework:** Nuxt 4 (Vue 3)
- **Styling:** TailwindCSS
- **State Management:** Pinia
- **Components:** Headless UI
- **Architecture:** Store-Service-API pattern.

## 🚀 Key Workflows

### 1. KGB Generation
- Automatically identifies eligible employees.
- Calculates new salary based on the latest regulations (e.g., PP No. 5 Tahun 2024).
- Creates an immutable **Snapshot** of employee data at the time of generation to ensure auditability.

### 2. PMK (Peninjauan Masa Kerja)
- Allows manual adjustment of work periods which directly affects future KGB calculations.
- Supports document uploads for proof of service.

### 3. Approval Workflow
- **Draft:** Initial calculation state.
- **Submitted:** Sent by Operator OPD for review.
- **Verified:** Checked by Verifikator.
- **Approved:** Finalized by Admin BKPSDM. Once approved, the record is immutable.

## 🛠️ Development Guide

### Prerequisites
- PHP 8.3+
- Node.js & NPM
- Composer

### Getting Started

#### API (Backend)
```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev # Runs server, queue, and logs concurrently
```

#### Client (Frontend)
```bash
cd client
npm install
npm run dev
```

### Testing
- **Backend:** `php artisan test` or `npm test` (inside `api` folder).
- **Frontend:** `npm run test` (inside `client` folder).

### Standards & Conventions
- **Naming:** Follow PSR-12 for PHP and standard Vue/Nuxt conventions for the frontend.
- **Logic Placement:** Never put business logic in Controllers or Models; always use **Services**.
- **Security:** Ensure all sensitive operations are covered by middleware and proper authorization policies.
- **Audit:** All state changes must be logged in `audit_logs` table.

## 📁 Key Directories

- `api/app/Services/Kgb/`: Core KGB calculation and workflow logic.
- `api/app/Services/SimAsn/`: Integration layer with the external SIM-ASN API.
- `api/database/migrations/`: Database schema for KGB, PMK, and related tables.
- `client/pages/kgb/`: Nuxt pages for KGB management.
- `client/stores/`: Pinia stores for application state.

## 🔗 Integration with SIM-ASN
The system acts as a "Processor + Recorder" for SIM-ASN data. It does not replace SIM-ASN but complements it by providing a specialized workflow for salary management. Configuration for the integration can be found in `api/config/sim-asn.php`.
