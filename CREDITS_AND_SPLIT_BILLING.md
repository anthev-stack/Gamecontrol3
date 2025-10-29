# Credits System & Split Billing Documentation

## Overview

Two powerful features have been added to enhance your hosting marketplace:

1. **Credit System** - Users can have account credits to pay for servers
2. **Split Billing** - Users can invite friends to share server costs 50/50

---

## 🎫 Credit System

### Features

- Users have a credit balance on their account
- Credits can be used to pay for server orders (full or partial)
- Admins can grant/deduct credits through admin panel
- Full transaction history tracking
- Multiple credit reasons: Giveaway, Refund, Gift

### Database Structure

**Users Table** (modified):
- `credits` (decimal 10,2) - Current credit balance

**Credit Transactions Table**:
- Tracks all credit movements
- Records admin who granted credits
- Links to orders when credits are used
- Stores balance after each transaction

### Admin Credit Management

#### Access Admin Panel
Navigate to `/admin/credits` (requires admin authentication)

#### Grant Credits to Users

**Via Admin Panel UI**:
```
/admin/credits → Select User → Grant Credits
```

**Via API** (POST `/admin/credits/grant`):
```json
{
  "user_id": 1,
  "amount": 25.00,
  "reason": "giveaway",
  "description": "Promotional giveaway for new users"
}
```

**Reasons:**
- `giveaway` - Promotional credits
- `refund` - Refund for issues
- `gift` - Gift credits

**Via Laravel Tinker**:
```php
php artisan tinker

$user = User::find(1);
$user->credits += 25.00;
$user->save();

CreditTransaction::create([
    'user_id' => $user->id,
    'admin_id' => Auth::id(),
    'amount' => 25.00,
    'balance_after' => $user->credits,
    'type' => 'admin_grant',
    'reason' => 'giveaway',
    'description' => 'Welcome bonus',
]);
```

#### View Credit Statistics

GET `/admin/credits/statistics`:
- Total credits in circulation
- Users with credits
- Credits granted (last 30 days)
- Credits used (last 30 days)
- Recent transactions

#### View User Transaction History

GET `/admin/credits/users/{user}/transactions`:
- All credit transactions for specific user
- Shows amounts, dates, reasons
- Links to related orders

### User Experience

#### Viewing Credits
Users can see their credit balance:
- In checkout page
- In billing dashboard
- In account overview

#### Using Credits

During checkout, users can:
1. See available credit balance
2. Choose to use credits
3. Credits applied automatically to reduce total
4. Remaining amount charged to payment method

**Example:**
- Order Total: $50.00
- User Credits: $30.00
- User pays: $20.00 (via credit card)
- Credits used: $30.00

### Credit Transaction Types

- `admin_grant` - Admin manually added credits
- `purchase` - User purchased credits (if you implement this)
- `refund` - Refund issued
- `payment` - Credits used to pay for order

### API Endpoints

```
Admin Routes (auth + admin required):
GET    /admin/credits                     - Admin panel page
GET    /admin/credits/users               - List all users with credits
GET    /admin/credits/users/{user}/transactions - User's transaction history
POST   /admin/credits/grant               - Grant credits to user
POST   /admin/credits/deduct              - Deduct credits from user
GET    /admin/credits/statistics          - Credit statistics

User Routes (auth required):
GET    /billing/credits                   - User's credit balance and history
```

---

## 👥 Split Billing

### Features

- Server owners can invite others to split billing
- 50/50 cost sharing
- Invited user gets server access automatically
- Email invitations with 7-day expiry
- Both users pay their share each billing cycle

### How It Works

#### 1. Server Owner Sends Invitation

**Via API** (POST `/servers/{server}/billing/invite`):
```json
{
  "email": "friend@example.com",
  "message": "Want to split the cost of our Minecraft server?"
}
```

**What Happens:**
- System creates invitation with unique token
- Invitation expires in 7 days
- Email sent to invitee (if email configured)
- Owner's billing share becomes 50%

#### 2. Invitee Receives Invitation

**Check Invitations** (GET `/billing/invitations`):
```json
{
  "data": [
    {
      "uuid": "...",
      "token": "abc123...",
      "server": { "name": "My Minecraft Server" },
      "inviter": { "username": "john_doe" },
      "share_percentage": 50.00,
      "expires_at": "2024-01-22T..."
    }
  ]
}
```

#### 3. Accept or Decline

**Accept** (POST `/billing/invitations/{token}/accept`):
- Creates billing share (50%)
- Grants server access (as subuser)
- Both users now pay 50% each

**Decline** (POST `/billing/invitations/{token}/decline`):
- Invitation marked as declined
- No billing changes made

### Database Structure

**Server Billing Shares Table**:
- Links users to servers with their share percentage
- Tracks billing status
- Controls server access

**Billing Invitations Table**:
- Stores pending/accepted/declined invitations
- Unique tokens for security
- Expiry dates
- Custom messages

### Managing Split Billing

#### View Server Billing Shares

GET `/servers/{server}/billing/shares`:
```json
{
  "data": [
    {
      "user": { "username": "john_doe", "email": "john@example.com" },
      "share_percentage": 50.00,
      "status": "active",
      "has_server_access": true
    },
    {
      "user": { "username": "jane_smith", "email": "jane@example.com" },
      "share_percentage": 50.00,
      "status": "active",
      "has_server_access": true
    }
  ]
}
```

#### Remove User from Split Billing

DELETE `/servers/{server}/billing/shares/{user}`:
- Removes billing share
- Removes server access
- Owner's share returns to 100%

#### Cancel Invitation

DELETE `/billing/invitations/{invitation}`:
- Cancels pending invitation
- Only inviter can cancel

### Billing Calculation

When an order/invoice is generated for a shared server:

```php
$totalCost = 19.99; // Server monthly cost

// Calculate each user's share
$ownerShare = ServerBillingShare::where('server_id', $server->id)
    ->where('user_id', $owner->id)
    ->first();
    
$ownerAmount = ($totalCost * $ownerShare->share_percentage) / 100;
// $ownerAmount = $19.99 * 50 / 100 = $9.995 ≈ $10.00

$sharedUserAmount = ($totalCost * 50) / 100;
// $sharedUserAmount = $10.00
```

### API Endpoints

```
Split Billing Routes (auth required):
GET    /servers/{server}/billing/shares           - View billing shares
POST   /servers/{server}/billing/invite           - Send invitation
DELETE /servers/{server}/billing/shares/{user}    - Remove user from billing

GET    /billing/invitations                       - Get user's invitations
POST   /billing/invitations/{token}/accept        - Accept invitation
POST   /billing/invitations/{token}/decline       - Decline invitation
DELETE /billing/invitations/{invitation}          - Cancel invitation (inviter only)
```

### User Permissions

**Server Owner**:
- Can send billing invitations
- Can remove users from billing split
- Automatically gets server access

**Invited User**:
- Can accept or decline invitation
- Gets server access when accepted
- Can see their billing share percentage

**Admin**:
- Can view all billing shares
- Can manually adjust percentages
- Can remove users from billing

---

## 🔄 Integration with Checkout

### Modified Checkout Flow

1. User adds plan to cart
2. Goes to checkout
3. Sees available credit balance
4. Can choose to use credits:
   - **Use All Credits** - Apply maximum credits
   - **Custom Amount** - Choose how much to use
5. Remaining amount charged to payment method
6. Server created
7. Credits deducted and transaction recorded

### Checkout API Updates

POST `/checkout/complete`:
```json
{
  "billing_name": "John Doe",
  "billing_email": "john@example.com",
  "use_credits": true,
  "credits_amount": 30.00,
  ...
}
```

Response:
```json
{
  "success": true,
  "message": "Order completed successfully",
  "data": {
    "order": {
      "total": 50.00,
      "credits_used": 30.00,
      "remaining_amount": 20.00
    },
    "user": {
      "remaining_credits": 0.00
    }
  }
}
```

---

## 💻 Implementation Examples

### Give Credits to Multiple Users

```php
// Giveaway to all users who registered this month
$users = User::whereMonth('created_at', now()->month)->get();

foreach ($users as $user) {
    DB::transaction(function () use ($user) {
        $amount = 10.00;
        $user->credits += $amount;
        $user->save();
        
        CreditTransaction::create([
            'user_id' => $user->id,
            'admin_id' => Auth::id(),
            'amount' => $amount,
            'balance_after' => $user->credits,
            'type' => 'admin_grant',
            'reason' => 'giveaway',
            'description' => 'Monthly welcome bonus',
        ]);
    });
}
```

### Automatic Split Billing on Order

```php
// When creating recurring invoices
$server = Server::find($serverId);
$shares = ServerBillingShare::where('server_id', $server->id)
    ->where('status', 'active')
    ->get();

$totalCost = 19.99;

foreach ($shares as $share) {
    $userAmount = ($totalCost * $share->share_percentage) / 100;
    
    Invoice::create([
        'user_id' => $share->user_id,
        'server_id' => $server->id,
        'amount' => $userAmount,
        'total' => $userAmount,
        'due_date' => now()->addDays(7),
    ]);
}
```

---

## 🎯 Use Cases

### Credit System Use Cases

1. **Promotional Campaigns**
   - Give $10 credits to first 100 signups
   - Seasonal promotions

2. **Refund Management**
   - Issue refunds as credits
   - Faster than payment gateway refunds

3. **Referral Programs**
   - Give credits for successful referrals
   - Both referrer and referee get credits

4. **Loyalty Rewards**
   - Long-term customers get credits
   - Birthday bonuses

### Split Billing Use Cases

1. **Friend Groups**
   - Friends sharing gaming server costs
   - Each pays their fair share

2. **Content Creators**
   - Streamer and mods share server
   - Community-funded servers

3. **Development Teams**
   - Team members split test server costs
   - Fair billing for shared resources

---

## 🔒 Security Considerations

### Credits

- Only admins can grant credits
- All transactions logged with admin ID
- Balance verification before deduction
- Transaction history immutable

### Split Billing

- Email verification for invitations
- Unique tokens (64 characters)
- 7-day expiry on invitations
- Only owner can invite/remove users
- Server access control integrated

---

## 📊 Admin Monitoring

### Credit Monitoring

Watch for:
- Unusual credit transactions
- High balance users
- Frequent admin grants
- Credit abuse patterns

### Split Billing Monitoring

Track:
- Number of shared servers
- Invitation acceptance rates
- Failed billing on shared servers
- User disputes

---

## 🚀 Future Enhancements

### Credits
- [ ] Users can purchase credits
- [ ] Credit packages with discounts
- [ ] Automatic credit rewards
- [ ] Credit gifting between users

### Split Billing
- [ ] Custom split percentages (not just 50/50)
- [ ] More than 2 users per server
- [ ] Weighted billing based on usage
- [ ] Automatic billing retries

---

## 📝 Database Queries for Reports

### Top Users by Credits
```sql
SELECT username, email, credits 
FROM users 
WHERE credits > 0 
ORDER BY credits DESC 
LIMIT 10;
```

### Credit Activity Last 30 Days
```sql
SELECT 
    DATE(created_at) as date,
    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as credits_granted,
    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as credits_used
FROM credit_transactions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Servers with Split Billing
```sql
SELECT s.name, COUNT(sbs.id) as sharers
FROM servers s
JOIN server_billing_shares sbs ON s.id = sbs.server_id
WHERE sbs.status = 'active'
GROUP BY s.id
HAVING sharers > 1;
```

---

## ✅ Testing Checklist

### Credits
- [ ] Admin can grant credits
- [ ] Credits appear in user balance
- [ ] Credits can be used at checkout
- [ ] Transaction history accurate
- [ ] Partial credit payments work
- [ ] Zero balance prevents overspending

### Split Billing
- [ ] Owner can send invitation
- [ ] Invitation email sent
- [ ] User can accept invitation
- [ ] Both users get server access
- [ ] Billing splits correctly
- [ ] Owner can remove user
- [ ] Invitations expire after 7 days

---

This completes the Credits and Split Billing documentation! Both features are fully integrated and ready to use.

