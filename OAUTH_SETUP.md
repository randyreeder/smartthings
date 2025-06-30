# OAuth Setup Quick Start

## For First-Time Setup:

1. **Copy the example configuration:**
   ```bash
   cp oauth_tokens.ini.example oauth_tokens.ini
   ```

2. **Update oauth_tokens.ini with your SmartThings app credentials:**
   - Get them from: https://developer.smartthings.com/
   - Update client_id, client_secret, and redirect_uri

3. **For Garmin Watch Integration:**
   - Visit: `GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID`
   - Follow the OAuth authorization flow
   - Save your User ID and API Key

## Authentication Methods:

- **Personal Token**: `GET /json.php?token=YOUR_PAT`
- **OAuth**: `GET /json.php?user_id=YOUR_ID&api_key=YOUR_KEY`

See `AUTHENTICATION_SETUP.md` for detailed instructions.
