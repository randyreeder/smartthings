# Secure Production Deployment Guide

This guide explains how to securely deploy the SmartThings API to a production web server by moving sensitive files outside the web root.

## Recommended Directory Structure

```
/home1/rreeder/                     # Your home directory (or ~/  )
├── smartthings_config/            # Configuration files (OUTSIDE web root)
│   ├── oauth_tokens.ini          # OAuth app credentials
│   └── tokens/                   # OAuth user token storage
├── git/smartthings/              # Git repository (OUTSIDE web root)
│   ├── vendor/                   # Composer dependencies
│   └── src/                      # SmartThings library source
└── public_html/                  # Web root (or www/ or htdocs/)
    └── smartthings/
        ├── json.php             # Main API endpoint
        ├── set.php              # Device control endpoint
        └── index.html           # Optional API documentation
```

**Note:** This structure keeps your git repository and config files separate and secure.

## Deployment Steps

### 1. Set Up Directory Structure

The deployment script will automatically detect your home directory and create the appropriate structure. You can customize paths using environment variables:

```bash
# Optional: Customize paths (otherwise defaults will be used)
export SMARTTHINGS_WEB_ROOT="public_html/smartthings"
export SMARTTHINGS_CONFIG_DIR="$HOME/smartthings_config"
export SMARTTHINGS_TOKEN_DIR="$HOME/smartthings_config/tokens"
export SMARTTHINGS_APP_DIR="$HOME/smartthings_app"

# Run the deployment script
./deploy-secure.sh
```

**Default paths used:**
- Web root: `~/public_html/smartthings/` (or `/home1/username/public_html/smartthings/`)
- Config: `~/smartthings_config/`
- Tokens: `~/smartthings_config/tokens/`
- Git repo: `~/git/smartthings/` (vendor and src directories)

### 2. Run Deployment Script

```bash
# Run the secure deployment script
./deploy-secure.sh
```

This script will:
- Create the secure directory structure
- Copy API files to your web root
- Move sensitive files outside the web root
- Set proper file permissions
- Display the final directory structure

### 3. Configure Environment Variables (Optional but Recommended)

For maximum security, use environment variables instead of config files:

```bash
# Add to your shell profile (~/.bashrc, ~/.zshrc, etc.)
export SMARTTHINGS_CLIENT_ID="your_client_id"
export SMARTTHINGS_CLIENT_SECRET="your_client_secret"
export SMARTTHINGS_REDIRECT_URI="https://yourdomain.com/smartthings/json.php"
export SMARTTHINGS_CONFIG_FILE="$HOME/smartthings_config/oauth_tokens.ini"
export SMARTTHINGS_TOKEN_DIR="$HOME/smartthings_config/tokens"
export SMARTTHINGS_VENDOR_PATH="$HOME/git/smartthings/vendor/autoload.php"
export SMARTTHINGS_SRC_PATH="$HOME/git/smartthings/src/smartThings"
```

## Testing Deployment

1. **Verify API endpoints work**:
   ```bash
   curl "https://yourdomain.com/smartthings/json.php?token=YOUR_TOKEN"
   ```

2. **Test environment variable setup**:
   ```bash
   php tests/test-environment.php
   ```

3. **Verify sensitive files are not web accessible**:
   - Try accessing config files directly (should fail)
   - Try accessing token files directly (should fail)

## Maintenance

- **Update dependencies**: Run `composer update` in your app directory
- **Backup token files**: Regular backups of your tokens directory
- **Monitor logs**: Check for unauthorized access attempts
- **Update paths**: Modify environment variables if you change directory structure

## Troubleshooting

### Common Issues

1. **"Config file not found"**: Check the path in the deployment script output
2. **"Permission denied"**: Ensure proper file permissions (600 for .ini files, 700 for token directories)
3. **"Class not found"**: Verify vendor/autoload.php path is correct
4. **"Invalid API key"**: Complete OAuth setup flow first

### Path Customization

If you need different paths, you can customize them:

```bash
# Before running deploy-secure.sh
export SMARTTHINGS_WEB_ROOT="www/api"
export SMARTTHINGS_CONFIG_DIR="/home1/rreeder/config"
export SMARTTHINGS_APP_DIR="/home1/rreeder/smartthings"
```

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
