# Environment Variable Setup for SmartThings API

## Method 1: Apache Virtual Host Configuration

Add to your Apache virtual host file (e.g., `/etc/apache2/sites-available/your-site.conf`):

```apache
<VirtualHost *:80>
    ServerName yourserver.com
    DocumentRoot /var/www/html
    
    # SmartThings OAuth Environment Variables
    SetEnv SMARTTHINGS_CLIENT_ID "your_actual_client_id"
    SetEnv SMARTTHINGS_CLIENT_SECRET "your_actual_client_secret"
    SetEnv SMARTTHINGS_REDIRECT_URI "https://yourserver.com/tests/json.php"
    
    # Optional: Set custom paths for token storage
    SetEnv SMARTTHINGS_TOKENS_DIR "/var/www/secure/user_tokens"
</VirtualHost>
```

## Method 2: System Environment Variables (Most Secure)

### For Linux/Ubuntu servers:

1. **Create environment file:**
```bash
sudo nano /etc/environment
```

2. **Add your credentials:**
```bash
SMARTTHINGS_CLIENT_ID="your_actual_client_id"
SMARTTHINGS_CLIENT_SECRET="your_actual_client_secret"
SMARTTHINGS_REDIRECT_URI="https://yourserver.com/tests/json.php"
```

3. **For PHP-FPM, add to pool configuration:**
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Add these lines:
```ini
env[SMARTTHINGS_CLIENT_ID] = $SMARTTHINGS_CLIENT_ID
env[SMARTTHINGS_CLIENT_SECRET] = $SMARTTHINGS_CLIENT_SECRET  
env[SMARTTHINGS_REDIRECT_URI] = $SMARTTHINGS_REDIRECT_URI
```

4. **Restart services:**
```bash
sudo systemctl restart apache2
sudo systemctl restart php8.1-fpm
```

## Method 3: Docker Environment Variables

If using Docker:

```yaml
# docker-compose.yml
version: '3'
services:
  web:
    image: php:apache
    environment:
      - SMARTTHINGS_CLIENT_ID=your_actual_client_id
      - SMARTTHINGS_CLIENT_SECRET=your_actual_client_secret
      - SMARTTHINGS_REDIRECT_URI=https://yourserver.com/tests/json.php
    volumes:
      - ./:/var/www/html
```

## Method 4: Shared Hosting (.htaccess)

For shared hosting providers that support SetEnv:

```apache
# In your .htaccess file
SetEnv SMARTTHINGS_CLIENT_ID "your_client_id"
SetEnv SMARTTHINGS_CLIENT_SECRET "your_client_secret"
SetEnv SMARTTHINGS_REDIRECT_URI "https://yourserver.com/tests/json.php"
```

## Testing Environment Variables

Create a test file to verify your environment variables are working:

```php
<?php
// test-env.php - Remove this file after testing!
echo "<h3>Environment Variables Test</h3>";
echo "CLIENT_ID: " . ($_ENV['SMARTTHINGS_CLIENT_ID'] ? 'SET' : 'NOT SET') . "<br>";
echo "CLIENT_SECRET: " . ($_ENV['SMARTTHINGS_CLIENT_SECRET'] ? 'SET' : 'NOT SET') . "<br>";
echo "REDIRECT_URI: " . ($_ENV['SMARTTHINGS_REDIRECT_URI'] ? 'SET' : 'NOT SET') . "<br>";

// Show values (REMOVE THIS IN PRODUCTION!)
echo "<pre>";
print_r($_ENV);
echo "</pre>";
?>
```

## Security Benefits

✅ **No credentials in code** - Can't accidentally commit secrets  
✅ **No file permissions issues** - No config files to secure  
✅ **Environment-specific** - Different values for dev/staging/production  
✅ **Process isolation** - Each process gets its own environment  
✅ **Backup safety** - Credentials not included in file backups
