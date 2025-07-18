# Garmin Watch SmartThings Integration

This guide explains how users can configure their Garmin watch app to access their SmartThings devices through your web server using **two different authentication methods**.

## 🏗️ **System Architecture Overview**

The SmartThings API server stores credentials and tokens in secure locations:

### Server-Side Storage (Outside Web Root)
```
~/smartthings_config/
├── oauth_tokens.ini              # OAuth app credentials (client_id, client_secret)
└── tokens/                       # User authentication tokens
    ├── a1b2c3d4e5f6...json      # User 1's OAuth tokens (filename = SHA256 hash of API key)
    └── f6e5d4c3b2a1...json      # User 2's OAuth tokens (filename = SHA256 hash of API key)
```

### What Each Token File Contains
```json
{
  "user_id": "user_a1b2c3d4_1735948800",
  "api_key": "64-character-random-hex-string",
  "access_token": "smartthings-oauth-access-token", 
  "refresh_token": "smartthings-oauth-refresh-token",
  "created": 1735948800
}
```

---

## For Users: Choose Your Authentication Method

### Method 1: Personal Access Token (Simple, but requires creating a token)

**What to enter in your Garmin watch app settings:**
- ✅ **Server URL**: `https://yourserver.com/weather/smartthings/json.php`
- ✅ **Token**: `6e1347cf-db1a-4901-bb81-174f5b1b05db` (your personal token)

**How to get your Personal Access Token:**
1. Go to https://account.smartthings.com/tokens
2. Generate new token with device permissions
3. Copy the 36-character token
4. Enter in your Garmin watch app

### Method 2: OAuth Authorization (No token creation needed)

**What to enter in your Garmin watch app settings:**
- ✅ **Server URL**: `https://yourserver.com/weather/smartthings/json.php`
- ✅ **API Key**: `abc123def456...` (64-character key **generated by server** during OAuth setup)

**🎯 Seamless Setup Process (No Manual Copy/Paste!):**
1. **Watch app starts OAuth flow** by opening browser to:
   ```
   https://yourserver.com/weather/smartthings/json.php?setup=1
   ```
2. **Watch app gets Session ID** from the setup page and starts polling
3. **User completes OAuth** in browser (clicks authorize, logs in, grants permissions)
4. **Watch app automatically receives API key** via polling - no manual copy needed!
5. **Done!** Watch app automatically saves and starts using the API key

**📱 Traditional Setup Process (Manual):**
1. **Visit setup URL** in any web browser:
   ```
   https://yourserver.com/weather/smartthings/json.php?setup=1
   ```
2. **Note your Session ID** (automatically generated, e.g., `user_a1b2c3d4_1735948800`)
3. **Click "Authorize SmartThings Access"**
4. **Log in with your SmartThings credentials**
5. **Grant permissions** to access your devices
6. **Copy your API Key** from the success page (64-character hex string)
7. **Manually enter API Key** into your Garmin watch app

**Example Setup:**
- Visit: `https://yourserver.com/weather/smartthings/json.php?setup=1`
- Session ID generated: `user_a1b2c3d4_1735948800`
- Watch app polls: `https://yourserver.com/weather/smartthings/json.php?poll=user_a1b2c3d4_1735948800`
- After authorization, **Server generates** API Key: `abc123def456789...` (automatically delivered to watch app)
- Your watch calls: `https://yourserver.com/weather/smartthings/json.php?api_key=abc123def456789...`

---

## 🔐 **Authentication Details for Garmin Developers**

### What Your Garmin Watch App Needs to Store

#### Method 1 - Personal Access Token:
```json
{
  "server_url": "https://yourserver.com/weather/smartthings/json.php",
  "auth_method": "token",
  "token": "6e1347cf-db1a-4901-bb81-174f5b1b05db"
}
```

#### Method 2 - OAuth API Key:
```json
{
  "server_url": "https://yourserver.com/weather/smartthings/json.php", 
  "auth_method": "api_key",
  "api_key": "abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef"
}
```

### What Your Watch App Sends to json.php

#### Method 1 - Personal Token Request:
```
GET /weather/smartthings/json.php?token=6e1347cf-db1a-4901-bb81-174f5b1b05db
```

#### Method 2a - OAuth API Key Request:
```
GET /weather/smartthings/json.php?api_key=abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef
```

#### Method 2b - OAuth Session Polling (NEW!):
```
GET /weather/smartthings/json.php?poll=user_a1b2c3d4_1735948800
```

### 🆕 **Seamless OAuth Polling API**

The polling API allows watch apps to automatically receive the API key without user copy/paste:

#### Setup Flow Options:

**🎯 Option 1: Watch App Gets Session ID via JSON API**
1. **Watch calls JSON setup endpoint** to get Session ID
2. **Watch opens browser** to auth URL  
3. **Watch starts polling** using Session ID
4. **User completes OAuth** in browser
5. **Watch automatically receives API key** and stops polling

**📱 Option 2: Watch App Provides Own Session ID**  
1. **Watch generates Session ID** (format: `user_[16hex]_[timestamp]`)
2. **Watch calls JSON setup endpoint** with custom Session ID
3. **Watch opens browser** to auth URL
4. **Watch starts polling** using its own Session ID
5. **User completes OAuth** in browser
6. **Watch automatically receives API key** and stops polling

**🌐 Option 3: Browser-Based (Manual Session ID)**
1. **User opens browser** to setup URL
2. **User notes Session ID** from page
3. **User enters Session ID** into watch app  
4. **Watch starts polling** using entered Session ID
5. **User completes OAuth** in browser
6. **Watch automatically receives API key** and stops polling

#### API Endpoints:

**Get Session ID (JSON):**
```
GET /weather/smartthings/json.php?setup=1&format=json
GET /weather/smartthings/json.php?setup=1&format=json&user_id=custom_session_id
```

**Response:**
```json
{
  "session_id": "user_a1b2c3d4_1735948800",
  "auth_url": "https://api.smartthings.com/oauth/authorize?...",
  "poll_url": "https://yourserver.com/weather/smartthings/json.php?poll=user_a1b2c3d4_1735948800",
  "expires_in": 3600,
  "instructions": "Open auth_url in browser, then poll poll_url every 5-10 seconds"
}
```

#### Polling Responses:

**🔄 Pending (waiting for user to complete OAuth):**
```json
{
  "status": "pending",
  "message": "Waiting for OAuth authorization to complete",
  "session_id": "user_a1b2c3d4_1735948800",
  "expires_in": 3240
}
```

**✅ Success (OAuth completed):**
```json
{
  "status": "success", 
  "api_key": "abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef",
  "message": "OAuth setup completed successfully",
  "session_id": "user_a1b2c3d4_1735948800"
}
```

**❌ Session Not Found:**
```json
{
  "status": "not_found",
  "message": "Session not found or expired",
  "session_id": "user_a1b2c3d4_1735948800"
}
```

**⏰ Session Expired:**
```json
{
  "status": "expired",
  "message": "Session expired. Please start a new setup process.",
  "session_id": "user_a1b2c3d4_1735948800"
}
```

### Server-Side Token Processing

1. **Personal Token**: Server uses token directly with SmartThings API
2. **OAuth API Key**: 
   - Server receives 64-character API key from Garmin app
   - Server calculates `SHA256(api_key)` to find token file
   - Loads stored `access_token` and `refresh_token` from file
   - Uses SmartThings OAuth tokens to access API

**Important**: The `client_id` and `client_secret` in `oauth_tokens.ini` are server-side OAuth app credentials that Garmin apps never see or use.

---

## 🔄 **How Refresh Tokens Work**

### Token Lifecycle
OAuth access tokens expire (typically after 24 hours). Refresh tokens allow your system to automatically get new access tokens without requiring user re-authentication.

### Automatic Refresh Process
1. **API Request Made** - Watch app makes API call using stored API key
2. **Token Expired** - SmartThings returns 401 Unauthorized (access token expired)
3. **Automatic Refresh** - Server automatically uses refresh token to get new access token
4. **Token Updated** - New access token (and potentially new refresh token) stored in user's token file
5. **Request Retried** - Original API request retried with new access token
6. **Success** - API call succeeds, watch app gets data

### What Happens Behind the Scenes

#### Server-Side Refresh Process:
```php
// When SmartThings API returns 401 (token expired)
if ($code === 401 && !empty($refresh_token)) {
    // 1. Call SmartThings OAuth refresh endpoint
    POST https://auth-global.api.smartthings.com/oauth/token
    {
        "grant_type": "refresh_token",
        "client_id": "your_app_client_id",
        "client_secret": "your_app_client_secret", 
        "refresh_token": "stored_refresh_token"
    }
    
    // 2. SmartThings responds with new tokens
    {
        "access_token": "new_access_token",
        "refresh_token": "new_refresh_token", // May be same or new
        "token_type": "Bearer",
        "expires_in": 86400
    }
    
    // 3. Update stored tokens in user's JSON file
    {
        "user_id": "user_a1b2c3d4_1735948800",
        "api_key": "abc123def456...",
        "access_token": "new_access_token",     // Updated
        "refresh_token": "new_refresh_token",   // Updated
        "created": 1735948800,
        "refreshed": 1735948900                 // Added timestamp
    }
    
    // 4. Retry original API request with new access token
    // 5. Return data to watch app (seamless to user)
}
```

### User Experience
- **Seamless** - Watch app continues working without interruption
- **No Re-authentication** - Users never need to redo OAuth setup
- **Automatic** - All token refresh happens server-side
- **Secure** - Refresh tokens are stored securely on server

### Token Security
- **Refresh tokens** are long-lived (months/years) and stored securely on server
- **Access tokens** are short-lived (hours/days) and refreshed automatically
- **API keys** (64-character hex) are permanent identifiers for watch apps
- **Session IDs** are temporary (1 hour) and used only during OAuth setup

### Error Handling
If refresh token is invalid or expired:
- User will need to complete OAuth setup again
- Watch app will receive 401 error with `setup_url` in response
- Users can re-authorize without losing other settings

### Refresh Token Best Practices
1. **Store securely** - Refresh tokens stored outside web root
2. **Handle rotation** - Some OAuth providers issue new refresh tokens on refresh
3. **Graceful degradation** - Fall back to re-authentication if refresh fails
4. **Logging** - Log refresh attempts for debugging (but not token values)

---

## Which Method Should You Choose?

### ✅ Choose Method 1 (Personal Token) if you:
- Don't mind creating a SmartThings developer token
- Want the simplest one-time setup
- Prefer not to use your SmartThings login through a web browser

### ✅ Choose Method 2 (OAuth) if you:
- Prefer logging in through the official SmartThings website
- Want a more "app-like" authorization experience
- Don't want to create developer tokens manually

---

## API Usage Examples

### Method 1: Personal Token
```
GET https://yourserver.com/weather/smartthings/json.php?token=6e1347cf-db1a-4901-bb81-174f5b1b05db
```

### Method 2: OAuth API Key (64-character hex string)
```
GET https://yourserver.com/weather/smartthings/json.php?api_key=abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef
```

**Both methods return the same JSON response:**
```json
{
  "error_code": 200,
  "error_message": "",
  "devices": [
    {
      "id": "9b0820c2-6356-458f-88ca-91084dc9b2f3",
      "name": "eWeLink Outlet",
      "label": "Fan Outlet", 
      "type": "ZIGBEE",
      "value": "off"
    }
  ]
}
```

---

## 🔧 **OAuth Setup Flow Details (Method 2)**

### 🆕 **Seamless Flow (Watch App Polling)**

#### 🎯 **Method A: Server-Generated Session ID**

##### Step 1: Watch App Gets Session ID
```
GET /weather/smartthings/json.php?setup=1&format=json
```
**Server responds with:**
```json
{
  "session_id": "user_a1b2c3d4_1735948800",
  "auth_url": "https://api.smartthings.com/oauth/authorize?...",
  "poll_url": "https://yourserver.com/weather/smartthings/json.php?poll=user_a1b2c3d4_1735948800",
  "expires_in": 3600
}
```

##### Step 2: Watch App Opens Browser & Starts Polling
- **Watch opens browser** to `auth_url` 
- **Watch starts polling** `poll_url` every 5-10 seconds
- **Server responds** with `{"status": "pending"}` while waiting

##### Step 3-6: Same as before...
(User completes OAuth, server exchanges tokens, watch receives API key)

#### 📱 **Method B: Custom Session ID** 

##### Step 1: Watch App Generates Session ID
```javascript
// Generate session ID: user_[16hex]_[timestamp]
var sessionId = "user_a1b2c3d4e5f6g7h8_" + Time.now().value();
```

##### Step 2: Watch App Registers Session ID  
```
GET /weather/smartthings/json.php?setup=1&format=json&user_id=user_a1b2c3d4e5f6g7h8_1735948800
```

##### Step 3: Watch App Opens Browser & Starts Polling
- **Watch opens browser** to returned `auth_url`
- **Watch starts polling** using its own Session ID  
- **Benefit**: Watch app has full control over Session ID format

#### 🌐 **Method C: Manual Session ID (Fallback)**

##### Step 1: User Opens Browser
```
GET /weather/smartthings/json.php?setup=1
```
- Returns HTML page with Session ID displayed
- User notes Session ID: `user_a1b2c3d4_1735948800`

##### Step 2: User Enters Session ID in Watch App
- User manually types Session ID into watch app
- Watch app starts polling using entered Session ID

### 📱 **Traditional Manual Flow (Browser Only)**

#### Step 1-3: Same as Seamless Flow
(Initial setup, user authorization, token exchange)

#### Step 4: Manual Success Page
- Displays the 64-character API key to user
- User manually copies key: `abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef`
- User manually enters key into Garmin watch app settings

#### Step 5: Manual Configuration
- User pastes API key into watch app
- Watch app validates 64-character format
- Normal API usage begins

---

## 🚨 **Error Handling**

### No Authentication Provided
```json
{
  "error_code": 400,
  "error_message": "Authentication required. Use ?token=YOUR_TOKEN or ?api_key=YOUR_API_KEY",
  "devices": [],
  "help": "For Personal Access Token: https://account.smartthings.com/tokens"
}
```

### Invalid OAuth API Key
```json
{
  "error_message": "Invalid API key. Please complete OAuth setup first.",
  "error_code": 401,
  "devices": [],
  "setup_url": "/json.php?setup=1"
}
```

### OAuth Setup Required  
```json
{
  "error_message": "Invalid API key.",
  "error_code": 403,
  "devices": [],
  "help": "Use the API key provided during OAuth setup"
}
```

---

## 💻 **For Garmin Watch Developers**

### 🆕 **Seamless OAuth Implementation (Recommended)**

```javascript
// OAuth setup with automatic polling - multiple approaches!
class SmartThingsOAuth {
    
    // Approach 1: Let server generate Session ID
    function startOAuthSetupAuto() {
        var setupUrl = "https://yourserver.com/weather/smartthings/json.php?setup=1&format=json";
        
        Toybox.Communications.makeWebRequest(
            setupUrl, 
            null, 
            {}, 
            method(:onSetupResponse)
        );
    }
    
    // Approach 2: Generate custom Session ID
    function startOAuthSetupCustom() {
        // Generate custom session ID with required format
        var timestamp = Time.now().value();
        var randomHex = generateRandomHex(16); // 16 characters
        var sessionId = "user_" + randomHex + "_" + timestamp;
        
        var setupUrl = "https://yourserver.com/weather/smartthings/json.php?setup=1&format=json&user_id=" + sessionId;
        
        Toybox.Communications.makeWebRequest(
            setupUrl, 
            null, 
            {}, 
            method(:onSetupResponse)
        );
    }
    
    function onSetupResponse(responseCode, data) {
        if (responseCode == 200) {
            // Save session info
            Properties.setValue("session_id", data["session_id"]);
            Properties.setValue("poll_url", data["poll_url"]);
            
            // Open browser for user OAuth
            Toybox.System.openUrl(data["auth_url"]);
            
            // Start polling for API key
            startPolling(data["session_id"]);
            
        } else {
            showError("Setup failed: " + responseCode);
        }
    }
    
    function startPolling(sessionId) {
        // Store session ID and start polling
        _sessionId = sessionId;
        _pollTimer = new Timer.Timer();
        _pollTimer.start(method(:pollForApiKey), 5000, true); // Poll every 5 seconds
    }
    
    function pollForApiKey() {
        var pollUrl = "https://yourserver.com/weather/smartthings/json.php?poll=" + _sessionId;
        
        Toybox.Communications.makeWebRequest(
            pollUrl, 
            null, 
            {}, 
            method(:onPollResponse)
        );
    }
    
    function onPollResponse(responseCode, data) {
        if (responseCode == 200) {
            switch(data["status"]) {
                case "success":
                    // Got the API key! Save it and stop polling
                    var apiKey = data["api_key"];
                    Properties.setValue("api_key", apiKey);
                    Properties.setValue("auth_method", "api_key");
                    _pollTimer.stop();
                    showMessage("OAuth setup complete!");
                    break;
                    
                case "pending":
                    // Still waiting, continue polling
                    var expiresIn = data["expires_in"];
                    showMessage("Waiting for OAuth... (" + expiresIn + "s remaining)");
                    break;
                    
                case "expired":
                case "not_found":
                    // Session expired or invalid, restart setup
                    _pollTimer.stop();
                    showError("Session expired. Please restart setup.");
                    break;
            }
        } else {
            showError("Polling failed: " + responseCode);
        }
    }
    
    function generateRandomHex(length) {
        var chars = "0123456789abcdef";
        var result = "";
        for (var i = 0; i < length; i++) {
            result += chars.charAt(Math.rand() % chars.length());
        }
        return result;
    }
}
```

### **Traditional API Usage (Both Methods)**

```javascript
// Method 1: Personal Token
var url1 = "https://yourserver.com/weather/smartthings/json.php?token=" + userToken;

// Method 2: OAuth API Key (64-character hex string)
var url2 = "https://yourserver.com/weather/smartthings/json.php?api_key=" + apiKey;

// Same request handling for both methods
Toybox.Communications.makeWebRequest(url, null, options, method(:onReceive));

function onReceive(responseCode, data) {
    if (responseCode == 200) {
        // Success - data contains device list
        var response = data;  // JSON already parsed
        var devices = response["devices"];
        
        // Process devices array
        for (var i = 0; i < devices.size(); i++) {
            var device = devices[i];
            System.println("Device: " + device["label"] + " = " + device["value"]);
        }
    } else {
        // Handle errors
        var errorMsg = data["error_message"];
        System.println("API Error: " + errorMsg);
        
        if (responseCode == 401 && data.hasKey("setup_url")) {
            // OAuth setup required
            System.println("Setup required at: " + data["setup_url"]);
        }
    }
}
```

### Watch App Settings Structure

**Recommended settings screen:**
```
┌─────────────────────────────────┐
│ SmartThings API Settings        │
├─────────────────────────────────┤
│ Server URL: [              ]    │
│ ________________________________│
│                                 │
│ Authentication Method:          │
│ ○ Personal Token               │
│ ○ OAuth API Key                │
│                                 │
│ Token/API Key: [              ] │
│ ________________________________│
│                                 │
│ [ Test Connection ]             │
│ [ Save Settings ]               │
└─────────────────────────────────┘
```

### User Setup Instructions by Method

#### For Method 1 (Personal Token) Users:
1. **Direct them to**: https://account.smartthings.com/tokens
2. **Tell them to**: Create token with device read/control permissions  
3. **Token format**: 36-character UUID (e.g., `6e1347cf-db1a-4901-bb81-174f5b1b05db`)
4. **Paste into**: Watch app token field

#### For Method 2 (OAuth) Users - Seamless Approach:

**🎯 Fully Automated (Recommended):**
1. **Watch app handles everything** - just tap "Setup OAuth" in watch app
2. **Watch app gets Session ID** automatically via JSON API
3. **Watch app opens browser** for you to complete OAuth  
4. **User completes OAuth** in browser (no copying needed!)
5. **Watch app automatically receives** the 64-character API key
6. **Done!** Watch app saves API key and starts using the API immediately

**📱 Semi-Automated (Fallback):**
1. **User opens** `https://yourserver.com/weather/smartthings/json.php?setup=1` in browser
2. **User notes Session ID** displayed on page (e.g., `user_a1b2c3d4_1735948800`)  
3. **User enters Session ID** into watch app settings
4. **Watch app starts polling** automatically using entered Session ID
5. **User completes OAuth** in browser (authorize button on same page)
6. **Watch app automatically receives** the API key - no manual copying!

#### For Method 2 (OAuth) Users - Manual Approach:
1. **Direct them to**: `https://yourserver.com/weather/smartthings/json.php?setup=1`
2. **Tell them to**: Complete browser-based OAuth authorization
3. **API Key format**: 64-character hex string (e.g., `abc123def456789abcdef...`)
4. **Copy/paste into**: Watch app API key field

### Error Handling Best Practices

```javascript
function handleApiError(responseCode, data) {
    switch(responseCode) {
        case 400:
            // No authentication provided
            showError("Please configure authentication in settings");
            break;
            
        case 401:
            // Invalid OAuth API key - setup required
            if (data.hasKey("setup_url")) {
                showError("OAuth setup required. Visit: " + data["setup_url"]);
            } else {
                showError("Invalid credentials");
            }
            break;
            
        case 403:
            // API key format error
            showError("Invalid API key format");
            break;
            
        case 500:
            // Server configuration error
            showError("Server configuration error");
            break;
            
        default:
            showError("Network error: " + responseCode);
    }
}
```

### Token Storage Recommendations

```javascript
// Store user credentials securely
var settings = {
    "server_url": Properties.getValue("server_url"),
    "auth_method": Properties.getValue("auth_method"), // "token" or "api_key"
    "credential": Properties.getValue("credential")     // The actual token or API key
};

// Validate before use
function validateSettings() {
    if (!settings["server_url"] || !settings["credential"]) {
        return false;
    }
    
    if (settings["auth_method"] == "token") {
        // Personal token should be 36 characters (UUID format)
        return settings["credential"].length() == 36;
    } else if (settings["auth_method"] == "api_key") {
        // OAuth API key should be 64 characters (hex string)
        return settings["credential"].length() == 64;
    }
    
    return false;
}
```

---

## 📋 **Summary**

**Method 1 - Personal Token:**
- ✅ One-time token creation at SmartThings
- ✅ Direct API access  
- ✅ 36-character UUID format
- ❌ Requires SmartThings developer token creation

**Method 2 - OAuth:**
- ✅ No token creation needed
- ✅ Official SmartThings login flow
- ✅ More user-friendly browser setup
- ✅ 64-character hex API key
- ✅ Secure token storage (SHA256 hashed filenames)
- 🆕 **Seamless polling API** - no manual copy/paste needed!
- ✅ **Session-based setup** with automatic API key delivery
- ❌ Requires one-time web browser setup

**🆕 New Polling API Features:**
- 🔄 **Automatic API key retrieval** via session polling
- ⏰ **1-hour session expiry** for security
- 🧹 **Automatic cleanup** of completed/expired sessions  
- 📱 **No manual copy/paste** required for watch apps
- 🔒 **Secure session tracking** with SHA256 hashed session files

**Storage Architecture:**
- 🔐 **Server config**: `~/smartthings_config/oauth_tokens.ini` (OAuth app credentials)
- 🎫 **User tokens**: `~/smartthings_config/tokens/SHA256(api_key).json` (per-user OAuth tokens)
- 🕰️ **Session files**: `~/smartthings_config/tokens/session_SHA256(session_id).json` (temporary polling)
- 🌐 **API endpoints**: `~/public_html/weather/smartthings/json.php` (web-accessible)

Both methods provide identical functionality - users can choose based on their preference! The new polling API makes OAuth setup seamless for watch applications.


### 🚨 **Critical Issue: Missing Token Refresh (Fixed!)**

**Problem Identified:**
Your Garmin app was working yesterday but failing today because:

1. ✅ **OAuth tokens were valid yesterday** (24-hour lifespan)
2. ❌ **Access token expired today** (normal SmartThings behavior)
3. ❌ **json.php had NO automatic token refresh** (critical gap)
4. ❌ **Token file became empty** during failed refresh attempt

**Root Cause:**
The original `json.php` implementation was missing automatic token refresh logic. When access tokens expired, the system returned 401 errors instead of:
1. Using the refresh token to get new access tokens
2. Retrying the API call with refreshed tokens
3. Updating the token file with new tokens

**✅ Solution Implemented:**
The `json.php` script now includes robust token refresh logic:

```php
try {
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    // If 401 Unauthorized and we have refresh token, try to refresh and retry
    if ($e->getCode() === 401 && $smartAPI->getRefreshToken()) {
        $client_creds = getClientCredentials();
        $refreshed = $smartAPI->refreshAccessToken($client_creds['client_id'], $client_creds['client_secret']);
        
        if ($refreshed) {
            // Retry the API call with refreshed token
            $devices = $smartAPI->list_devices();
        }
    }
}
```

**Recovery Steps:**
1. **Complete OAuth setup again**: Visit `/json.php?setup=1`
2. **Get new API key** (or keep using your existing one if you prefer)
3. **System will now handle token refresh automatically**
4. **Your app should work long-term without this issue recurring**

### 🔍 **Investigating Refresh Token Lifespan Issues**

**Normal Behavior:**
- **Access tokens**: Expire every 24 hours ✅ (handled automatically by system)
- **Refresh tokens**: Should last 6-12 months or longer

**If your refresh token expired after only 24 hours, possible causes:**
1. **SmartThings OAuth app configuration issue** - Check Developer Console
2. **Refresh token was revoked** - User security action or account changes
3. **OAuth app credentials changed/expired** - Verify app is still active
4. **SmartThings account security action** - Password change, 2FA, etc.
5. **Unusual SmartThings API behavior** - Rare, but possible

**Monitoring Your New API Key:**
To verify your new API key will refresh properly after 24+ hours:

```bash
# Test immediately
curl 'https://yourserver.com/weather/smartthings/json.php?api_key=YOUR_NEW_API_KEY'

# Test again after 25+ hours
curl 'https://yourserver.com/weather/smartthings/json.php?api_key=YOUR_NEW_API_KEY'
```

**If the new API key also fails after 24 hours:**
- Check your SmartThings Developer Console for app status
- Verify OAuth app hasn't been disabled or modified
- Consider recreating the OAuth app with fresh credentials
- Contact SmartThings support if the issue persists

The automatic refresh system is working correctly - if refresh tokens are expiring unusually quickly, it's likely a SmartThings account or OAuth app configuration issue.