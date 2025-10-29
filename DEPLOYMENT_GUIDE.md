# Deployment Guide for Gamecontrol3 Hosting Marketplace

This guide will help you deploy your hosting marketplace to a VM and push it to GitHub.

## Prerequisites

- A VM with Ubuntu 20.04/22.04 or similar
- Root or sudo access
- Domain name pointed to your VM
- Basic knowledge of Linux and command line

## Step 1: Prepare Your VM

### Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-{cli,gd,mysql,pdo,mbstring,tokenizer,bcmath,xml,fpm,curl,zip}

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and Yarn
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm install -g yarn

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Redis (optional but recommended)
sudo apt install -y redis-server
```

## Step 2: Set Up GitHub Repository

### Initialize Git Locally (if not already done)

```bash
cd /path/to/Gamecontrol3
git init
git add .
git commit -m "Initial commit: Pterodactyl with hosting marketplace"
```

### Create Repository on GitHub

1. Go to https://github.com/anthev-stack
2. Create new repository named `Gamecontrol3`
3. **DO NOT** initialize with README (you already have one)

### Push to GitHub

```bash
# Add remote
git remote add origin https://github.com/anthev-stack/Gamecontrol3.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 3: Clone to VM

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/anthev-stack/Gamecontrol3.git
cd Gamecontrol3

# Set permissions
sudo chown -R www-data:www-data /var/www/Gamecontrol3
```

## Step 4: Configure Application

### Environment Configuration

```bash
# Copy environment file
sudo cp .env.example .env

# Generate app key
sudo php artisan key:generate --force

# Edit .env file
sudo nano .env
```

Configure these important values in `.env`:

```env
APP_NAME="Your Hosting Company"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panel
DB_USERNAME=pterodactyl
DB_PASSWORD=your_secure_password

MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

### Database Setup

```bash
# Create database
sudo mysql -u root -p

# In MySQL:
CREATE DATABASE panel;
CREATE USER 'pterodactyl'@'127.0.0.1' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON panel.* TO 'pterodactyl'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

### Run Migrations

```bash
sudo php artisan migrate --force --seed
```

### Create Admin User

```bash
sudo php artisan p:user:make
```

Follow prompts to create your admin account.

## Step 5: Install Dependencies

```bash
# Install PHP dependencies
sudo composer install --no-dev --optimize-autoloader

# Install Node dependencies
yarn install

# Build assets
yarn run build

# Set permissions
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 storage bootstrap/cache
```

## Step 6: Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/pterodactyl
```

Add this configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    root /var/www/Gamecontrol3/public;
    index index.php;

    # SSL Configuration (get certificates with certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_session_cache shared:SSL:10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256";
    ssl_prefer_server_ciphers on;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/pterodactyl /etc/nginx/sites-enabled/pterodactyl
sudo nginx -t
sudo systemctl restart nginx
```

## Step 7: SSL Certificate

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

## Step 8: Set Up Cron Jobs

```bash
sudo crontab -e -u www-data
```

Add:

```cron
* * * * * php /var/www/Gamecontrol3/artisan schedule:run >> /dev/null 2>&1
```

## Step 9: Set Up Queue Worker

Create systemd service:

```bash
sudo nano /etc/systemd/system/pteroq.service
```

Add:

```ini
[Unit]
Description=Pterodactyl Queue Worker
After=redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/Gamecontrol3/artisan queue:work --queue=high,standard,low --sleep=3 --tries=3
StartLimitInterval=180
StartLimitBurst=30
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable --now pteroq.service
sudo systemctl status pteroq.service
```

## Step 10: Create Initial Hosting Plans

Connect to your database and add plans, or use the seeder. Example:

```bash
sudo php artisan tinker
```

Then in tinker:

```php
$nest = \Pterodactyl\Models\Nest::first();
$egg = \Pterodactyl\Models\Egg::first();

\Pterodactyl\Models\HostingPlan::create([
    'name' => 'Starter Plan',
    'description' => 'Perfect for beginners',
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
    'price' => 5.99,
    'billing_period' => 'monthly',
    'is_active' => true,
    'is_featured' => false,
]);
```

## Step 11: Testing

1. Visit your domain
2. Browse hosting plans
3. Add to cart
4. Test checkout process
5. Verify server creation
6. Check billing dashboard

## Maintenance Commands

```bash
# Update from GitHub
cd /var/www/Gamecontrol3
sudo git pull origin main
sudo composer install --no-dev --optimize-autoloader
yarn install
yarn run build
sudo php artisan migrate --force
sudo php artisan view:clear
sudo php artisan config:clear
sudo chown -R www-data:www-data /var/www/Gamecontrol3

# View logs
sudo tail -f storage/logs/laravel.log

# Restart services
sudo systemctl restart nginx php8.2-fpm pteroq
```

## Security Checklist

- [ ] Change default database passwords
- [ ] Enable firewall (ufw)
- [ ] Keep system updated
- [ ] Regular backups
- [ ] Monitor logs
- [ ] Disable debug mode in production
- [ ] Use strong passwords
- [ ] Enable 2FA for admin accounts

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/Gamecontrol3
sudo chmod -R 755 storage bootstrap/cache
```

### Database Connection Issues
- Check MySQL is running: `sudo systemctl status mysql`
- Verify credentials in `.env`
- Check firewall rules

### 500 Errors
- Check logs: `storage/logs/laravel.log`
- Clear cache: `php artisan config:clear`
- Rebuild cache: `php artisan config:cache`

## Support

- Pterodactyl Discord: https://discord.gg/pterodactyl
- GitHub Issues: https://github.com/anthev-stack/Gamecontrol3/issues

---

**Congratulations!** Your hosting marketplace is now live! 🎉

