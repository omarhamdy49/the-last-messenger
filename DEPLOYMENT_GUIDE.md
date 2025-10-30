# Deployment Guide

**The Last Messenger** - Production Deployment Documentation
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 📚 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Environment Configuration](#environment-configuration)
4. [Web Server Setup](#web-server-setup)
5. [Database Setup](#database-setup)
6. [Caching](#caching)
7. [Queue Configuration](#queue-configuration)
8. [Monitoring](#monitoring)
9. [Backup & Recovery](#backup--recovery)
10. [Scaling](#scaling)

---

## ⚙️ Prerequisites

### Server Requirements

**Minimum Requirements**:
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher
- **Redis**: 6.0 or higher
- **Composer**: 2.5 or higher
- **Node.js**: 18.x or higher (for asset compilation)
- **Memory**: 2GB RAM minimum, 4GB recommended
- **Storage**: 20GB minimum, 50GB recommended

**Recommended Production Specs**:
- **CPU**: 4 cores or more
- **RAM**: 8GB or more
- **Storage**: SSD with 100GB+
- **Network**: 100Mbps+ bandwidth

### Required PHP Extensions

```bash
# Check installed extensions
php -m

# Required extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- Filter
- GD (for image manipulation)
- Hash
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- PDO_MySQL
- Session
- Tokenizer
- XML
- XMLReader
- XMLWriter
- Zip
```

### Installing PHP Extensions (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-redis php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl
```

### Installing MySQL

```bash
# Ubuntu/Debian
sudo apt install mysql-server
sudo mysql_secure_installation

# Start MySQL
sudo systemctl start mysql
sudo systemctl enable mysql
```

### Installing Redis

```bash
# Ubuntu/Debian
sudo apt install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Verify Redis is running
redis-cli ping
# Should return: PONG
```

### Installing Composer

```bash
# Download installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Install globally
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Verify installation
composer --version
```

---

## 📦 Installation Steps

### Step 1: Clone Repository

```bash
# Create project directory
sudo mkdir -p /var/www/the-last-messenger
cd /var/www/the-last-messenger

# Clone repository
git clone https://github.com/your-org/the-last-messenger-be.git .

# Or upload files via SFTP/SCP
```

### Step 2: Set Permissions

```bash
# Set ownership (replace 'www-data' with your web server user)
sudo chown -R www-data:www-data /var/www/the-last-messenger

# Set directory permissions
sudo find /var/www/the-last-messenger -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/the-last-messenger -type f -exec chmod 644 {} \;

# Make storage and cache writable
sudo chmod -R 775 /var/www/the-last-messenger/storage
sudo chmod -R 775 /var/www/the-last-messenger/bootstrap/cache
```

### Step 3: Install PHP Dependencies

```bash
cd /var/www/the-last-messenger

# Install Composer dependencies (production mode)
composer install --optimize-autoloader --no-dev

# For development/staging, use:
# composer install
```

### Step 4: Install Node Dependencies (Optional)

```bash
# Install Node.js dependencies
npm install

# Build assets for production
npm run build

# Or for development:
# npm run dev
```

### Step 5: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings
nano .env
```

### Step 6: Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Seed database with initial data
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AdminUserSeeder

# Or seed all:
# php artisan db:seed --force
```

### Step 7: Cache Configuration

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Step 8: Create Symbolic Link for Storage

```bash
# Link storage to public directory
php artisan storage:link
```

---

## 🔧 Environment Configuration

### Essential .env Variables

```bash
# Application
APP_NAME="The Last Messenger"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=last_messenger_prod
DB_USERNAME=last_messenger_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=lm_prod_

# Queue
QUEUE_CONNECTION=redis
QUEUE_REDIS_DB=1

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# Mail (Configure based on your provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (Optional - for file storage)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_ENDPOINT=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
SESSION_DOMAIN=.yourdomain.com

# Admin Portal
ADMIN_URL_PREFIX=su/portal/v1/admin
```

### Security Settings

**Important Security Configurations**:

```bash
# .env
APP_DEBUG=false  # MUST be false in production
APP_ENV=production

# Disable error display
display_errors = Off  # in php.ini
log_errors = On
error_log = /var/log/php/error.log
```

**Generate Strong Secrets**:
```bash
# Generate random string for passwords
openssl rand -base64 32

# Generate APP_KEY
php artisan key:generate
```

---

## 🌐 Web Server Setup

### Nginx Configuration

**Create site configuration**:
```bash
sudo nano /etc/nginx/sites-available/the-last-messenger
```

**Configuration**:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/the-last-messenger/public;
    index index.php index.html;

    # SSL Certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Client Max Body Size (for file uploads)
    client_max_body_size 100M;

    # Logs
    access_log /var/log/nginx/the-last-messenger-access.log;
    error_log /var/log/nginx/the-last-messenger-error.log;

    # Laravel specific
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Enable site and restart Nginx**:
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/the-last-messenger /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### Apache Configuration

**Create virtual host**:
```bash
sudo nano /etc/apache2/sites-available/the-last-messenger.conf
```

**Configuration**:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/the-last-messenger/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    # Directory Configuration
    <Directory /var/www/the-last-messenger/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/the-last-messenger-error.log
    CustomLog ${APACHE_LOG_DIR}/the-last-messenger-access.log combined
</VirtualHost>
```

**Enable modules and site**:
```bash
# Enable required modules
sudo a2enmod rewrite ssl headers

# Enable site
sudo a2ensite the-last-messenger

# Disable default site
sudo a2dissite 000-default

# Restart Apache
sudo systemctl restart apache2
```

### SSL/HTTPS Setup with Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# For Nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# For Apache
# sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run

# Add cron job for auto-renewal
sudo crontab -e
# Add this line:
0 3 * * * /usr/bin/certbot renew --quiet
```

---

## 🗄️ Database Setup

### Create Database and User

```sql
-- Connect to MySQL
mysql -u root -p

-- Create database
CREATE DATABASE last_messenger_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'last_messenger_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- Grant privileges
GRANT ALL PRIVILEGES ON last_messenger_prod.* TO 'last_messenger_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### Database Configuration

**MySQL Configuration** (`/etc/mysql/mysql.conf.d/mysqld.cnf`):
```ini
[mysqld]
# Performance settings
max_connections = 200
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_file_per_table = 1

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Logging
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

**Restart MySQL**:
```bash
sudo systemctl restart mysql
```

### Backup Database

**Create backup script**:
```bash
sudo nano /usr/local/bin/backup-database.sh
```

```bash
#!/bin/bash

# Configuration
DB_NAME="last_messenger_prod"
DB_USER="last_messenger_user"
DB_PASS="YOUR_PASSWORD"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

# Create backup directory if not exists
mkdir -p $BACKUP_DIR

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_FILE

# Delete backups older than 7 days
find $BACKUP_DIR -name "*.sql.gz" -type f -mtime +7 -delete

echo "Backup completed: $BACKUP_FILE"
```

**Make executable and schedule**:
```bash
sudo chmod +x /usr/local/bin/backup-database.sh

# Add to cron (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-database.sh
```

---

## ⚡ Caching

### Redis Configuration

**Redis config** (`/etc/redis/redis.conf`):
```ini
# Bind to localhost
bind 127.0.0.1

# Set password
requirepass YOUR_STRONG_PASSWORD

# Max memory
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Log level
loglevel notice
logfile /var/log/redis/redis-server.log
```

**Restart Redis**:
```bash
sudo systemctl restart redis-server
```

**Update `.env`**:
```bash
REDIS_PASSWORD=YOUR_STRONG_PASSWORD
```

### Laravel Cache Configuration

**Cache drivers**:
```bash
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Clear all caches**:
```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear compiled classes
php artisan clear-compiled
```

**Rebuild caches for production**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔄 Queue Configuration

### Setting Up Queue Worker

**Create Supervisor configuration**:
```bash
sudo nano /etc/supervisor/conf.d/the-last-messenger-worker.conf
```

**Configuration**:
```ini
[program:the-last-messenger-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/the-last-messenger/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/the-last-messenger-worker.log
stopwaitsecs=3600
```

**Start queue worker**:
```bash
# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start worker
sudo supervisorctl start the-last-messenger-worker:*

# Check status
sudo supervisorctl status

# Restart worker (after code updates)
sudo supervisorctl restart the-last-messenger-worker:*
```

### Queue Monitoring

**Install Horizon** (optional, for better queue management):
```bash
composer require laravel/horizon

# Publish config
php artisan horizon:install

# Update Supervisor config to use Horizon
# command=php /var/www/the-last-messenger/artisan horizon
```

---

## 📊 Monitoring

### Application Monitoring with Sentry

**Install Sentry SDK**:
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_SENTRY_DSN
```

**Configure `.env`**:
```bash
SENTRY_LARAVEL_DSN=https://YOUR_KEY@sentry.io/YOUR_PROJECT
SENTRY_TRACES_SAMPLE_RATE=0.2
```

### Log Monitoring

**Configure log rotation** (`/etc/logrotate.d/laravel`):
```
/var/www/the-last-messenger/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/supervisorctl restart the-last-messenger-worker:* > /dev/null 2>&1 || true
    endscript
}
```

### Server Monitoring

**Install monitoring tools**:
```bash
# Install htop for process monitoring
sudo apt install htop

# Install iotop for I/O monitoring
sudo apt install iotop

# Install nethogs for network monitoring
sudo apt install nethogs
```

**Monitor services**:
```bash
# Check service status
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status php8.2-fpm

# View logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/mysql/error.log
sudo tail -f /var/www/the-last-messenger/storage/logs/laravel.log
```

---

## 💾 Backup & Recovery

### Automated Backup Script

**Complete backup script**:
```bash
sudo nano /usr/local/bin/backup-complete.sh
```

```bash
#!/bin/bash

# Configuration
APP_DIR="/var/www/the-last-messenger"
BACKUP_DIR="/var/backups/the-last-messenger"
DATE=$(date +"%Y%m%d_%H%M%S")
DB_NAME="last_messenger_prod"
DB_USER="last_messenger_user"
DB_PASS="YOUR_PASSWORD"

# Create backup directory
mkdir -p $BACKUP_DIR/$DATE

# Backup database
echo "Backing up database..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/$DATE/database.sql.gz

# Backup storage files
echo "Backing up storage files..."
tar -czf $BACKUP_DIR/$DATE/storage.tar.gz -C $APP_DIR storage/app

# Backup .env file
echo "Backing up environment file..."
cp $APP_DIR/.env $BACKUP_DIR/$DATE/.env

# Create archive
echo "Creating final archive..."
tar -czf $BACKUP_DIR/backup_$DATE.tar.gz -C $BACKUP_DIR $DATE

# Cleanup
rm -rf $BACKUP_DIR/$DATE

# Delete backups older than 30 days
find $BACKUP_DIR -name "backup_*.tar.gz" -type f -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/backup_$DATE.tar.gz"
```

**Make executable and schedule**:
```bash
sudo chmod +x /usr/local/bin/backup-complete.sh

# Daily backup at 3 AM
sudo crontab -e
0 3 * * * /usr/local/bin/backup-complete.sh >> /var/log/backup.log 2>&1
```

### Restore from Backup

```bash
#!/bin/bash

BACKUP_FILE="/var/backups/the-last-messenger/backup_YYYYMMDD_HHMMSS.tar.gz"
RESTORE_DIR="/tmp/restore"
APP_DIR="/var/www/the-last-messenger"

# Extract backup
mkdir -p $RESTORE_DIR
tar -xzf $BACKUP_FILE -C $RESTORE_DIR

# Restore database
gunzip < $RESTORE_DIR/*/database.sql.gz | mysql -u last_messenger_user -p last_messenger_prod

# Restore storage
tar -xzf $RESTORE_DIR/*/storage.tar.gz -C $APP_DIR

# Restore .env
cp $RESTORE_DIR/*/.env $APP_DIR/.env

# Set permissions
chown -R www-data:www-data $APP_DIR/storage

echo "Restore completed"
```

### Off-site Backup (AWS S3)

```bash
# Install AWS CLI
sudo apt install awscli

# Configure AWS
aws configure

# Backup to S3
aws s3 cp /var/backups/the-last-messenger/backup_$DATE.tar.gz s3://your-bucket/backups/
```

---

## 📈 Scaling

### Horizontal Scaling

**Load Balancer Configuration (Nginx)**:
```nginx
upstream backend {
    least_conn;
    server 192.168.1.10:80 weight=1 max_fails=3 fail_timeout=30s;
    server 192.168.1.11:80 weight=1 max_fails=3 fail_timeout=30s;
    server 192.168.1.12:80 weight=1 max_fails=3 fail_timeout=30s;
}

server {
    listen 80;
    server_name yourdomain.com;

    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Database Scaling

**Read Replicas**:
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            '192.168.1.20',  // Read replica 1
            '192.168.1.21',  // Read replica 2
        ],
    ],
    'write' => [
        'host' => [
            '192.168.1.10',  // Master
        ],
    ],
    // ... other config
],
```

### CDN Setup

**CloudFlare Configuration**:
1. Add domain to CloudFlare
2. Update DNS records
3. Enable caching for static assets
4. Enable security features
5. Update `.env`:
   ```bash
   APP_URL=https://yourdomain.com
   ASSET_URL=https://cdn.yourdomain.com
   ```

---

## 🔒 Security Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Set strong `APP_KEY`
- [ ] Configure firewall (UFW)
- [ ] Enable HTTPS/SSL
- [ ] Set proper file permissions
- [ ] Disable directory listing
- [ ] Configure CSP headers
- [ ] Enable rate limiting
- [ ] Use strong database passwords
- [ ] Set Redis password
- [ ] Configure fail2ban
- [ ] Keep software updated
- [ ] Enable audit logging
- [ ] Backup regularly
- [ ] Monitor logs
- [ ] Use security scanners

---

## 🎉 Post-Deployment

### Verify Installation

```bash
# Check PHP version
php -v

# Check Laravel installation
php artisan --version

# Check database connection
php artisan migrate:status

# Check queue worker
sudo supervisorctl status

# Check web server
curl -I https://yourdomain.com

# Check Redis
redis-cli ping
```

### Performance Testing

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test performance
ab -n 1000 -c 10 https://yourdomain.com/
```

### Final Steps

1. ✅ Change default admin passwords
2. ✅ Test all major functionality
3. ✅ Verify email sending
4. ✅ Test file uploads
5. ✅ Verify search functionality
6. ✅ Test API endpoints
7. ✅ Monitor error logs
8. ✅ Setup monitoring alerts
9. ✅ Document server details
10. ✅ Train administrators

---

## 📞 Support

For deployment issues:
- 📧 Email: devops@thelastmessenger.com
- 📖 Documentation: https://docs.thelastmessenger.com
- 🐛 Issues: https://github.com/your-org/the-last-messenger-be/issues

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger DevOps Team
