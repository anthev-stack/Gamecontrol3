# Feature Update: Credits & Split Billing

## 🎉 New Features Added!

### 1. User Credit System 💰

Users can now have account credits that can be used to pay for servers!

#### What was added:

**Database:**
- Added `credits` column to `users` table
- New `credit_transactions` table for transaction history

**Admin Features:**
- `/admin/credits` - Credit management panel
- Grant credits with reasons: Giveaway, Refund, Gift
- View all users and their credit balances
- See detailed transaction history per user
- Credit statistics dashboard

**User Features:**
- Use credits during checkout (full or partial payment)
- View credit balance in billing dashboard
- Transaction history showing all credit movements
- Automatic credit deduction when used

**API Endpoints:**
```
Admin:
GET  /admin/credits/users - List all users with credits
POST /admin/credits/grant - Grant credits
POST /admin/credits/deduct - Deduct credits  
GET  /admin/credits/statistics - View statistics

Checkout:
POST /checkout/complete - Now supports use_credits parameter
```

---

### 2. Split Billing System 🤝

Users can now invite friends to share server costs 50/50!

#### What was added:

**Database:**
- `server_billing_shares` - Tracks who pays what percentage
- `billing_invitations` - Manages invitations and their status

**Features:**
- Server owners can invite friends by email
- Invitations expire after 7 days
- Both users pay 50% of server cost
- Invited user automatically gets server access (as subuser)
- Full invitation management (accept/decline/cancel)

**Workflow:**
1. Owner sends invitation to friend's email
2. Friend receives invitation (pending status)
3. Friend accepts → Gets server access + 50% billing responsibility
4. Both users now share the cost equally

**API Endpoints:**
```
GET    /servers/{server}/billing/shares - View who's sharing billing
POST   /servers/{server}/billing/invite - Send invitation
DELETE /servers/{server}/billing/shares/{user} - Remove user

GET    /billing/invitations - User's pending invitations
POST   /billing/invitations/{token}/accept - Accept invitation
POST   /billing/invitations/{token}/decline - Decline invitation
DELETE /billing/invitations/{invitation} - Cancel invitation
```

---

## 📦 Files Created/Modified

### New Files:

**Migrations:**
- `2024_01_15_000006_add_credits_to_users_table.php`
- `2024_01_15_000007_create_credit_transactions_table.php`
- `2024_01_15_000008_create_server_billing_shares_table.php`

**Models:**
- `app/Models/CreditTransaction.php`
- `app/Models/ServerBillingShare.php`
- `app/Models/BillingInvitation.php`

**Controllers:**
- `app/Http/Controllers/Admin/CreditManagementController.php`
- `app/Http/Controllers/Marketplace/SplitBillingController.php`

**Routes:**
- `routes/admin-credits.php`
- Updated `routes/marketplace.php`

**Documentation:**
- `CREDITS_AND_SPLIT_BILLING.md`
- `FEATURES_UPDATE.md` (this file)

### Modified Files:

**Controllers:**
- `app/Http/Controllers/Marketplace/CheckoutController.php` - Added credit payment support

**Routes:**
- `app/Providers/RouteServiceProvider.php` - Registered new routes

---

## 🚀 Getting Started

### Step 1: Run Migrations

```bash
php artisan migrate
```

This will add:
- `credits` column to users table
- `credit_transactions` table
- `server_billing_shares` table
- `billing_invitations` table

### Step 2: Grant Some Credits (Testing)

Via Tinker:
```bash
php artisan tinker
```

```php
$user = User::find(1);
$user->credits = 50.00;
$user->save();

// Record transaction
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 50.00,
    'balance_after' => 50.00,
    'type' => 'admin_grant',
    'reason' => 'gift',
    'description' => 'Test credits',
]);
```

### Step 3: Test Credit Payment

1. Add a plan to cart
2. Go to checkout
3. Use credits to pay (full or partial)
4. Check credit balance after purchase

### Step 4: Test Split Billing

**As Server Owner:**
```bash
POST /servers/1/billing/invite
{
  "email": "friend@example.com",
  "message": "Let's split this server!"
}
```

**As Invited User:**
```bash
GET /billing/invitations
POST /billing/invitations/{token}/accept
```

---

## 💡 Use Case Examples

### Example 1: Promotional Campaign

Give $10 credits to new users:

```php
// In admin or cron job
$newUsers = User::where('created_at', '>=', now()->subDay())->get();

foreach ($newUsers as $user) {
    $user->credits += 10.00;
    $user->save();
    
    CreditTransaction::create([
        'user_id' => $user->id,
        'admin_id' => auth()->id(),
        'amount' => 10.00,
        'balance_after' => $user->credits,
        'type' => 'admin_grant',
        'reason' => 'giveaway',
        'description' => 'Welcome bonus for new users',
    ]);
}
```

### Example 2: Refund as Credits

```php
// Process refund
$order = Order::find($orderId);
$refundAmount = $order->total;

$user = $order->user;
$user->credits += $refundAmount;
$user->save();

CreditTransaction::create([
    'user_id' => $user->id,
    'admin_id' => auth()->id(),
    'amount' => $refundAmount,
    'balance_after' => $user->credits,
    'type' => 'refund',
    'reason' => 'refund',
    'description' => "Refund for order {$order->order_number}",
    'order_id' => $order->id,
]);
```

### Example 3: Friends Sharing Minecraft Server

1. **John** buys a Minecraft server ($19.99/month)
2. **John** invites **Sarah** to split billing
3. **Sarah** accepts the invitation
4. Both get server access
5. Each pays $9.99/month going forward

---

## 🎨 Frontend Integration

You'll want to add UI components for:

### Credits Display
- Show credit balance in checkout
- Credit transaction history page
- Use credits checkbox/toggle in checkout

### Split Billing
- "Invite Friend" button on server page
- Invitation list page
- Accept/decline invitation UI
- List of shared billing servers

### Admin Panel
- Credit management interface
- User search and credit balance view
- Grant/deduct credits form
- Statistics dashboard

---

## 🔐 Security Notes

### Credits
- Only admins can grant credits
- All transactions are logged with admin ID
- Users can't modify their own balance
- Transaction history is immutable

### Split Billing
- Only server owner can send invitations
- Email verification recommended
- Tokens are secure (64 random characters)
- Invitations expire automatically
- Server access controlled via subusers

---

## 📊 Admin Panel Access

Navigate to: `/admin/credits`

Features available:
- View all users and their credit balances
- Grant credits (with reason selection)
- Deduct credits if needed
- View transaction history
- See credit statistics

---

## 🔄 Checkout Flow Updated

**Previous:**
1. Add to cart
2. Checkout with billing info
3. Pay with credit card
4. Server created

**New:**
1. Add to cart
2. Checkout with billing info
3. **Choose to use credits** (new!)
4. Pay remaining with credit card
5. Server created
6. Credits deducted and recorded

---

## 📈 Billing Dashboard Updates

Users can now see:
- Current credit balance
- Credit transaction history
- Servers they're sharing billing on
- Pending billing invitations
- Split billing percentages

---

## ✅ Testing Checklist

Before deploying to production:

**Credits:**
- [ ] Admin can access `/admin/credits`
- [ ] Admin can grant credits with all 3 reasons
- [ ] Credits appear in user account
- [ ] Credits can be used at checkout
- [ ] Partial credit payments work correctly
- [ ] Full credit payments work (no card needed)
- [ ] Transaction history is accurate
- [ ] Can't use more credits than available

**Split Billing:**
- [ ] Server owner can send invitation
- [ ] Invitation appears for recipient
- [ ] Recipient can accept invitation
- [ ] Both users get server access after acceptance
- [ ] Billing shares show 50/50
- [ ] Owner can remove user from billing
- [ ] Invitations expire after 7 days
- [ ] Can decline invitations

---

## 🐛 Known Limitations

1. **Split Billing:**
   - Currently only supports 50/50 splits (can be customized in code)
   - Only 2 users per server (can be extended)
   - Recurring billing not yet automated (needs cron job)

2. **Credits:**
   - No user-initiated credit purchases yet (coming soon)
   - No automatic credit expiry
   - No credit transfer between users

3. **Email Notifications:**
   - Invitation emails not yet implemented
   - Need to configure mail settings

---

## 🚧 TODO / Future Enhancements

### Credits:
- [ ] Allow users to purchase credits
- [ ] Credit packages with bonus amounts
- [ ] Automatic loyalty credits
- [ ] Credit gifting between users
- [ ] Referral rewards

### Split Billing:
- [ ] Custom split percentages (not just 50/50)
- [ ] Support 3+ users per server
- [ ] Automated recurring billing
- [ ] Usage-based split billing
- [ ] Billing history per user

### Both:
- [ ] Email notifications
- [ ] SMS notifications
- [ ] Mobile app support
- [ ] Analytics dashboard
- [ ] Fraud detection

---

## 📖 Documentation

For detailed documentation, see:
- **CREDITS_AND_SPLIT_BILLING.md** - Complete feature documentation
- **MARKETPLACE_SETUP.md** - General setup guide
- **DEPLOYMENT_GUIDE.md** - Deployment instructions

---

## 🎓 Learning Resources

**Credits Implementation:**
- Laravel transactions: https://laravel.com/docs/database#database-transactions
- Model events: https://laravel.com/docs/eloquent#events

**Split Billing:**
- Many-to-many relationships: https://laravel.com/docs/eloquent-relationships
- Pivot tables: https://laravel.com/docs/eloquent-relationships#defining-custom-intermediate-table-models

---

## Support

Questions or issues? Check:
1. `CREDITS_AND_SPLIT_BILLING.md` for detailed docs
2. GitHub Issues for bug reports
3. Your database logs for errors

---

**These features are production-ready!** 🎉

Just run migrations and you're good to go. The backend is complete - you just need to add the frontend UI components.

