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
- ✅ **API Key**: `abc123def456...` (64-character key provided after OAuth setup)

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
- After authorization, API Key: `abc123def456789...` (automatically delivered to watch app)
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

#### Setup Flow:
1. **Watch opens browser** to setup URL
2. **Watch extracts Session ID** from the setup page
3. **Watch starts polling** every 5-10 seconds
4. **User completes OAuth** in browser  
5. **Watch automatically receives API key** and stops polling

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
   - Server calculates `SHA256(api_key)` to find token file
   - Loads stored `access_token` and `refresh_token` from file
   - Uses SmartThings OAuth tokens to access API

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

### Step 1: Initial Setup Request
```
GET /weather/smartthings/json.php?setup=1
```
- Server generates random `user_id` like: `user_a1b2c3d4_1735948800`
- Returns HTML page with authorization button
- Session ID displayed for reference

### Step 2: SmartThings Authorization  
- User clicks button → Redirected to SmartThings OAuth
- User logs in → Grants device permissions
- SmartThings redirects back with authorization code

### Step 3: Token Exchange
- Server exchanges authorization code for OAuth tokens
- Generates 64-character random API key
- Stores tokens in file: `/tokens/SHA256(api_key).json`

### Step 4: Success Page
- Displays the 64-character API key to user
- User copies this key into their Garmin watch app
- Key format: `abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef`

### Step 5: Normal Usage
```
GET /weather/smartthings/json.php?api_key=abc123def456789abcdef123456789abcdef123456789abcdef123456789abcdef
```
- Server calculates `SHA256(api_key)` to find token file
- Loads OAuth tokens from storage
- Makes authenticated SmartThings API call
- Returns device list as JSON

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
// OAuth setup with automatic polling - no user copy/paste needed!
class SmartThingsOAuth {
    
    function startOAuthSetup() {
        var setupUrl = "https://yourserver.com/weather/smartthings/json.php?setup=1";
        
        // Open browser for user to complete OAuth
        Toybox.System.openUrl(setupUrl);
        
        // TODO: Extract session ID from the opened page (implementation varies by platform)
        // For now, you might need to ask user to copy the Session ID
        showMessage("Complete OAuth in browser, then enter Session ID from the page");
    }
    
    function pollForApiKey(sessionId) {
        var pollUrl = "https://yourserver.com/weather/smartthings/json.php?poll=" + sessionId;
        
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
                    showMessage("OAuth setup complete!");
                    stopPolling();
                    break;
                    
                case "pending":
                    // Still waiting, continue polling
                    var expiresIn = data["expires_in"];
                    showMessage("Waiting for OAuth... (" + expiresIn + "s remaining)");
                    // Continue polling in 5-10 seconds
                    break;
                    
                case "expired":
                case "not_found":
                    // Session expired or invalid, restart setup
                    showError("Session expired. Please restart setup.");
                    stopPolling();
                    break;
            }
        } else {
            showError("Polling failed: " + responseCode);
        }
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
1. **Watch app opens browser** to: `https://yourserver.com/weather/smartthings/json.php?setup=1`
2. **Watch app starts polling** using Session ID from the page
3. **User completes OAuth** in browser (no copying needed!)
4. **Watch app automatically receives** the 64-character API key
5. **Watch app saves API key** and can immediately start using the API

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
