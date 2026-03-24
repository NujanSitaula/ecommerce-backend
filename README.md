# Laravel Backend API

This is the Laravel backend API for the e-commerce application.

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js and npm (for frontend assets if needed)

## Installation

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment:**
   - Copy `.env.example` to `.env` (already done if using fresh install)
   - Update database credentials in `.env`:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=ecom_db
     DB_USERNAME=root
     DB_PASSWORD=your_password
     ```
   - Update `FRONTEND_URL` if your Next.js frontend runs on a different port:
     ```
     FRONTEND_URL=http://localhost:3000
     ```

3. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

4. **Create database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE ecom_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
   Or use PHP:
   ```bash
   php -r "try { \$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'your_password'); \$pdo->exec('CREATE DATABASE IF NOT EXISTS ecom_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); echo 'Database created\n'; } catch (PDOException \$e) { echo 'Error: ' . \$e->getMessage() . '\n'; }"
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

## Running the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## Queue Worker (Queued Emails)

Queued emails are delivered by Laravel's queue worker (database queue connection).

1. Ensure migrations are up to date:
   ```bash
   php artisan migrate
   ```

2. Start the queue worker in a separate terminal:
   ```bash
   php artisan queue:work --queue=default --tries=3
   ```

3. When debugging email delivery, you can also check failed jobs:
   ```bash
   php artisan queue:failed
   ```

## API Endpoints

### Test Endpoint
- **GET** `/api/test` - Returns API status and version

## CORS Configuration

CORS is configured to allow requests from:
- `http://localhost:3000` (Next.js default)
- `http://127.0.0.1:3000`
- Value from `FRONTEND_URL` environment variable

To modify CORS settings, edit `config/cors.php`.

## Database

The application uses MySQL. The database name is configured in `.env` as `DB_DATABASE`.

### Migrations

Run migrations:
```bash
php artisan migrate
```

Check migration status:
```bash
php artisan migrate:status
```

## Project Structure

- `app/` - Application code (Controllers, Models, etc.)
- `routes/api.php` - API route definitions
- `config/` - Configuration files
- `database/migrations/` - Database migrations
- `.env` - Environment configuration (not in version control)

## Next Steps

1. Create API controllers for e-commerce functionality
2. Set up authentication (Laravel Sanctum recommended)
3. Create models and migrations for products, orders, etc.
4. Implement API endpoints matching the frontend's expected structure
