# VM Deployment Quick Start Guide

## 🎯 Complete Installation on Fresh Ubuntu VM

This guide assumes:
- Ubuntu 20.04/22.04 VM
- Root or sudo access
- Domain pointed to VM IP

---

## 📋 Step 1: Prepare Your VM

### SSH into your VM
```bash
ssh root@your-vm-ip
# or
ssh ubuntu@your-vm-ip
```

### Update system
```bash
sudo apt update && sudo apt upgrade -y
```

---

## 🔧 Step 2: Install Required Software

### Install PHP 8.2 and Extensions
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-{cli,gd,mysql,pdo,mbstring,tokenizer,bcmath,xml,fpm,curl,zip}

# Verify installation
php -v
```

### Install Composer
```bash
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Verify
composer --version
```

### Install Node.js 18 and Yarn
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Yarn
sudo npm install -g yarn

# Verify
node -v
yarn -v
```

### Install MySQL
```bash
sudo apt install -y mysql-server

# Secure installation
sudo mysql_secure_installation
# Answer: Y for all questions
# Set a strong root password
```

### Install Nginx
```bash
sudo apt install -y nginx
```

### Install Redis (Optional but recommended)
```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
```

---

## 📥 Step 3: Clone Your Repository

```bash
# Navigate to web directory
cd /var/www

# Clone from GitHub
sudo git clone https://github.com/anthev-stack/Gamecontrol3.git

# Set ownership
sudo chown -R www-data:www-data /var/www/Gamecontrol3

# Navigate into directory
cd Gamecontrol3
```

---

## ⚙️ Step 4: Configure Application

### Copy environment file
```bash
sudo cp .env.example .env
sudo nano .env
```

### Configure .env file
```env
APP_NAME="Your Hosting Company"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gamecontrol
DB_USERNAME=gamecontrol_user
DB_PASSWORD=CHANGE_THIS_PASSWORD

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Press `CTRL+X`, then `Y`, then `Enter` to save.

---

## 🗄️ Step 5: Setup Database

### Create database and user
```bash
sudo mysql -u root -p
```

In MySQL console:
```sql
CREATE DATABASE gamecontrol;
CREATE USER 'gamecontrol_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON gamecontrol.* TO 'gamecontrol_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**⚠️ Use the same password you set in .env file!**

---

## 📦 Step 6: Install Dependencies

```bash
cd /var/www/Gamecontrol3

# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install JavaScript dependencies
sudo -u www-data yarn install

# Generate application key
sudo -u www-data php artisan key:generate --force
```

---

## 🔄 Step 7: Run Migrations

```bash
# Run all migrations (including marketplace, credits, split billing)
sudo -u www-data php artisan migrate --force

# You should see:
# ✔ Created hosting_plans table
# ✔ Created carts table
# ✔ Created orders table
# ✔ Created invoices table
# ✔ Created payments table
# ✔ Added credits to users table
# ✔ Created credit_transactions table
# ✔ Created server_billing_shares table
```

---

## 🏗️ Step 8: Build Assets

```bash
# Build production assets
sudo -u www-data yarn run build

# This may take a few minutes
```

---

## 🔐 Step 9: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 /var/www/Gamecontrol3/storage
sudo chmod -R 755 /var/www/Gamecontrol3/bootstrap/cache
```

---

## 🌐 Step 10: Configure Nginx

### Create Nginx configuration
```bash
sudo nano /etc/nginx/sites-available/gamecontrol
```

Paste this configuration:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/Gamecontrol3/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**⚠️ Replace `yourdomain.com` with your actual domain!**

### Enable the site
```bash
sudo ln -s /etc/nginx/sites-available/gamecontrol /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 🔒 Step 11: Install SSL Certificate

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Follow prompts:
# - Enter email address
# - Agree to terms
# - Choose to redirect HTTP to HTTPS (option 2)

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## ⏰ Step 12: Setup Cron Jobs

```bash
sudo crontab -e -u www-data
```

Add this line:
```cron
* * * * * cd /var/www/Gamecontrol3 && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 Step 13: Setup Queue Worker

### Create systemd service
```bash
sudo nano /etc/systemd/system/gamecontrol-worker.service
```

Paste this:
```ini
[Unit]
Description=Gamecontrol Queue Worker
After=network.target mysql.service redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/Gamecontrol3/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/www/Gamecontrol3/storage/logs/worker.log
StandardError=append:/var/www/Gamecontrol3/storage/logs/worker-error.log

[Install]
WantedBy=multi-user.target
```

### Enable and start worker
```bash
sudo systemctl daemon-reload
sudo systemctl enable gamecontrol-worker
sudo systemctl start gamecontrol-worker
sudo systemctl status gamecontrol-worker
```

---

## 👤 Step 14: Create Admin User

```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan p:user:make
```

Follow prompts:
```
Email Address: admin@yourdomain.com
Username: admin
First Name: Admin
Last Name: User
Password: [enter strong password]
Is this user an administrator? yes
```

---

## 🎯 Step 15: Create Test Hosting Plans

```bash
sudo -u www-data php artisan tinker
```

In Tinker:
```php
$nest = \Pterodactyl\Models\Nest::first();
$egg = \Pterodactyl\Models\Egg::first();

// If no nest/egg exists, you need to create them first via admin panel

\Pterodactyl\Models\HostingPlan::create([
    'name' => 'Starter Plan',
    'description' => 'Perfect for small servers',
    'slug' => 'starter-plan',
    'memory' => 2048,
    'swap' => 512,
    'disk' => 5120,
    'io' => 500,
    'cpu' => 100,
    'nest_id' => $nest->id,
    'egg_id' => $egg->id,
    'database_limit' => 1,
    'allocation_limit' => 3,
    'backup_limit' => 1,
    'price' => 9.99,
    'billing_period' => 'monthly',
    'is_active' => true,
    'is_featured' => true,
]);

\Pterodactyl\Models\HostingPlan::create([
    'name' => 'Pro Plan',
    'description' => 'For growing communities',
    'slug' => 'pro-plan',
    'memory' => 4096,
    'swap' => 1024,
    'disk' => 10240,
    'io' => 500,
    'cpu' => 200,
    'nest_id' => $nest->id,
    'egg_id' => $egg->id,
    'database_limit' => 3,
    'allocation_limit' => 5,
    'backup_limit' => 3,
    'price' => 19.99,
    'billing_period' => 'monthly',
    'is_active' => true,
    'is_featured' => false,
]);

exit
```

---

## ✅ Step 16: Test Everything

### Test Website
1. Visit `https://yourdomain.com`
2. Should see hosting plans homepage
3. Try adding to cart

### Test Admin Panel
1. Visit `https://yourdomain.com/admin`
2. Login with admin credentials
3. Navigate to `/admin/credits`
4. Should see credit management panel

### Grant Test Credits
```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan tinker
```

```php
$user = \Pterodactyl\Models\User::first();
$user->credits = 50.00;
$user->save();

\Pterodactyl\Models\CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 50.00,
    'balance_after' => 50.00,
    'type' => 'admin_grant',
    'reason' => 'gift',
    'description' => 'Test credits',
]);

exit
```

### Test Full Flow
1. Browse plans
2. Add to cart
3. Checkout (create account)
4. Use credits to pay
5. Verify server created
6. Check billing dashboard

---

## 🔥 Step 17: Configure Firewall

```bash
# Allow SSH, HTTP, and HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## 📊 Step 18: Monitoring & Logs

### View Laravel logs
```bash
sudo tail -f /var/www/Gamecontrol3/storage/logs/laravel.log
```

### View Nginx logs
```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
```

### View Queue Worker logs
```bash
sudo tail -f /var/www/Gamecontrol3/storage/logs/worker.log
```

### Check Services
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status gamecontrol-worker
```

---

## 🔄 Updating After Changes

When you push updates to GitHub:

```bash
cd /var/www/Gamecontrol3

# Pull latest code
sudo -u www-data git pull origin main

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data yarn install

# Run new migrations
sudo -u www-data php artisan migrate --force

# Rebuild assets
sudo -u www-data yarn run build

# Clear cache
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart gamecontrol-worker
```

---

## 🐛 Troubleshooting

### 500 Error
```bash
# Check logs
sudo tail -50 /var/www/Gamecontrol3/storage/logs/laravel.log

# Fix permissions
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 /var/www/Gamecontrol3/storage
```

### Can't connect to database
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
sudo mysql -u gamecontrol_user -p gamecontrol
```

### Plans not showing
```bash
# Check if plans exist
sudo -u www-data php artisan tinker
>>> \Pterodactyl\Models\HostingPlan::count()
```

### Queue not processing
```bash
# Restart worker
sudo systemctl restart gamecontrol-worker

# Check status
sudo systemctl status gamecontrol-worker
```

---

## 📝 Important URLs

After deployment, access:

- **Homepage:** `https://yourdomain.com`
- **Admin Panel:** `https://yourdomain.com/admin`
- **Cart:** `https://yourdomain.com/cart`
- **Checkout:** `https://yourdomain.com/checkout`
- **Billing:** `https://yourdomain.com/billing`
- **Credit Management:** `https://yourdomain.com/admin/credits`

---

## 🎉 Success!

If everything works:
- ✅ Website loads at your domain
- ✅ SSL certificate installed
- ✅ Plans visible on homepage
- ✅ Can add to cart
- ✅ Checkout works
- ✅ Admin panel accessible
- ✅ Credit system functional
- ✅ All services running

**Your hosting marketplace is now live!** 🚀

---

## 📞 Need Help?

Check these files:
- `DEPLOYMENT_GUIDE.md` - Detailed deployment guide
- `CREDITS_AND_SPLIT_BILLING.md` - Feature documentation
- `MARKETPLACE_SETUP.md` - Setup instructions
- Laravel logs: `/var/www/Gamecontrol3/storage/logs/`

---

**Total Installation Time:** ~30-45 minutes

Good luck with your hosting business! 💼

