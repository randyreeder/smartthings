# Garmin Watch SmartThings Integration

This guide explains how users can configure their Garmin watch app to access their SmartThings devices through your web server using **two different methods**.

## For Users: Choose Your Authentication Method

### Method 1: Personal Access Token (Simple, but requires creating a token)

**What to enter in your Garmin watch app settings:**
- ✅ **Server URL**: `https://yourserver.com/tests/json.php`
- ✅ **Token**: `6e1347cf-db1a-4901-bb81-174f5b1b05db` (your personal token)

**How to get your Personal Access Token:**
1. Go to https://account.smartthings.com/tokens
2. Generate new token with device permissions
3. Copy the 36-character token
4. Enter in your Garmin watch app

### Method 2: OAuth Authorization (No token creation needed)

**What to enter in your Garmin watch app settings:**
- ✅ **Server URL**: `https://yourserver.com/tests/json.php`
- ✅ **API Key**: `abc123def456...` (provided after OAuth setup)

**Setup Process:**
1. **Visit setup URL** in any web browser:
   ```
   https://yourserver.com/tests/json.php?setup=1
   ```
2. **Click "Authorize SmartThings Access"**
3. **Log in with your SmartThings credentials**
4. **Grant permissions** to access your devices
5. **Copy your API Key** from the success page
6. **Done!** Your Garmin watch can now use your API Key

**Example Setup:**
- Visit: `https://yourserver.com/tests/json.php?setup=1`
- After authorization, get your API Key: `abc123def456...`
- Your watch calls: `https://yourserver.com/tests/json.php?api_key=abc123def456...`

---

## Which Method Should You Choose?

### ✅ Choose Method 1 (Personal Token) if you:
- Don't mind creating a SmartThings developer token
- Want the simplest one-time setup
- Prefer not to use your SmartThings login through a web browser

### ✅ Choose Method 2 (OAuth) if you:
- Prefer logging in through the official SmartThings website
- Want a more "app-like" authorization experience

---

## API Usage Examples

### Method 1: Personal Token
```
GET https://yourserver.com/tests/json.php?token=6e1347cf-db1a-4901-bb81-174f5b1b05db
```

### Method 2: OAuth API Key
```
GET https://yourserver.com/tests/json.php?api_key=abc123def456...
```

**Both methods return the same JSON response:**
```json
[
  {
    "id": "9b0820c2-6356-458f-88ca-91084dc9b2f3",
    "name": "eWeLink Outlet",
    "label": "Fan Outlet",
    "type": "ZIGBEE",
    "value": "off"
  }
]
```

---

## OAuth Setup Flow (Method 2)

### Step 1: Initial Setup Request
```
GET /json.php?setup=1
```
Returns an HTML page with authorization button.

### Step 2: SmartThings Authorization
User clicks button → Redirected to SmartThings → Logs in → Grants permissions

### Step 3: Completion
User redirected back with success message showing their API Key. Save this API Key!

### Step 4: Normal Usage
```
GET /json.php?api_key=abc123def456...
```
Returns JSON array of devices.

---

## Error Handling

### No Authentication Provided
```json
{
  "error_message": "Authentication required",
  "error_code": 400,
  "methods": {
    "personal_token": "GET /json.php?token=YOUR_PERSONAL_ACCESS_TOKEN",
    "oauth_setup": "GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID"
  }
}
```

### OAuth User Not Set Up Yet
```json
{
  "error_message": "Invalid API key. Please complete OAuth setup first.",
  "error_code": 401,
  "setup_url": "/json.php?setup=1"
}
```

---

## For Garmin Watch Developers

### Sample Code for Both Methods

```javascript
// Method 1: Personal Token
var url1 = "https://yourserver.com/tests/json.php?token=" + userToken;

// Method 2: OAuth API Key  
var url2 = "https://yourserver.com/tests/json.php?api_key=" + apiKey;

// Same request handling for both methods
Toybox.Communications.makeWebRequest(url, null, options, method(:onReceive));
```

### User Setup Instructions

For **Method 1** users: Direct them to SmartThings token page
For **Method 2** users: Direct them to your setup URL: `/json.php?setup=1`

---

## Summary

**Method 1 - Personal Token:**
- ✅ One-time token creation
- ✅ Direct API access
- ❌ Requires SmartThings developer token

**Method 2 - OAuth:**
- ✅ No token creation needed
- ✅ Official SmartThings login
- ✅ More user-friendly
- ✅ Secure API key only (no user ID needed)
- ❌ Requires one-time web browser setup

Both methods provide the same functionality - users can choose based on their preference!
