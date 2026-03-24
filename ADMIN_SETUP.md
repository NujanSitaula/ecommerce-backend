# Admin Panel Backend Setup

This document describes the backend changes made to support the admin panel.

## Changes Made

### 1. Database Migration
- Added `role` column to `users` table (default: 'customer')
- Migration: `2026_01_13_073656_add_role_to_users_table.php`

### 2. User Model Updates
- Added `role` to `$fillable` array
- Added `isAdmin()` helper method

### 3. Authentication Updates
- **AuthController::login()** - Now returns user object with role:
  ```json
  {
    "token": "...",
    "user": {
      "id": "1",
      "email": "admin@example.com",
      "name": "Admin",
      "role": "admin"
    }
  }
  ```
- **AuthController::profile()** - Returns user with role (used by `/api/auth/me`)
- **AuthController::verifyOtp()** - Also returns user with role

### 4. Admin Middleware
- Created `EnsureUserIsAdmin` middleware
- Registered as `admin` alias in `bootstrap/app.php`
- Checks if authenticated user has `role === 'admin'`

### 5. Admin Controllers

#### AdminProductController
- `GET /api/admin/products` - List all products (with pagination, search, status filter)
- `GET /api/admin/products/{id}` - Get single product
- `POST /api/admin/products` - Create product
- `PUT /api/admin/products/{id}` - Update product
- `DELETE /api/admin/products/{id}` - Delete product

#### AdminSettingsController
- `GET /api/admin/settings` - Get store settings
- `PUT /api/admin/settings` - Update store settings

### 6. Routes
All admin routes are protected with `auth:api` and `admin` middleware:
```php
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    // Products
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::get('/products/{id}', [AdminProductController::class, 'show']);
    Route::put('/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index']);
    Route::put('/settings', [AdminSettingsController::class, 'update']);
});
```

### 7. Artisan Command
- `php artisan admin:create-user` - Create an admin user interactively

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Create Admin User
```bash
php artisan admin:create-user
```

Or manually:
```bash
php artisan tinker
>>> $user = App\Models\User::create([
    'email' => 'admin@example.com',
    'name' => 'Admin User',
    'password' => Hash::make('your-secure-password'),
    'role' => 'admin',
    'email_verified_at' => now(),
]);
```

### 3. Update Existing User to Admin
```bash
php artisan tinker
>>> $user = App\Models\User::where('email', 'existing@example.com')->first();
>>> $user->role = 'admin';
>>> $user->save();
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - Login (returns user with role)
- `GET /api/auth/me` - Get current user (returns user with role)
- `GET /api/auth/profile` - Alias for `/api/auth/me`
- `POST /api/auth/logout` - Logout

### Admin Products
- `GET /api/admin/products?page=1&perPage=20&search=term&status=active`
- `GET /api/admin/products/{id}`
- `POST /api/admin/products` (requires admin)
- `PUT /api/admin/products/{id}` (requires admin)
- `DELETE /api/admin/products/{id}` (requires admin)

### Admin Settings
- `GET /api/admin/settings` (requires admin)
- `PUT /api/admin/settings` (requires admin)

## Testing

1. Start Laravel backend:
   ```bash
   php artisan serve
   ```

2. Create admin user:
   ```bash
   php artisan admin:create-user
   ```

3. Test login:
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@example.com","password":"your-password"}'
   ```

4. Test admin products (replace TOKEN):
   ```bash
   curl -X GET http://localhost:8000/api/admin/products \
     -H "Authorization: Bearer TOKEN"
   ```

## Notes

- All admin routes require authentication (`auth:api`) and admin role (`admin` middleware)
- Product status can be: `active` or `draft`
- Settings are stored in cache (consider migrating to database table for production)
- Admin panel expects backend at `http://localhost:8000` by default (configurable via env)

