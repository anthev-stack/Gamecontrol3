# Hosting Marketplace for Pterodactyl Panel

This is a modified version of Pterodactyl Panel that includes a full hosting marketplace with cart, checkout, and billing features built directly into the panel.

## 🚀 Features Added

### 1. **Public Homepage**
- Browse available hosting plans without logging in
- View plan specifications and pricing
- Featured plans highlighted

### 2. **Shopping Cart**
- Guest cart support (session-based)
- Authenticated user carts
- Add/remove items
- Update quantities
- Real-time cart total calculations

### 3. **Checkout Process**
- **Guest Checkout**: Create account during checkout
- Integrated registration and billing information
- Automatic server provisioning after order completion
- Order confirmation with server details

### 4. **Billing Dashboard**
- View order history
- Download invoices
- Track payment status
- Monitor overdue payments

### 5. **Database Structure**
New tables created:
- `hosting_plans` - Server plans with specifications
- `carts` - Shopping carts (user/guest)
- `cart_items` - Items in cart
- `orders` - Completed orders
- `order_items` - Order line items
- `invoices` - Billing invoices
- `invoice_items` - Invoice line items
- `payments` - Payment records

## 📋 Installation

### Prerequisites
- PHP 8.2 or 8.3
- MySQL/MariaDB
- Node.js 18+
- Composer
- Pterodactyl Panel (base installation)

### Step 1: Clone and Install

```bash
# Clone the repository
git clone https://github.com/anthev-stack/Gamecontrol3.git
cd Gamecontrol3

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install JavaScript dependencies
yarn install
```

### Step 2: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Update `.env` with your database credentials and app settings.

### Step 3: Run Migrations

```bash
# Run all migrations including the new marketplace tables
php artisan migrate
```

### Step 4: Build Assets

```bash
# Build production assets
yarn run build

# Or for development with hot reload
yarn run dev
```

### Step 5: Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 6: Create an Admin User

```bash
php artisan p:user:make
```

## 📝 Creating Hosting Plans

You'll need to create hosting plans in the database. Here's an example:

### Via Database Seeder (Recommended)

Create `database/seeders/HostingPlanSeeder.php`:

```php
<?php

use Illuminate\Database\Seeder;
use Pterodactyl\Models\HostingPlan;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Egg;

class HostingPlanSeeder extends Seeder
{
    public function run()
    {
        $nest = Nest::first();
        $egg = Egg::first();

        HostingPlan::create([
            'name' => 'Budget Minecraft Plan',
            'description' => 'Perfect for small Minecraft servers',
            'slug' => 'budget-minecraft',
            'memory' => 2048,
            'swap' => 512,
            'disk' => 5120,
            'io' => 500,
            'cpu' => 100,
            'nest_id' => $nest->id,
            'egg_id' => $egg->id,
            'database_limit' => 2,
            'allocation_limit' => 5,
            'backup_limit' => 2,
            'price' => 9.99,
            'billing_period' => 'monthly',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=HostingPlanSeeder
```

### Via Database Query

```sql
INSERT INTO hosting_plans (name, description, slug, memory, swap, disk, io, cpu, nest_id, egg_id, database_limit, allocation_limit, backup_limit, price, billing_period, is_active, is_featured, created_at, updated_at)
VALUES ('Budget Plan', 'Affordable hosting', 'budget-plan', 2048, 512, 5120, 500, 100, 1, 1, 2, 5, 2, 9.99, 'monthly', 1, 1, NOW(), NOW());
```

## 🎨 Frontend Structure

The marketplace features use the same React frontend as Pterodactyl. The routes are:

- `/` - Homepage (public)
- `/cart` - Shopping cart
- `/checkout` - Checkout page
- `/billing` - Billing dashboard (requires auth)
- `/billing/orders` - Order history
- `/billing/invoices` - Invoice list

## 🔌 API Endpoints

### Public Endpoints
```
GET  /plans - List all active plans
GET  /cart/show - Get current cart
POST /cart/add - Add item to cart
DELETE /cart/remove/{id} - Remove item
PATCH /cart/update/{id} - Update quantity
POST /cart/clear - Clear cart
POST /checkout/complete - Complete checkout
```

### Authenticated Endpoints
```
GET /billing/orders - List user orders
GET /billing/invoices - List user invoices
GET /billing/invoices/{id} - Get invoice details
```

## 💳 Payment Integration

Currently, the system creates servers immediately after checkout (for development). To integrate payment gateways:

1. Update `app/Http/Controllers/Marketplace/CheckoutController.php`
2. Add payment processing before server creation
3. Implement webhook handlers
4. Update order status based on payment

Recommended gateways:
- **Stripe** - Credit cards, most popular
- **PayPal** - Global acceptance
- **Paddle** - Merchant of record
- **Mollie** - European-focused

## 🛠️ Admin Features (Coming Soon)

- Create/edit/delete hosting plans
- Manage orders
- View sales reports
- Configure pricing
- Manage promotions

## 📊 Workflow

1. **User browses plans** at `/`
2. **Adds to cart** → creates/updates cart
3. **Proceeds to checkout** → `/checkout`
4. **Creates account** (if guest) or logs in
5. **Completes order** → server provisioned immediately
6. **Receives confirmation** → order and server details
7. **Manages billing** at `/billing`

## 🔒 Security Considerations

- Guest carts are session-based (not stored permanently)
- All payments require authentication
- Server creation uses existing Pterodactyl security
- Rate limiting on API endpoints
- CSRF protection on forms

## 🚨 Important Notes

### Server Provisioning

The current checkout creates servers immediately for all plans in cart. You should:

1. Implement payment verification first
2. Queue server creation (Laravel queues)
3. Send email notifications
4. Handle failures gracefully

### Node Selection

Currently hardcoded to use first node. Update:
- `app/Http/Controllers/Marketplace/CheckoutController.php`
- Line ~162: `'node_id' => 1,`
- Line ~163: `'allocation_id' => 1,`

Implement a node selection algorithm or let users choose.

### Stock Management

Plans support stock limits but checking is manual. Implement:
- Database triggers
- Cron jobs to check availability
- Lock items during checkout

## 📞 Support

For issues related to:
- **Pterodactyl Panel**: https://pterodactyl.io
- **This Marketplace**: Open an issue on GitHub

## 📄 License

This code is built on Pterodactyl Panel which is licensed under MIT.

## 🤝 Contributing

Contributions welcome! Areas needing work:
- Payment gateway integration
- Admin panel for plans
- Email notifications
- Frontend UI/UX improvements
- Recurring billing
- Promo codes

## ⚡ Next Steps

1. **Create hosting plans** in database
2. **Configure nodes and eggs**
3. **Build frontend assets**
4. **Test checkout flow**
5. **Integrate payment gateway**
6. **Deploy to production**

---

**Note**: This is a modification of Pterodactyl Panel. For production use, thoroughly test all features including:
- Server creation
- Payment processing
- User registration flow
- Email delivery
- Database transactions

Make sure to backup your database before running migrations!


