# Quick Commands Reference

## 🚀 Push to GitHub

```bash
cd /path/to/Gamecontrol3
git add .
git commit -m "Complete hosting marketplace with credits and split billing"
git remote add origin https://github.com/anthev-stack/Gamecontrol3.git
git branch -M main
git push -u origin main
```

---

## 📥 Deploy to VM (All-in-One Script)

**⚠️ Run this on your VM after SSH**

```bash
# Part 1: Install Software
sudo apt update && sudo apt upgrade -y
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.2 php8.2-{cli,gd,mysql,pdo,mbstring,tokenizer,bcmath,xml,fpm,curl,zip} nginx mysql-server redis-server
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - && sudo apt install -y nodejs
sudo npm install -g yarn

# Part 2: Setup Database
sudo mysql -e "CREATE DATABASE gamecontrol;"
sudo mysql -e "CREATE USER 'gamecontrol_user'@'localhost' IDENTIFIED BY 'YourPasswordHere123!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON gamecontrol.* TO 'gamecontrol_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Part 3: Clone and Setup
cd /var/www
sudo git clone https://github.com/anthev-stack/Gamecontrol3.git
cd Gamecontrol3
sudo cp .env.example .env
sudo chown -R www-data:www-data /var/www/Gamecontrol3

# Edit .env file now!
# sudo nano .env
# Set DB_PASSWORD, APP_URL, etc.

# Part 4: Install and Build
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data yarn install
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data yarn run build
sudo chmod -R 755 storage bootstrap/cache

# Part 5: Create Admin User
sudo -u www-data php artisan p:user:make
```

---

## ⚡ Super Quick Deploy (Copy-Paste)

**After installing software and setting up database:**

```bash
cd /var/www && \
sudo git clone https://github.com/anthev-stack/Gamecontrol3.git && \
cd Gamecontrol3 && \
sudo cp .env.example .env && \
sudo chown -R www-data:www-data . && \
echo "Now edit .env file: sudo nano .env" && \
echo "Then run: sudo -u www-data composer install --no-dev --optimize-autoloader && sudo -u www-data yarn install && sudo -u www-data php artisan key:generate --force && sudo -u www-data php artisan migrate --force && sudo -u www-data yarn run build"
```

---

## 🔧 Maintenance Commands

### Update from GitHub
```bash
cd /var/www/Gamecontrol3
sudo -u www-data git pull origin main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data yarn install && sudo -u www-data yarn run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:clear && sudo -u www-data php artisan cache:clear
sudo systemctl restart php8.2-fpm gamecontrol-worker
```

### Grant Credits to User
```bash
sudo -u www-data php artisan tinker
```
```php
$user = User::where('email', 'user@email.com')->first();
$user->credits += 25.00;
$user->save();
CreditTransaction::create([
    'user_id' => $user->id,
    'amount' => 25.00,
    'balance_after' => $user->credits,
    'type' => 'admin_grant',
    'reason' => 'giveaway',
    'description' => 'Promotional credits',
]);
exit
```

### View Logs
```bash
# Laravel logs
sudo tail -f /var/www/Gamecontrol3/storage/logs/laravel.log

# Nginx error log
sudo tail -f /var/log/nginx/error.log

# Queue worker log
sudo tail -f /var/www/Gamecontrol3/storage/logs/worker.log
```

### Restart Services
```bash
sudo systemctl restart nginx php8.2-fpm mysql redis-server gamecontrol-worker
```

### Check Service Status
```bash
sudo systemctl status nginx php8.2-fpm mysql redis-server gamecontrol-worker
```

### Clear All Caches
```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear
```

### Fix Permissions
```bash
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 /var/www/Gamecontrol3/storage
sudo chmod -R 755 /var/www/Gamecontrol3/bootstrap/cache
```

---

## 🗄️ Database Commands

### Backup Database
```bash
mysqldump -u gamecontrol_user -p gamecontrol > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Database
```bash
mysql -u gamecontrol_user -p gamecontrol < backup_20240115_120000.sql
```

### Reset Database (⚠️ DANGER)
```bash
sudo -u www-data php artisan migrate:fresh --force
```

---

## 📊 Useful Queries

### Count users with credits
```bash
sudo mysql -u gamecontrol_user -p gamecontrol -e "SELECT COUNT(*) FROM users WHERE credits > 0;"
```

### List top users by credits
```bash
sudo mysql -u gamecontrol_user -p gamecontrol -e "SELECT username, email, credits FROM users WHERE credits > 0 ORDER BY credits DESC LIMIT 10;"
```

### Count active plans
```bash
sudo mysql -u gamecontrol_user -p gamecontrol -e "SELECT COUNT(*) FROM hosting_plans WHERE is_active = 1;"
```

### Recent orders
```bash
sudo mysql -u gamecontrol_user -p gamecontrol -e "SELECT id, order_number, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10;"
```

---

## 🔐 Security Commands

### Update all packages
```bash
sudo apt update && sudo apt upgrade -y
```

### Enable firewall
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status
```

### Renew SSL certificate
```bash
sudo certbot renew
```

### Change database password
```bash
sudo mysql
```
```sql
ALTER USER 'gamecontrol_user'@'localhost' IDENTIFIED BY 'NewStrongPassword123!';
FLUSH PRIVILEGES;
EXIT;
```
Then update in `/var/www/Gamecontrol3/.env`

---

## 🎯 Testing Commands

### Test checkout flow
```bash
cd /var/www/Gamecontrol3
sudo -u www-data php artisan tinker
```
```php
// Create test user with credits
$user = User::factory()->create(['credits' => 100.00]);
echo "Test user: " . $user->email . "\nPassword: password\n";
exit
```

### Create test hosting plans
```bash
sudo -u www-data php artisan tinker
```
```php
$nest = Nest::first();
$egg = Egg::first();
HostingPlan::create([
    'name' => 'Test Plan',
    'description' => 'Test plan',
    'slug' => 'test-plan',
    'memory' => 2048,
    'swap' => 512,
    'disk' => 5120,
    'io' => 500,
    'cpu' => 100,
    'nest_id' => $nest->id,
    'egg_id' => $egg->id,
    'database_limit' => 1,
    'allocation_limit' => 1,
    'backup_limit' => 1,
    'price' => 5.99,
    'billing_period' => 'monthly',
    'is_active' => true,
]);
exit
```

---

## 📝 One-Liner Install Script

Save this as `install.sh` on your VM:

```bash
#!/bin/bash
echo "Installing Gamecontrol3..."
sudo apt update && sudo apt upgrade -y
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.2 php8.2-{cli,gd,mysql,pdo,mbstring,tokenizer,bcmath,xml,fpm,curl,zip} nginx mysql-server redis-server certbot python3-certbot-nginx
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - && sudo apt install -y nodejs
sudo npm install -g yarn
echo "Software installed! Now setup database and clone repo."
```

Then run:
```bash
chmod +x install.sh
./install.sh
```

---

## 🆘 Emergency Commands

### Site down - restart everything
```bash
sudo systemctl restart nginx php8.2-fpm mysql redis-server gamecontrol-worker
```

### High CPU usage
```bash
# Check what's using CPU
top
htop

# Restart queue worker
sudo systemctl restart gamecontrol-worker
```

### Disk full
```bash
# Check disk usage
df -h

# Clean old logs
sudo find /var/www/Gamecontrol3/storage/logs -name "*.log" -mtime +7 -delete
sudo journalctl --vacuum-time=7d
```

### Database locked
```bash
sudo systemctl restart mysql
```

---

## 📚 Documentation Quick Links

- Full Deployment: `DEPLOYMENT_GUIDE.md`
- Credits & Split Billing: `CREDITS_AND_SPLIT_BILLING.md`
- Push to GitHub: `PUSH_TO_GITHUB.md`
- VM Quick Start: `VM_DEPLOYMENT_QUICK_START.md`
- Feature Summary: `COMPLETE_FEATURES_SUMMARY.md`

---

**Keep this file handy for quick reference!** 📖

