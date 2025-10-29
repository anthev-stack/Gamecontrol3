# Admin Panel for Plan Management (Future Enhancement)

## Current Plan Management

Currently, hosting plans can be managed through:

### 1. Database Direct
```sql
INSERT INTO hosting_plans (...) VALUES (...);
UPDATE hosting_plans SET price = 14.99 WHERE id = 1;
DELETE FROM hosting_plans WHERE id = 1;
```

### 2. Laravel Tinker
```bash
php artisan tinker
```

```php
// Create plan
\Pterodactyl\Models\HostingPlan::create([...]);

// Update plan
$plan = \Pterodactyl\Models\HostingPlan::find(1);
$plan->price = 14.99;
$plan->save();

// Delete plan
$plan->delete();

// List all plans
\Pterodactyl\Models\HostingPlan::all();
```

### 3. Database Seeder (Recommended)
Create `database/seeders/HostingPlanSeeder.php` and run:
```bash
php artisan db:seed --class=HostingPlanSeeder
```

## Future Admin Panel Features

A complete admin panel would include:

### Plan Management
- List all plans (active/inactive)
- Create new plans
- Edit existing plans
- Delete plans
- Toggle active status
- Set featured plans
- Manage stock limits

### Order Management
- View all orders
- Filter by status/date
- Process refunds
- Link to customer accounts
- Export order data

### Customer Management
- View all customers
- Customer details
- Server usage
- Payment history
- Suspend/unsuspend accounts

### Financial Reports
- Revenue over time
- Popular plans
- Conversion rates
- Outstanding invoices
- Payment methods breakdown

### Settings
- Tax rates by location
- Currency settings
- Email templates
- Payment gateway config
- Node selection rules

## Quick Implementation Guide

If you want to add a basic admin panel, here's the structure:

### 1. Create Admin Routes
```php
// routes/admin.php
Route::prefix('/admin/marketplace')->group(function () {
    Route::get('/plans', [Admin\MarketplaceController::class, 'plans']);
    Route::post('/plans', [Admin\MarketplaceController::class, 'createPlan']);
    Route::patch('/plans/{plan}', [Admin\MarketplaceController::class, 'updatePlan']);
    Route::delete('/plans/{plan}', [Admin\MarketplaceController::class, 'deletePlan']);
});
```

### 2. Create Admin Controller
```php
// app/Http/Controllers/Admin/MarketplaceController.php
class MarketplaceController extends Controller
{
    public function plans()
    {
        $plans = HostingPlan::with(['nest', 'egg'])->get();
        return view('admin.marketplace.plans', compact('plans'));
    }
    
    // ... other methods
}
```

### 3. Create Admin Views
```php
// resources/views/admin/marketplace/plans.blade.php
// Use Pterodactyl's existing admin layout
```

## For Now

Use one of the management methods above. The admin panel can be added later as the business grows and you need more frequent plan management.

Most hosting companies:
- Create plans once during setup
- Rarely change them
- When they do, database/tinker is sufficient

## Priority

**Low** - The core marketplace functionality is complete. Focus on:
1. Payment gateway integration
2. Email notifications
3. Testing and debugging
4. Marketing and customer acquisition

Admin panel for plans is a "nice to have" that can be added later based on actual need.

