
# SmartThings PHP API Library

>This project provides a robust PHP wrapper for the Samsung SmartThings API, supporting both Personal Access Token and OAuth authentication, with optional Garmin ConnectIQ integration.

## Features
- **Multiple Authentication Methods**: Personal Access Token (simple) or OAuth (secure, multi-user)
- **Garmin Watch Integration**: Seamless support for Garmin ConnectIQ apps
- **Environment Detection**: Works in local and production environments
- **Device Control**: Manage switches, dimmers, TVs, Ecobee, and more

## Quick Start
Install via Composer:

```bash
composer require giannisftaras/smartthings
```

## Authentication Overview
- **Personal Access Token**: Easiest for personal/testing use
- **OAuth with API Key**: Recommended for production and multi-user apps

See [SETUP_AND_SECURITY.md](SETUP_AND_SECURITY.md) for detailed setup, configuration, and security best practices.

## Secure Deployment
For production, move all sensitive files outside the web root and use environment variables for credentials. See [SETUP_AND_SECURITY.md](SETUP_AND_SECURITY.md) for directory structure, deployment scripts, and .htaccess configuration.

## Garmin Integration
For Garmin ConnectIQ integration instructions, see [GARMIN_INTEGRATION.md](GARMIN_INTEGRATION.md).

## Supported Devices
- Samsung TVs
- Ecobee
- Switches and Dimmers
- Generic SmartThings devices

## License
MIT

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
