# Setup and Security Guide

This guide consolidates all authentication, configuration, deployment, and security instructions for the SmartThings PHP API Library.

## Authentication Setup

### 1. Personal Access Token (Simple)
- Best for personal use or testing
- Generate at https://account.smartthings.com/tokens
- Use directly in API calls

### 2. OAuth with API Key (Secure, Multi-User)
- Recommended for production and multi-user apps
- Requires SmartThings Developer App and OAuth credentials
- Store credentials in environment variables or config files **outside** the web root

#### Example Environment Variables
```bash
export SMARTTHINGS_CLIENT_ID="your_client_id"
export SMARTTHINGS_CLIENT_SECRET="your_client_secret"
export SMARTTHINGS_REDIRECT_URI="https://yourdomain.com/smartthings/json.php"
```

#### Example Config File (oauth_tokens.ini)
```ini
[oauth_app]
client_id = "your_smartthings_app_client_id"
client_secret = "your_smartthings_app_client_secret"
redirect_uri = "https://yourserver.com/smartthings/api/json.php"
```

## Secure Deployment

- **Move all config and token files outside the web root**
- Use the provided `deploy-secure.sh` script to automate directory setup
- Recommended structure:

```
~/smartthings_config/
├── oauth_tokens.ini
└── tokens/
~/git/smartthings/
├── vendor/
└── src/
~/public_html/smartthings/
├── json.php
├── set.php
└── index.html
```

## Web Server Security

- Use `.htaccess` to block direct web access to `.ini`, `.json`, `.env`, etc.
- Example:
```apache
<FilesMatch "\.(ini|json|log|env)$">
    Require all denied
</FilesMatch>
```

## Troubleshooting
- See error messages in [AUTHENTICATION_SETUP.md] for common issues
- Ensure all sensitive files are outside the web root and not web-accessible

## What Each User Needs
- For Personal Access Token: Only the token
- For OAuth: API key, client ID, client secret, and redirect URI

## Further Help
- For Garmin integration, see [GARMIN_INTEGRATION.md]
- For project overview, see [README.md]
