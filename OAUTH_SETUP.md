# OAuth Setup Quick Start

## For First-Time Setup:

1. **Copy the example configuration:**
   ```bash
   cp oauth_tokens.ini.example oauth_tokens.ini
   ```

2. **Update oauth_tokens.ini with your SmartThings app credentials:**
   - Get them from: https://developer.smartthings.com/
   - Update client_id, client_secret, and redirect_uri

3. **For User Integration:**
   - Visit: `GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID`
   - Follow the OAuth authorization flow
   - Save your API Key (user_id is no longer needed for API calls)

## Authentication Methods:

- **Personal Token**: `GET /json.php?token=YOUR_PAT`
- **OAuth**: `GET /json.php?api_key=YOUR_API_KEY`

See `AUTHENTICATION_SETUP.md` for detailed instructions.
