# Secure Production Deployment Guide

This guide explains how to securely deploy the SmartThings API to a production web server by moving sensitive files outside the web root.

## Recommended Directory Structure

```
/var/www/                           # Web server root
├── html/                          # Public web root
│   ├── smartthings/              # Your public API endpoints
│   │   ├── json.php             # Main API endpoint
│   │   ├── set.php              # Device control endpoint
│   │   └── index.html           # Optional API documentation
│   └── .htaccess                # Web server security rules
├── config/                       # Configuration files (OUTSIDE web root)
│   ├── bearer.ini               # API credentials
│   └── userinfo.ini            # User configuration
├── tokens/                       # OAuth tokens (OUTSIDE web root)
├── vendor/                       # Composer dependencies (OUTSIDE web root)
└── src/                         # Source code (OUTSIDE web root)
```

## Deployment Steps

### 1. Set Up Directory Structure

```bash
# On your web server
sudo mkdir -p /var/www/html/smartthings
sudo mkdir -p /var/www/config
sudo mkdir -p /var/www/tokens
sudo chown -R www-data:www-data /var/www/
sudo chmod -R 755 /var/www/
sudo chmod 700 /var/www/config
sudo chmod 700 /var/www/tokens
```

### 2. Copy Files to Secure Locations

```bash
# Copy public API endpoints to web root
cp tests/json.php /var/www/html/smartthings/
cp tests/set.php /var/www/html/smartthings/

# Move sensitive files outside web root
cp bearer.ini /var/www/config/
cp userinfo.ini /var/www/config/
cp -r vendor/ /var/www/
cp -r src/ /var/www/

# Create tokens directory with proper permissions
sudo chown www-data:www-data /var/www/tokens
sudo chmod 700 /var/www/tokens
```

### 3. Update File Permissions

```bash
sudo chown -R www-data:www-data /var/www/
sudo chmod 644 /var/www/html/smartthings/*.php
sudo chmod 600 /var/www/config/*.ini
sudo chmod -R 755 /var/www/vendor/
sudo chmod -R 755 /var/www/src/
```

### 4. Configure Environment Variables (Recommended)

Instead of using .ini files, use environment variables for maximum security:

```bash
# In your web server configuration or systemd environment file
export SMARTTHINGS_API_TOKEN="your_api_token_here"
export SMARTTHINGS_CLIENT_ID="your_client_id_here"
export SMARTTHINGS_CLIENT_SECRET="your_client_secret_here"
export SMARTTHINGS_REDIRECT_URI="https://yourdomain.com/smartthings/json.php"
export SMARTTHINGS_TOKEN_DIR="/var/www/tokens"
```

### 5. Web Server Security

Add this to your Apache/Nginx configuration:

#### Apache (.htaccess in web root)
```apache
# Deny access to sensitive directories if they accidentally end up in web root
<Directory "*/config">
    Require all denied
</Directory>
<Directory "*/tokens">
    Require all denied
</Directory>
<Directory "*/vendor">
    Require all denied
</Directory>
<Directory "*/src">
    Require all denied
</Directory>

# Deny access to .ini files
<Files "*.ini">
    Require all denied
</Files>
```

#### Nginx
```nginx
location ~ /(config|tokens|vendor|src)/ {
    deny all;
    return 404;
}

location ~ \.ini$ {
    deny all;
    return 404;
}
```

## Security Benefits

1. **Configuration files outside web root**: Prevents direct web access to credentials
2. **Token storage outside web root**: OAuth tokens cannot be accessed via HTTP
3. **Source code outside web root**: Protects your application logic
4. **Proper file permissions**: Limits access to web server user only
5. **Environment variables**: Most secure way to handle credentials

## Testing Deployment

1. Verify API endpoints work: `https://yourdomain.com/smartthings/json.php`
2. Verify config files are not accessible: `https://yourdomain.com/config/bearer.ini` should return 404
3. Verify tokens directory is not accessible: `https://yourdomain.com/tokens/` should return 404
4. Test OAuth flow and device control functionality

## Maintenance

- Regularly update dependencies: `composer update` in `/var/www/`
- Monitor log files for security attempts
- Keep credentials in environment variables when possible
- Backup token files regularly (they're in `/var/www/tokens/`)

# Move application files  
mv vendor /var/www/smartthings-app/
mv src /var/www/smartthings-app/
mv composer.json /var/www/smartthings-app/
mv composer.lock /var/www/smartthings-app/      # if exists

# Keep only API endpoints in web root
mkdir -p /var/www/html/smartthings/api
mv tests/json.php /var/www/html/smartthings/api/
mv tests/set.php /var/www/html/smartthings/api/
```

### 3. Update PHP File Paths

Update the paths in your PHP files to point to the new secure locations:

```php
// New secure paths (update in json.php and set.php)
$config_file = '/var/www/smartthings-config/oauth_tokens.ini';
$tokens_dir = '/var/www/smartthings-config/user_tokens';
$autoload_file = '/var/www/smartthings-app/vendor/autoload.php';

// Include SmartThings source files
require_once '/var/www/smartthings-app/src/smartThings/smartThingsAPI.php';
require_once '/var/www/smartthings-app/src/smartThings/device_wrapper.php';
require_once '/var/www/smartthings-app/src/smartThings/locations_rooms.php';
```

## Benefits of This Structure

✅ **Maximum Security** - No sensitive files in web-accessible directories  
✅ **No .htaccess needed** - Files physically unreachable via web  
✅ **Clean separation** - Configuration, application, and web files separated  
✅ **Backup safety** - Sensitive files excluded from web backups  
✅ **Environment isolation** - Easy to have different configs per environment  
✅ **Permission control** - Fine-grained file system permissions  

## Local Development Alternative

For local development, you can use a simpler structure:

```
your-project/
├── config/                     ← Local config directory
│   ├── oauth_tokens.ini
│   └── user_tokens/
├── vendor/                     ← Composer dependencies
├── src/                        ← SmartThings library
└── public/                     ← Local web root
    ├── json.php
    └── set.php
```

## Environment Variables Alternative

Instead of files, you can also use environment variables:
```bash
export SMARTTHINGS_CLIENT_ID="your_client_id"
export SMARTTHINGS_CLIENT_SECRET="your_client_secret"  
export SMARTTHINGS_REDIRECT_URI="https://yourserver.com/smartthings/api/json.php"
export SMARTTHINGS_TOKENS_DIR="/var/www/smartthings-config/user_tokens"
```

This approach combines the security of external configuration with the flexibility of environment-specific settings.
