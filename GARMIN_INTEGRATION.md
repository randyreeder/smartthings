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
- ✅ **User ID**: `any_unique_name_you_choose` (e.g., `john_smith_123`)

**Setup Process:**
1. **Choose a unique User ID** (e.g., your email, name + number, etc.)
2. **Visit setup URL** in any web browser:
   ```
   https://yourserver.com/tests/json.php?setup=1&user_id=YOUR_CHOSEN_ID
   ```
3. **Click "Authorize SmartThings Access"**
4. **Log in with your SmartThings credentials**
5. **Grant permissions** to access your devices
6. **Done!** Your Garmin watch can now use your User ID

**Example Setup:**
- Visit: `https://yourserver.com/tests/json.php?setup=1&user_id=john_smith_123`
- After authorization, your watch calls: `https://yourserver.com/tests/json.php?user_id=john_smith_123`

---

## Which Method Should You Choose?

### ✅ Choose Method 1 (Personal Token) if you:
- Don't mind creating a SmartThings developer token
- Want the simplest one-time setup
- Prefer not to use your SmartThings login through a web browser

### ✅ Choose Method 2 (OAuth) if you:
- Don't want to create any tokens yourself
- Prefer logging in through the official SmartThings website
- Want a more "app-like" authorization experience

---

## API Usage Examples

### Method 1: Personal Token
```
GET https://yourserver.com/tests/json.php?token=6e1347cf-db1a-4901-bb81-174f5b1b05db
```

### Method 2: OAuth User ID
```
GET https://yourserver.com/tests/json.php?user_id=john_smith_123
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
GET /json.php?setup=1&user_id=YOUR_CHOSEN_ID
```
Returns an HTML page with authorization button.

### Step 2: SmartThings Authorization
User clicks button → Redirected to SmartThings → Logs in → Grants permissions

### Step 3: Completion
User redirected back with success message. Watch app can now use the User ID.

### Step 4: Normal Usage
```
GET /json.php?user_id=YOUR_CHOSEN_ID
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

### OAuth User Not Authorized Yet
```json
{
  "error_message": "User not authorized. Please complete setup first.",
  "error_code": 401,
  "setup_url": "/json.php?setup=1&user_id=john_smith_123"
}
```

---

## For Garmin Watch Developers

### Sample Code for Both Methods

```javascript
// Method 1: Personal Token
var url1 = "https://yourserver.com/tests/json.php?token=" + userToken;

// Method 2: OAuth User ID  
var url2 = "https://yourserver.com/tests/json.php?user_id=" + userId;

// Same request handling for both methods
Toybox.Communications.makeWebRequest(url, null, options, method(:onReceive));
```

### User Setup Instructions

For **Method 1** users: Direct them to SmartThings token page
For **Method 2** users: Direct them to your setup URL

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
- ❌ Requires one-time web browser setup

Both methods provide the same functionality - users can choose based on their preference!
