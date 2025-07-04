# Manual Deployment Guide

This guide explains how to manually deploy the SmartThings API to production without using the automated `deploy-secure.sh` script. This is useful for custom setups or when you want to understand the deployment process step-by-step.

## Overview

The secure deployment involves moving sensitive files outside the web root while keeping only the API endpoints web-accessible.

## Directory Structure Goal

```
~/smartthings_config/              # Configuration (outside web root)
├── oauth_tokens.ini              # OAuth app credentials
└── tokens/                       # User OAuth tokens (auto-created)

~/git/smartthings/                # Code repository (outside web root)
├── vendor/                       # Composer dependencies
├── src/                         # SmartThings library source
├── tests/json.php              # Source API files
└── tests/set.php               # Source API files

~/public_html/weather/smartthings/ # Public API (in web root)
├── json.php                     # Main API endpoint
└── set.php                      # Device control endpoint
```

## Step-by-Step Manual Deployment

### Step 1: Create Secure Directories

```bash
# Set your paths (customize as needed)
HOME_DIR="${HOME:-/home1/rreeder}"
WEB_ROOT="$HOME_DIR/public_html/weather/smartthings"
CONFIG_DIR="$HOME_DIR/smartthings_config"
TOKENS_DIR="$HOME_DIR/smartthings_config/tokens"

# Create directories
mkdir -p "$WEB_ROOT"
mkdir -p "$CONFIG_DIR"
mkdir -p "$TOKENS_DIR"
```

### Step 2: Set Proper Permissions

```bash
# Web root permissions (readable by web server)
chmod 755 "$WEB_ROOT"

# Config directory permissions (secure, not web accessible)
chmod 700 "$CONFIG_DIR"
chmod 700 "$TOKENS_DIR"
```

### Step 3: Copy API Files to Web Root

```bash
# Navigate to your git repository
cd ~/git/smartthings

# Copy API endpoints to web-accessible directory
cp tests/json.php "$WEB_ROOT/"
cp tests/set.php "$WEB_ROOT/"

# Set appropriate permissions for PHP files
chmod 644 "$WEB_ROOT"/json.php
chmod 644 "$WEB_ROOT"/set.php
```

### Step 4: Set Up Configuration File

```bash
# Copy OAuth configuration to secure location
cp oauth_tokens.ini "$CONFIG_DIR/"

# Set secure permissions on config file
chmod 600 "$CONFIG_DIR/oauth_tokens.ini"
```

### Step 5: Verify File Permissions

```bash
# Check that permissions are correct
ls -la "$WEB_ROOT"          # Should show 644 for .php files
ls -la "$CONFIG_DIR"        # Should show 600 for .ini files
ls -ld "$CONFIG_DIR"        # Should show 700 for directory
ls -ld "$TOKENS_DIR"        # Should show 700 for directory
```

### Step 6: Test the Deployment

```bash
# Test that config file is not web accessible (should fail)
curl https://yourdomain.com/weather/smartthings/oauth_tokens.ini

# Test that API endpoints work (should succeed)
curl https://yourdomain.com/weather/smartthings/json.php?token=YOUR_TOKEN
```

## Alternative Paths and Customization

### Custom Web Root

If your web root is different:
```bash
# For Apache virtual hosts
WEB_ROOT="/var/www/html/api/smartthings"

# For Nginx
WEB_ROOT="/usr/share/nginx/html/smartthings"

# For shared hosting with different structure
WEB_ROOT="$HOME_DIR/www/api"
```

### Custom Config Location

```bash
# Alternative config locations
CONFIG_DIR="$HOME_DIR/private/smartthings"
CONFIG_DIR="/etc/smartthings"
CONFIG_DIR="$HOME_DIR/.config/smartthings"
```

### Environment Variables Setup

Instead of using config files, you can set environment variables:

#### Option A: Shell Profile
```bash
# Add to ~/.bashrc or ~/.zshrc
export SMARTTHINGS_CLIENT_ID="your_client_id"
export SMARTTHINGS_CLIENT_SECRET="your_client_secret"
export SMARTTHINGS_REDIRECT_URI="https://yourdomain.com/weather/smartthings/json.php"
export SMARTTHINGS_CONFIG_FILE="$HOME/smartthings_config/oauth_tokens.ini"
export SMARTTHINGS_TOKEN_DIR="$HOME/smartthings_config/tokens"
```

#### Option B: Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /home1/rreeder/public_html
    
    SetEnv SMARTTHINGS_CLIENT_ID "your_client_id"
    SetEnv SMARTTHINGS_CLIENT_SECRET "your_client_secret"
    SetEnv SMARTTHINGS_REDIRECT_URI "https://yourdomain.com/weather/smartthings/json.php"
    SetEnv SMARTTHINGS_TOKEN_DIR "/home1/rreeder/smartthings_config/tokens"
</VirtualHost>
```

#### Option C: PHP-FPM Pool
```ini
; In your PHP-FPM pool configuration
env[SMARTTHINGS_CLIENT_ID] = "your_client_id"
env[SMARTTHINGS_CLIENT_SECRET] = "your_client_secret"
env[SMARTTHINGS_REDIRECT_URI] = "https://yourdomain.com/weather/smartthings/json.php"
env[SMARTTHINGS_TOKEN_DIR] = "/home1/rreeder/smartthings_config/tokens"
```

## Troubleshooting Manual Deployment

### Permission Issues

```bash
# If you get permission denied errors
sudo chown -R your_username:your_username "$CONFIG_DIR"
sudo chown -R www-data:www-data "$WEB_ROOT"  # or appropriate web user

# If web server can't read files
chmod 755 "$WEB_ROOT"
chmod 644 "$WEB_ROOT"/*.php
```

### Path Issues

```bash
# Test that PHP can find the files
php -r "echo file_exists('$CONFIG_DIR/oauth_tokens.ini') ? 'Config found' : 'Config missing';"
php -r "echo file_exists('$HOME_DIR/git/smartthings/vendor/autoload.php') ? 'Vendor found' : 'Vendor missing';"
```

### Config File Format

Your `oauth_tokens.ini` should look like:
```ini
[oauth_app]
client_id = "your_smartthings_app_client_id"
client_secret = "your_smartthings_app_client_secret"
redirect_uri = "https://yourdomain.com/weather/smartthings/json.php"
```

### Web Server Configuration

#### Apache (.htaccess in document root)
```apache
# Deny access to config directories if they accidentally end up in web root
<DirectoryMatch "smartthings_config">
    Require all denied
</DirectoryMatch>

# Deny access to .ini files
<Files "*.ini">
    Require all denied
</Files>
```

#### Nginx
```nginx
# In your server block
location ~ /smartthings_config {
    deny all;
    return 404;
}

location ~ \.ini$ {
    deny all;
    return 404;
}
```

## Validation Checklist

After manual deployment, verify:

- [ ] ✅ API endpoints accessible: `https://yourdomain.com/weather/smartthings/json.php`
- [ ] ❌ Config files NOT accessible: `https://yourdomain.com/smartthings_config/` (should return 404/403)
- [ ] ✅ PHP can read config: No "config not found" errors in API responses
- [ ] ✅ OAuth setup works: `https://yourdomain.com/weather/smartthings/json.php?setup=1`
- [ ] ✅ Token storage writable: OAuth callback can save tokens
- [ ] ✅ Composer autoload works: No "class not found" errors

## Maintenance

### Updating Code
```bash
cd ~/git/smartthings
git pull
composer update
cp tests/json.php tests/set.php "$WEB_ROOT/"
```

### Backup Important Files
```bash
# Backup config and tokens
tar -czf smartthings-backup-$(date +%Y%m%d).tar.gz \
    "$CONFIG_DIR/oauth_tokens.ini" \
    "$TOKENS_DIR"
```

### Log File Locations
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php-fpm/www-error.log`

## Security Best Practices

1. **Never put config files in web root**
2. **Use environment variables in production**
3. **Set restrictive file permissions (600 for configs, 700 for directories)**
4. **Regularly update dependencies with `composer update`**
5. **Monitor access logs for unauthorized attempts**
6. **Use HTTPS for all API endpoints**
7. **Regularly rotate OAuth credentials**

This manual deployment gives you full control over the process and helps you understand exactly what the automated script does.
