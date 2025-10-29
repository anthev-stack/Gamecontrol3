# Fresh VM Installation - Gamecontrol3 (Pterodactyl + Marketplace)

## 🎯 Complete Installation from Scratch

This guide assumes a **fresh Ubuntu 22.04 VM** with root access.

---

## ✅ **Prerequisites**

- Fresh Ubuntu 22.04 VM
- Root or sudo access
- Domain name pointed to VM IP (optional for initial setup)
- Minimum 2GB RAM, 20GB disk

---

## 🚀 **COMPLETE INSTALLATION SCRIPT**

SSH into your VM and run these commands **one section at a time**:

---

### **SECTION 1: Update System & Install Software**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-{cli,gd,mysql,pdo,mbstring,tokenizer,bcmath,xml,fpm,curl,zip}

# Verify PHP
php -v
# Should show PHP 8.2.x
```

---

### **SECTION 2: Install Composer**

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Verify Composer
composer --version
# Should show Composer version 2.x
```

---

### **SECTION 3: Install Node.js 18 & Yarn**

```bash
# Remove any old Node.js
sudo apt remove --purge nodejs -y
sudo apt autoremove -y

# Install Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Verify Node.js and npm
node -v
npm -v
# Should show v18.x.x and npm 10.x.x

# Install Yarn
sudo npm install -g yarn

# Verify Yarn
yarn -v
# Should show 1.x.x
```

---

### **SECTION 4: Install MySQL**

```bash
# Install MySQL
sudo apt install -y mysql-server

# Start MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Check status
sudo systemctl status mysql
# Press 'q' to exit
```

---

### **SECTION 5: Create Database**

```bash
# Create database and user (NOTE: No special characters in password!)
sudo mysql <<EOF
CREATE DATABASE gamecontrol;
CREATE USER 'gamecontrol_user'@'localhost' IDENTIFIED BY 'GameControl2024Pass';
GRANT ALL PRIVILEGES ON gamecontrol.* TO 'gamecontrol_user'@'localhost';
FLUSH PRIVILEGES;
EXIT
EOF

# Verify database was created
sudo mysql -e "SHOW DATABASES;" | grep gamecontrol
# Should show: gamecontrol

# Verify user was created
sudo mysql -e "SELECT user, host FROM mysql.user WHERE user='gamecontrol_user';"
# Should show: gamecontrol_user | localhost
```

---

### **SECTION 6: Install Nginx & Redis**

```bash
# Install Nginx and Redis
sudo apt install -y nginx redis-server

# Start services
sudo systemctl start nginx redis-server
sudo systemctl enable nginx redis-server

# Check status
sudo systemctl status nginx
sudo systemctl status redis-server
# Press 'q' to exit each
```

---

### **SECTION 7: Clone Repository**

```bash
# Navigate to web directory
cd /var/www

# Clone from GitHub
sudo git clone https://github.com/anthev-stack/Gamecontrol3.git

# Navigate into directory
cd Gamecontrol3

# Check files are there
ls -l
# You should see: app, database, resources, routes, etc.
```

---

### **SECTION 8: Create .env Configuration**

```bash
# Create .env file
sudo tee /var/www/Gamecontrol3/.env > /dev/null <<'EOF'
APP_NAME=Gamecontrol
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_LOCALE_PHP=en_US

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gamecontrol
DB_USERNAME=gamecontrol_user
DB_PASSWORD=GameControl2024Pass

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@localhost
MAIL_FROM_NAME="${APP_NAME}"

HASHIDS_SALT=
HASHIDS_LENGTH=8

TRUSTED_PROXIES=*
EOF

# Set proper ownership
sudo chown www-data:www-data /var/www/Gamecontrol3/.env

# Verify .env exists
cat /var/www/Gamecontrol3/.env | head -5
```

---

### **SECTION 9: Set Permissions**

```bash
# Set ownership to web user
sudo chown -R www-data:www-data /var/www/Gamecontrol3

# Set proper permissions
sudo chmod -R 755 /var/www/Gamecontrol3
sudo chmod -R 755 /var/www/Gamecontrol3/storage
sudo chmod -R 755 /var/www/Gamecontrol3/bootstrap/cache
```

---

### **SECTION 10: Install Dependencies**

```bash
cd /var/www/Gamecontrol3

# Generate Laravel app key
sudo -u www-data php artisan key:generate --force

# Install PHP dependencies (this may take a few minutes)
sudo -u www-data composer install --no-dev --optimize-autoloader

# You should see: "Generating optimized autoload files"
```

---

### **SECTION 11: Install JavaScript Dependencies**

```bash
cd /var/www/Gamecontrol3

# Install JS dependencies (this may take 2-5 minutes)
sudo -u www-data yarn install

# You should see packages being installed
```

---

### **SECTION 12: Run Database Migrations**

```bash
cd /var/www/Gamecontrol3

# Run all migrations
sudo -u www-data php artisan migrate --force

# You should see:
# ✓ All Pterodactyl tables created
# ✓ hosting_plans table created
# ✓ carts table created
# ✓ orders table created
# ✓ invoices table created
# ✓ payments table created
# ✓ credits added to users table
# ✓ credit_transactions table created
# ✓ server_billing_shares table created
```

---

### **SECTION 13: Build Frontend Assets**

```bash
cd /var/www/Gamecontrol3

# Build production assets (this will take 5-10 minutes)
sudo -u www-data yarn run build

# You should see webpack building...
# Wait for "webpack compiled successfully"
```

---

### **SECTION 14: Create Admin User**

```bash
cd /var/www/Gamecontrol3

# Create your admin account
sudo -u www-data php artisan p:user:make

# Follow the prompts:
# Email: your-email@example.com
# Username: admin
# First Name: Admin
# Last Name: User
# Password: [enter a strong password]
# Is admin?: yes
```

---

### **SECTION 15: Configure Nginx**

```bash
# Create Nginx configuration
sudo tee /etc/nginx/sites-available/gamecontrol > /dev/null <<'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/Gamecontrol3/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Remove default Nginx site
sudo rm -f /etc/nginx/sites-enabled/default

# Enable our site
sudo ln -s /etc/nginx/sites-available/gamecontrol /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t
# Should show: "syntax is ok" and "test is successful"

# Restart Nginx
sudo systemctl restart nginx
```

---

### **SECTION 16: Setup Queue Worker**

```bash
# Create systemd service for queue worker
sudo tee /etc/systemd/system/gamecontrol-worker.service > /dev/null <<'EOF'
[Unit]
Description=Gamecontrol Queue Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/Gamecontrol3/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd
sudo systemctl daemon-reload

# Enable and start worker
sudo systemctl enable gamecontrol-worker
sudo systemctl start gamecontrol-worker

# Check status
sudo systemctl status gamecontrol-worker
# Press 'q' to exit
```

---

### **SECTION 17: Setup Cron Job**

```bash
# Add Laravel scheduler to cron
echo "* * * * * cd /var/www/Gamecontrol3 && php artisan schedule:run >> /dev/null 2>&1" | sudo -u www-data crontab -

# Verify cron was added
sudo crontab -l -u www-data
```

---

### **SECTION 18: Create Test Hosting Plans**

```bash
cd /var/www/Gamecontrol3

# Launch Laravel Tinker
sudo -u www-data php artisan tinker
```

**In Tinker, paste this:**

```php
// First, check if you have a nest and egg
$nest = \Pterodactyl\Models\Nest::first();
$egg = \Pterodactyl\Models\Egg::first();

// If they exist, create a plan
if ($nest && $egg) {
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
    echo "Plan created!\n";
} else {
    echo "You need to create a nest and egg first via the admin panel\n";
}

exit
```

---

### **SECTION 19: Give Admin User Test Credits**

```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan tinker
```

**In Tinker:**

```php
$admin = \Pterodactyl\Models\User::where('root_admin', true)->first();
$admin->credits = 100.00;
$admin->save();

\Pterodactyl\Models\CreditTransaction::create([
    'user_id' => $admin->id,
    'amount' => 100.00,
    'balance_after' => 100.00,
    'type' => 'admin_grant',
    'reason' => 'gift',
    'description' => 'Initial test credits',
]);

echo "Admin has " . $admin->credits . " credits\n";
exit
```

---

### **SECTION 20: Configure Firewall**

```bash
# Allow necessary ports
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS

# Enable firewall
sudo ufw --force enable

# Check status
sudo ufw status
```

---

## 🎉 **Installation Complete!**

### **Access Your Panel:**

Find your VM's IP address:
```bash
ip addr show | grep "inet " | grep -v 127.0.0.1
```

Visit in browser: `http://YOUR_VM_IP`

You should see:
- ✅ Hosting marketplace homepage
- ✅ Browse hosting plans

### **Access Admin Panel:**

Visit: `http://YOUR_VM_IP/admin`

Login with the admin credentials you created in Section 14.

---

## 📝 **What You Have Now:**

✅ Pterodactyl Panel base installation  
✅ Hosting marketplace (plans, cart, checkout)  
✅ User credit system  
✅ Split billing system  
✅ All database tables created  
✅ Frontend assets built  
✅ Admin user created  
✅ Test credits loaded  

---

## 🔧 **Next Steps:**

### 1. **Create Node & Allocations** (via Admin Panel)
```
/admin/nodes → Create Node
/admin/nodes/{node}/allocation → Create Allocations
```

### 2. **Create Nest & Egg** (if you want Minecraft/etc)
```
/admin/nests → Create Nest
/admin/nests/{nest}/eggs → Create Egg
```

### 3. **Create More Hosting Plans** (via Tinker)
```bash
sudo -u www-data php artisan tinker
```

```php
$nest = \Pterodactyl\Models\Nest::first();
$egg = \Pterodactyl\Models\Egg::first();

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

### 4. **Setup SSL (Optional but Recommended)**
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### 5. **Test Everything:**
- Visit homepage to see plans
- Add a plan to cart
- Go through checkout
- Check `/admin/credits` for credit management
- Test split billing invitation

---

## 🐛 **Troubleshooting**

### Check Logs
```bash
# Laravel logs
sudo tail -f /var/www/Gamecontrol3/storage/logs/laravel.log

# Nginx errors
sudo tail -f /var/log/nginx/error.log
```

### Fix Permissions
```bash
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 /var/www/Gamecontrol3/storage
sudo chmod -R 755 /var/www/Gamecontrol3/bootstrap/cache
```

### Restart Services
```bash
sudo systemctl restart nginx php8.2-fpm mysql redis-server gamecontrol-worker
```

### Clear Cache
```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
```

---

## ✅ **Verification Checklist**

After installation, verify:

- [ ] PHP 8.2 installed: `php -v`
- [ ] Composer installed: `composer --version`
- [ ] Node.js 18 installed: `node -v`
- [ ] Yarn installed: `yarn -v`
- [ ] MySQL running: `sudo systemctl status mysql`
- [ ] Database created: `sudo mysql -e "SHOW DATABASES;" | grep gamecontrol`
- [ ] Nginx running: `sudo systemctl status nginx`
- [ ] Code cloned: `ls /var/www/Gamecontrol3`
- [ ] .env exists: `cat /var/www/Gamecontrol3/.env | head -5`
- [ ] Dependencies installed: `ls /var/www/Gamecontrol3/vendor`
- [ ] Migrations run: `sudo mysql -e "USE gamecontrol; SHOW TABLES;"`
- [ ] Assets built: `ls /var/www/Gamecontrol3/public/js`
- [ ] Admin created: Can login at `/admin`
- [ ] Queue worker running: `sudo systemctl status gamecontrol-worker`

---

## 🎯 **Quick Test**

Visit `http://YOUR_VM_IP` in your browser.

You should see the hosting marketplace homepage! 🎉

---

## 📚 **Important Notes**

1. **Change APP_URL** in `.env` to your actual domain when you have one
2. **Setup Wings** (Pterodactyl daemon) separately for actual game server hosting
3. **Configure email** in `.env` for invitations and notifications
4. **Add SSL certificate** before going live
5. **Setup automatic backups** of your database

---

## 🆘 **Need Help?**

If you get stuck:
1. Check the logs (see Troubleshooting section)
2. Verify each step completed successfully
3. Make sure all services are running
4. Check file permissions

---

**Installation time: ~20-30 minutes**

Good luck! 🚀

