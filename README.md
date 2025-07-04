# PHP - Samsung Smart Things API

This library is a simple PHP wrapper for the Smart Things API with support for both Personal Access Tokens and OAuth authentication.

## Features

- **Multiple Authentication Methods**: Personal Access Token (simple) or OAuth (secure)
- **Garmin Watch Integration**: Optimized for Garmin ConnectIQ apps
- **Environment Detection**: Works in both local development and production
- **Device Control**: Control switches, dimmers, and other SmartThings devices

## Supported Devices:
 - TV
 - Ecobee
 - Switches and Dimmers
 - Generic devices

## Installation:

You can install the library using `composer` or by simply downloading this repository and including it in your project.

Installation using `composer`:

    composer require giannisftaras/smartthings

## Production Deployment

For production deployment with maximum security:

1. **Run the secure deployment script**:
   ```bash
   sudo ./deploy-secure.sh
   ```

2. **Use environment variables for credentials** (recommended):
   ```bash
   export SMARTTHINGS_CLIENT_ID="your_client_id"
   export SMARTTHINGS_CLIENT_SECRET="your_client_secret"
   export SMARTTHINGS_REDIRECT_URI="https://yourdomain.com/smartthings/json.php"
   ```

3. **Or use config files outside web root**:
   - Config files: `/var/www/config/bearer.ini`
   - Token storage: `/var/www/tokens/`
   - Source code: `/var/www/src/` and `/var/www/vendor/`

See `SECURE_DEPLOYMENT.md` for complete deployment instructions.

## Authentication Methods

### Method 1: Personal Access Token (Simple)
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
use SmartThings\SmartThingsAPI; 

// Create a Personal Access Token at https://account.smartthings.com/tokens
$userBearerToken = 'YOUR_PERSONAL_ACCESS_TOKEN';
$smartAPI = new SmartThingsAPI($userBearerToken);
$devices = $smartAPI->list_devices(); 

$tv = $devices[0];
$tv->power_on();
$tv->volume(10);
?>
```

### Method 2: OAuth (Secure, API Key Only)
```php
<?php
// For OAuth users, authenticate with just an API key
$api_key = 'YOUR_API_KEY_FROM_OAUTH_SETUP';

// API calls:
// GET /json.php?api_key=YOUR_API_KEY
// GET /set.php?api_key=YOUR_API_KEY&device_id=DEVICE_ID&value=on
?>
```

## OAuth Setup

Users can get OAuth credentials by visiting:
```
GET /json.php?setup=1
```

After completing OAuth authorization, users receive an API key that works for all subsequent API calls.

## Device Control

You can view the TV class in `/src/smartThings/devices/tv.php` for all available functions and commands.

## Locations and Rooms

You can also make basic usage of Locations and Rooms:

```php
$location = new SmartThings\Locations('<<LOCATION_ID>>');
$location->get_rooms();

$room = new SmartThings\Room('<<ROOM_ID>>');
$room->name();
```
