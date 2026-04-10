# Role-Based Authorization Guide

## Overview
The Sansworks API now implements comprehensive role-based access control (RBAC) to protect endpoints based on user roles (admin, manager, staff).

## User Roles

### 1. Admin
- **Full system access**
- Can manage all data
- Can manage users
- Can modify system settings
- Can recalculate statistics

### 2. Manager
- **Production management access**
- Can create/update/delete master data
- Can manage production workflows
- Can view statistics (read-only)
- Cannot manage users or system settings

### 3. Staff
- **Basic operational access**
- Can view all data (read-only)
- Cannot create, update, or delete data
- Cannot access management features

## Middleware Implementation

### Created Middleware Classes

#### 1. AdminMiddleware
```php
// Only allows admin users
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin only routes
});
```

#### 2. ManagerMiddleware
```php
// Allows manager and admin users
Route::middleware(['auth:sanctum', 'manager'])->group(function () {
    // Manager+ routes
});
```

#### 3. RoleMiddleware (Flexible)
```php
// Allows specific roles
Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function () {
    // Multiple roles
});
```

## Route Protection

### Public Routes (No Authentication)
```php
POST /api/auth/register
POST /api/auth/login
```

### Authenticated Routes (All Roles)
```php
// All authenticated users can access these

GET /api/dashboard/*
GET /api/sizes (read only)
GET /api/tailors (read only)
GET /api/brands (read only)
GET /api/articles (read only)
GET /api/fabrics (read only)
GET /api/cutting-results (read only)
GET /api/cutting-distributions (read only)
GET /api/deposit-cutting-results (read only)
GET /api/qc-results (read only)
GET /api/repair-distributions (read only)
GET /api/deposit-repair-results (read only)
GET /api/daily-statistics (read only)
GET /api/activity-logs (read only)
```

### Manager+ Routes (Admin & Manager)
```php
// Managers and admins can create/update/delete

POST /api/sizes
PUT /api/sizes/{id}
DELETE /api/sizes/{id}

POST /api/tailors
PUT /api/tailors/{id}
DELETE /api/tailors/{id}

POST /api/brands
PUT /api/brands/{id}
DELETE /api/brands/{id}

POST /api/articles
PUT /api/articles/{id}
DELETE /api/articles/{id}

POST /api/fabrics
PUT /api/fabrics/{id}
DELETE /api/fabrics/{id}
POST /api/fabrics/{id}/adjust-quantity

POST /api/cutting-results
PUT /api/cutting-results/{id}
DELETE /api/cutting-results/{id}

// ... and all production/quality control operations
```

### Admin Only Routes
```php
// Only admins can access these

POST /api/daily-statistics
PUT /api/daily-statistics/{id}
DELETE /api/daily-statistics/{id}
POST /api/daily-statistics/{id}/recalculate
```

## Authentication & Authorization Flow

### 1. User Authentication
```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password123"}'

# Response includes token
{
    "success": true,
    "data": {
        "user": {...},
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### 2. Accessing Protected Routes
```bash
# Include token in Authorization header
curl -X GET http://localhost:8000/api/sizes \
  -H "Authorization: Bearer 1|abc123..."
```

### 3. Authorization Errors

#### Not Authenticated (401)
```json
{
    "success": false,
    "message": "Authentication required. Please login to continue."
}
```

#### Access Denied (403)
```json
{
    "success": false,
    "message": "Access denied. Manager or Administrator privileges required."
}
```

## User Model Helper Methods

```php
// Check if user is admin
$user->isAdmin(); // Returns true/false

// Check if user is manager or admin
$user->isManager(); // Returns true/false

// Get user role
$user->role; // Returns 'admin', 'manager', or 'staff'
```

## Security Features

### 1. Token-Based Authentication
- Uses Laravel Sanctum for API tokens
- Tokens stored securely in database
- Each user can have multiple active tokens
- Tokens can be revoked individually or all at once

### 2. Role Hierarchy
```
Admin → Full Access
  ↓
Manager → Production Management
  ↓
Staff → Read-Only Access
```

### 3. Automatic Authorization Checks
- Middleware runs before controller methods
- Unauthorized access blocked before business logic
- Consistent error responses across all endpoints

### 4. Activity Logging
All actions are logged with:
- User who performed the action
- Action performed (create/update/delete)
- Old and new values
- Timestamp and IP address

## Testing Authorization

### Using Test Users

```bash
# Login as admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}'

# Login as manager
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"manager","password":"manager123"}'

# Login as staff
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"staff","password":"staff123"}'
```

### Testing Role Access

```bash
# Staff trying to create (should fail - 403)
curl -X POST http://localhost:8000/api/sizes \
  -H "Authorization: Bearer STAFF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","abbreviation":"TS"}'

# Manager trying to create (should work)
curl -X POST http://localhost:8000/api/sizes \
  -H "Authorization: Bearer MANAGER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","abbreviation":"TS"}'

# Admin accessing admin-only route (should work)
curl -X POST http://localhost:8000/api/daily-statistics \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"statistic_date":"2024-01-01"}'
```

## Best Practices

### 1. Always Check Authentication
```php
// In controllers
public function store(Request $request)
{
    // Middleware handles authentication
    // Just focus on business logic
    $size = Size::create($request->validated());
    return new SizeResource($size);
}
```

### 2. Log Important Actions
```php
// Activity logging is automatic via LogsActivity trait
$size->update($data); // Automatically logged
```

### 3. Use Role Checks in Controllers
```php
// For additional business logic checks
if (!auth()->user()->isAdmin()) {
    abort(403, 'Only admins can perform this action');
}
```

### 4. Frontend Integration
```javascript
// Vue.js example
const headers = {
    'Authorization': `Bearer ${token}`
};

// Staff users should see read-only UI
if (user.role === 'staff') {
    // Hide create/edit/delete buttons
}

// Manager+ users see full UI
if (['admin', 'manager'].includes(user.role)) {
    // Show all buttons
}
```

## Migration from Manual Authorization

### Before (Manual Checks)
```php
public function store(Request $request)
{
    if (!auth()->user()->isAdmin()) {
        return response()->json(['error' => 'Access denied'], 403);
    }
    // Business logic...
}
```

### After (Middleware Protection)
```php
// Routes are protected by middleware
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/sizes', [SizeController::class, 'store']);
});

// Controller focuses on business logic
public function store(Request $request)
{
    // No manual checks needed
    $size = Size::create($request->validated());
    return new SizeResource($size);
}
```

## Security Considerations

### 1. Token Security
- Never expose tokens in client-side JavaScript
- Always use HTTPS in production
- Implement token expiration policies
- Allow users to revoke tokens

### 2. Role Management
- Never elevate privileges programmatically
- Always validate role changes
- Log all role modifications
- Implement approval workflows for role changes

### 3. API Protection
- Rate limiting (recommended next step)
- Input validation (already implemented)
- SQL injection prevention (Eloquent ORM)
- XSS prevention (Laravel escaping)

## Troubleshooting

### Issue: 401 Unauthorized
**Cause**: Missing or invalid token
**Solution**: Ensure token is included in Authorization header

### Issue: 403 Forbidden
**Cause**: Insufficient role permissions
**Solution**: Check user role and required permissions

### Issue: 500 Server Error
**Cause**: Middleware or server configuration issue
**Solution**: Check Laravel logs in storage/logs

### Issue: Token Not Working
**Cause**: Token expired or revoked
**Solution**: Login again to get new token

The role-based authorization system is now fully implemented and protecting your API endpoints!
