# Hosting Marketplace Integration Setup Guide

This document explains how to set up and use the hosting marketplace features integrated into Pterodactyl Panel.

## Features

- **Public Homepage** - Browse available hosting plans
- **Shopping Cart** - Add plans to cart
- **Checkout Process** - Complete purchases with user registration
- **Billing Dashboard** - Manage invoices and payments
- **Automatic Server Provisioning** - Servers created automatically after payment

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `hosting_plans` - Available server plans
- `carts` - Shopping carts
- `cart_items` - Items in cart
- `orders` - Completed orders
- `order_items` - Items in each order
- `invoices` - Billing invoices
- `invoice_items` - Invoice line items
- `payments` - Payment records

### 2. Build Frontend Assets

```bash
yarn install
yarn run build
```

### 3. Create Hosting Plans

You need to create hosting plans in the database. You can do this via:
- Admin panel (coming soon)
- Database seeder
- Direct database queries

Example plan creation:
```php
HostingPlan::create([
    'name' => 'Budget Minecraft Plan',
    'description' => 'Perfect for small servers',
    'slug' => 'budget-minecraft',
    'memory' => 2048,
    'swap' => 512,
    'disk' => 5120,
    'io' => 500,
    'cpu' => 100,
    'nest_id' => 1, // Your Minecraft nest
    'egg_id' => 1,  // Your Minecraft egg
    'database_limit' => 2,
    'allocation_limit' => 5,
    'backup_limit' => 2,
    'price' => 9.99,
    'billing_period' => 'monthly',
    'is_active' => true,
    'is_featured' => true,
]);
```

## Usage

### For End Users

1. **Browse Plans**: Visit `/` to see available hosting plans
2. **Add to Cart**: Click "Add to Cart" on any plan
3. **Review Cart**: Go to `/cart` to review items
4. **Checkout**: Proceed to checkout
   - If not logged in, create an account
   - Enter billing information
   - Complete payment
5. **Access Server**: After payment, server is automatically created
6. **Manage Billing**: View invoices and payment history at `/billing`

### For Administrators

Create hosting plans and manage orders through the admin panel.

## Integration with Payment Gateways

Currently, the system supports manual payments. To integrate payment gateways:

1. Update `app/Services/Payments/PaymentService.php`
2. Add gateway-specific logic
3. Implement webhook handlers

Popular gateways to consider:
- Stripe
- PayPal
- Mollie
- Paddle

## Routes

### Public Routes (No Authentication)
- `GET /` - Homepage with plans listing
- `GET /plans` - List all active plans (JSON API)
- `GET /cart` - View shopping cart
- `POST /cart/add` - Add item to cart
- `DELETE /cart/remove/{id}` - Remove item from cart
- `POST /cart/clear` - Clear cart
- `GET /checkout` - Checkout page
- `POST /checkout/complete` - Complete checkout

### Authenticated Routes
- `GET /billing` - Billing dashboard
- `GET /billing/invoices` - List all invoices
- `GET /billing/invoices/{id}` - View invoice details
- `GET /billing/orders` - List all orders
- `POST /payments/create` - Create payment record

## Next Steps

1. Integrate a payment gateway
2. Add email notifications
3. Add automatic recurring billing
4. Create admin panel for plan management
5. Add promo codes/discounts
6. Implement server suspensions for non-payment

## Troubleshooting

### Cart not persisting for guests
- Ensure sessions are configured properly in `.env`
- Check SESSION_DRIVER setting

### Servers not creating after payment
- Check that nodes and eggs exist
- Verify allocations are available
- Check daemon connection

### Plans not showing
- Ensure plans are marked as `is_active`
- Check that nest_id and egg_id are valid


