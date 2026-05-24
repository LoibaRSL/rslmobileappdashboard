# RSL Mobile App Dashboard

Laravel dashboard and API for RSL/LRA digital tax services. The application manages individual TIN registrations, business registrations, business amendments, Digital Services review queues, role-based administration, WSO2 login, SMS notifications, and external SOAP submission workflows.

## Stack

- PHP 8.2+
- Laravel 13
- MySQL/MariaDB
- Vite
- Bootstrap 5 / UBold admin theme
- Laravel Socialite for WSO2 authentication
- Laravel Sanctum personal access tokens

## Main Modules

- WSO2 authentication and logout
- Dashboard statistics and recent activity
- Individual TIN registration and amendment APIs
- Business registration submission, document uploads, review, approval, rejection, and CSV export
- Business amendment submission and review tracking
- Digital Services registration assignment and review queues
- User, role, and permission management
- SMS notifications
- SOAP integration services for individual, business, and amendment submissions

## Important Paths

- Web routes: `routes/web.php`
- API routes: `routes/api.php`
- Models: `app/Models`
- Controllers: `app/Http/Controllers`
- Services and integrations: `app/Services`
- Views: `resources/views`
- Frontend assets: `resources/js`, `resources/scss`
- Migrations: `database/migrations`
- Seeders: `database/seeders`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database and integration settings in `.env`, then run one of the database setup paths below.

### Fresh Migration Path

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Use this path only after confirming the migrations match the intended production schema.

### Existing Dump Path

The current database dump lives outside this project at:

```text
C:\xampp_lite_8_5\www\rslstart.sql
```

It is a dump for database `rslstart` and includes application data. Treat it as sensitive because it appears to contain taxpayer/user registration records.

Example import:

```bash
mysql -u root -p rslstart < C:\xampp_lite_8_5\www\rslstart.sql
```

After import, update `.env`:

```env
DB_DATABASE=rslstart
DB_USERNAME=root
DB_PASSWORD=
```

## Database Cleanup Status

The dump and migrations are not currently identical.

Tables present in the dump but not created by migrations:

- `banking_details`
- `mobile_money_details`
- `phone_details`
- `business_amendments`
- `business_amendment_files`
- `business_amendment_histories`
- `business_registration_histories`
- `t_i_n_applications`

Tables created by migrations but not present in the dump:

- `business_accountant_details`
- `business_antl_details`
- `business_bank_details`
- `business_contact_details`
- `business_declaration_details`
- `business_director_partners`
- `business_fbt_details`
- `business_mobile_money_details`
- `business_nominated_officer_details`
- `business_paye_details`
- `business_personal_identification`
- `business_plastic_levy_details`
- `business_sbt_details`
- `business_soap_integration`
- `business_sole_trader_details`
- `business_structured_phones`
- `business_vat_details`
- `business_wht_details`
- `tin_assignment_history`

The current model code is closer to the dump for business registrations because `BusinessRegistration` uses JSON-style columns such as `name_structure`, `structured_postal_address`, `trade_details`, and `file_attachments`. Before relying on fresh migrations, reconcile these differences.

## Known Cleanup Items

- Normalize `app/Services` casing and remove duplicated service files under `app/services`.
- Reconcile route references to missing or renamed controllers.
- Decide whether the business registration schema should remain JSON-column based or move fully to normalized detail tables.
- Add migrations for dump-only tables that are still used by models, especially individual TIN detail tables and business amendment tables.
- Remove legacy/demo UBold views and assets that are not used by the application.
- Replace example tests with feature tests for registration submission, assignment, approval/rejection, permissions, and uploads.

## Development Commands

```bash
npm run dev
npm run build
php artisan test
php artisan route:list
```

