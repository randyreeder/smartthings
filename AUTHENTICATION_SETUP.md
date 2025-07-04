# SmartThings API Setup Guide

This guide explains how to set up authentication for the SmartThings API application.

## Current Authentication Methods

The API supports two authentication methods:

### Method 1: Personal Access Token (Simple)
- **Usage**: `GET /json.php?token=YOUR_PERSONAL_ACCESS_TOKEN`
- **Best for**: Quick setup, personal use, testing
- **Security**: Medium (single token for all access)

### Method 2: OAuth with API Key (Secure)
- **Usage**: `GET /json.php?api_key=YOUR_API_KEY`
- **Best for**: Multi-user applications, production use, Garmin watches
- **Security**: High (individual tokens per user, API key validation)

---

## Authentication Options

You have two options for authentication:

### Option 1: Personal Access Token (Recommended - Easiest)

**What you need:**
- Just a Personal Access Token from SmartThings

**Steps:**
1. Go to https://account.smartthings.com/tokens
2. Log in with your SmartThings credentials
3. Click **"Generate new token"**
4. Give it a name (e.g., "My App Token")
5. Select these scopes:
   - ✅ **List all devices** (`r:devices:*`)
   - ✅ **Control all devices** (`x:devices:*`)
6. Click **"Generate token"**
7. Copy the generated token (it will look like: `6e1347cf-db1a-4901-bb81-174f5b1b05db`)

**Configuration:**
Update your `oauth_tokens.ini` file:
```ini
[oauth_app]
client_id = "your_smartthings_app_client_id"
client_secret = "your_smartthings_app_client_secret"
redirect_uri = "your_app_redirect_uri"
```

**Note:** Personal Access Tokens are used directly in API calls, not stored in the .ini file.

---

### Option 2: OAuth with Per-User Security (Recommended for Multi-User Apps)

**What you need:**
- `client_id` (from your SmartThings Developer App)
- `client_secret` (from your SmartThings Developer App)  
- `redirect_uri` (your application's callback URL)

**Note:** Individual user tokens are managed automatically through the OAuth flow.

**Steps:**

#### Step 1: Create SmartThings Developer App
1. Go to https://developer.smartthings.com/
2. Sign in with your SmartThings credentials
3. Create a new **SmartApp** project
4. In your app settings, configure OAuth:
   - **Redirect URI**: Set to your application's callback URL
   - **Scopes**: Select `r:devices:*` and `x:devices:*`
5. Note down your **Client ID** and **Client Secret**

#### Step 2: Get OAuth Credentials
1. Update the `oauth_tokens.ini` file with your app credentials:
   ```ini
   [oauth_app]
   client_id = "your_app_client_id"
   client_secret = "your_app_client_secret"
   redirect_uri = "https://your-domain.com/path/to/json.php"
   ```

2. **For Users**: Use the OAuth setup flow:
   ```
   GET /json.php?setup=1
   ```
   - This will guide you through the OAuth authorization process
   - You'll receive an API Key for secure access (no user ID needed for API calls)

**Final Configuration:**
Your `oauth_tokens.ini` will contain only the app credentials:
```ini
[oauth_app]
client_id = "your_app_client_id"
client_secret = "your_app_client_secret"
redirect_uri = "https://your-domain.com/path/to/json.php"
```

**Note:** OAuth access tokens are stored per-user in individual JSON files for security, not in the shared .ini file.

---

## Testing Your Setup

Once configured, test your setup:

**Personal Access Token:**
```bash
curl "http://localhost:8080/tests/json.php?token=YOUR_PERSONAL_ACCESS_TOKEN"
```

**OAuth (after completing setup flow):**
```bash
curl "http://localhost:8080/tests/json.php?api_key=YOUR_API_KEY"
```

You should see a JSON response with all your SmartThings devices.

---

## Troubleshooting

### Error: "User not authorized" (401)
- **OAuth**: Complete the OAuth setup flow: `GET /json.php?setup=1`

### Error: "Invalid API key" (401/403) 
- **OAuth**: Use the exact API key provided during OAuth setup
- **OAuth**: Make sure you've completed the OAuth setup flow first

### Error: "Personal Token Invalid" (401)
- **Personal Token**: Check that your token is valid and hasn't expired
- Get a new token from: https://account.smartthings.com/tokens

### Error: "OAuth configuration not found" (500)
- Check that `oauth_tokens.ini` exists and contains the `[oauth_app]` section

### Error: "Invalid client_id" or OAuth errors
- Double-check your Client ID from the SmartThings Developer Console
- Verify your `oauth_tokens.ini` contains the correct app credentials

### No devices returned
- Check that you've granted the correct scopes (`r:devices:*` and `x:devices:*`)
- Verify your SmartThings account has devices
- Ensure your token/credentials have the necessary permissions

---

## Security Notes

- **Never share your tokens publicly**
- **Personal Access Tokens** don't expire but should be kept secure
- **OAuth tokens** are stored per-user and can be refreshed automatically
- **API keys** provide secure access without exposing user IDs
- Store your `oauth_tokens.ini` file securely and don't commit it to version control

---

## What Each User Needs to Provide

### For Personal Access Token (Simple):
```
✅ Personal Access Token (36-character UUID)
   Example: 6e1347cf-db1a-4901-bb81-174f5b1b05db
```

### For OAuth (Secure Multi-User):
```
✅ SmartThings Developer App with OAuth configured
✅ oauth_tokens.ini file with app credentials
✅ Complete OAuth setup flow per user
```

**Result:** Each user gets their own API Key for secure access.

The Personal Access Token method is recommended for personal use, while OAuth is better for applications serving multiple users.
