
<?php

// Security: Prevent direct web access to this script if accessed as a file
if (basename($_SERVER['SCRIPT_NAME']) !== basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied');
}

// Disable PHP warnings and deprecation notices for clean JSON output
error_reporting(E_ERROR | E_PARSE);

// Ensure tokens_dir is set before any usage
$is_production = !(strpos(__DIR__, '/Users/') === 0);
if ($is_production) {
    $www_dir = '/var/www';
    $tokens_dir = getenv('SMARTTHINGS_TOKEN_DIR') ?: $www_dir . '/smartthings_config/tokens';
} else {
    $tokens_dir = __DIR__ . '/../user_tokens';
}
$tokens_dir = $_ENV['SMARTTHINGS_TOKENS_DIR'] ?? $_SERVER['SMARTTHINGS_TOKENS_DIR'] ?? $tokens_dir;

// New: Retrieve stored OAuth URL by session ID (must run before any authentication logic)
if (isset($_GET['get_auth_url']) && $_GET['get_auth_url'] == '1') {
    $session_id = $_GET['session'] ?? null;
    if (!$session_id) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Missing required parameters"]);
        exit;
    }
    $session_file = $tokens_dir . '/session_' . hash('sha256', $session_id) . '.json';
    if (!file_exists($session_file)) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Session not found or expired"]);
        exit;
    }
    $session_data = json_decode(file_get_contents($session_file), true);
    if (isset($session_data['auth_url']) && !empty($session_data['auth_url'])) {
        header('Content-Type: application/json');
        echo json_encode(["auth_url" => $session_data['auth_url']]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "auth_url not found for session"]);
        exit;
    }
}

// Rate limiting protection to prevent "Too many requests" errors
function checkRateLimit() {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_file = "/tmp/smartthings_rate_limit_" . md5($client_ip);
    $current_time = time();
    $max_requests_per_minute = 15; // Allow 15 requests per minute per IP
    $max_requests_per_hour = 200;  // Allow 200 requests per hour per IP
    
    // Load existing request history
    $requests = [];
    if (file_exists($rate_limit_file)) {
        $data = json_decode(file_get_contents($rate_limit_file), true);
        $requests = $data['requests'] ?? [];
    }
    
    // Clean old requests (older than 1 hour)
    $requests = array_filter($requests, function($time) use ($current_time) {
        return $time > ($current_time - 3600); // Keep last hour
    });
    
    // Check rate limits
    $requests_last_minute = array_filter($requests, function($time) use ($current_time) {
        return $time > ($current_time - 60); // Last 60 seconds
    });
    
    if (count($requests_last_minute) >= $max_requests_per_minute) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: 60');
        echo json_encode([
            "error_code" => 429,
            "error_message" => "Rate limit exceeded. Maximum {$max_requests_per_minute} requests per minute allowed.",
            "retry_after" => 60,
            "help" => "Please reduce request frequency to avoid overloading the server."
        ]);
        exit;
    }
    
    if (count($requests) >= $max_requests_per_hour) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: 3600');
        echo json_encode([
            "error_code" => 429,
            "error_message" => "Hourly rate limit exceeded. Maximum {$max_requests_per_hour} requests per hour allowed.",
            "retry_after" => 3600,
            "help" => "Please wait before making more requests."
        ]);
        exit;
    }
    
    // Add current request to history
    $requests[] = $current_time;
    
    // Save updated request history
    file_put_contents($rate_limit_file, json_encode(['requests' => $requests]));
}

// Apply rate limiting for all requests except setup (to allow users to re-authenticate)
if (!isset($_REQUEST['setup'])) {
    checkRateLimit();
}

// SmartThings Webhook Handler for Lifecycle Events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $webhook_data = json_decode($input, true);
    
    // Handle SmartThings lifecycle events
    if ($webhook_data && isset($webhook_data['lifecycle'])) {
        switch ($webhook_data['lifecycle']) {
            case 'PING':
                // Respond to SmartThings registration ping
                header('Content-Type: application/json');
                echo json_encode([
                    'pingData' => [
                        'challenge' => $webhook_data['pingData']['challenge'] ?? ''
                    ]
                ]);
                exit;
                
            case 'CONFIGURATION':
                // Handle app configuration lifecycle
                header('Content-Type: application/json');
                echo json_encode([
                    'configurationData' => []
                ]);
                exit;
                
            case 'INSTALL':
            case 'UPDATE':
            case 'UNINSTALL':
                // Handle app installation lifecycle
                header('Content-Type: application/json');
                echo json_encode([
                    'installData' => [],
                    'updateData' => [],
                    'uninstallData' => []
                ]);
                exit;
                
            case 'EVENT':
                // Handle device events (if needed in future)
                header('Content-Type: application/json');
                echo json_encode([
                    'eventData' => []
                ]);
                exit;
                
            default:
                // Unknown lifecycle event
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode(['status' => 'received']);
                exit;
        }
    }
    
    // If not a SmartThings webhook, return error for POST requests
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

/*
 * SmartThings API for Garmin Watch - Multiple Authentication Methods
 * 
 * This script supports two authentication methods:
 * 
 * Method 1 - Personal Access Token (Simple):
 * GET /json.php?token=YOUR_PERSONAL_ACCESS_TOKEN
 * 
 * Method 2 - Secure OAuth (No PAT needed):
 * GET /json.php?api_key=SECURE_API_KEY
 * 
 * Users who don't want to create a PAT can get credentials by visiting:
 * GET /json.php?setup=1
 */

// Secure configuration: Use absolute paths outside web root for production
// Environment variables (most secure) or absolute paths outside web root
$is_production = !(strpos(__DIR__, '/Users/') === 0);

if ($is_production) {
    // Production paths (outside web root) - UPDATE THESE FOR YOUR SERVER
    $www_dir = '/var/www';
    $config_file = getenv('SMARTTHINGS_CONFIG_FILE') ?: $www_dir . '/smartthings_config/oauth_tokens.ini';
    $tokens_dir = getenv('SMARTTHINGS_TOKEN_DIR') ?: $www_dir . '/smartthings_config/tokens';
    $autoload_file = getenv('SMARTTHINGS_VENDOR_PATH') ?: $www_dir . '/lib/git/smartthings/vendor/autoload.php';
    $smartthings_src = getenv('SMARTTHINGS_SRC_PATH') ?: $www_dir . '/lib/git/smartthings/src/smartThings';
} else {
    // Local development paths (relative)
    $config_file = __DIR__ . '/../oauth_tokens.ini';
    $tokens_dir = __DIR__ . '/../user_tokens';
    $autoload_file = __DIR__ . '/../vendor/autoload.php';
    $smartthings_src = __DIR__ . '/../src/smartThings';
}

// Load Composer autoload
require_once $autoload_file;

// Load SmartThings classes if not autoloaded
if (!class_exists('SmartThings\SmartThingsAPI')) {
    require_once $smartthings_src . '/smartThingsAPI.php';
    require_once $smartthings_src . '/device_wrapper.php';
    require_once $smartthings_src . '/locations_rooms.php';
}

// Load OAuth app credentials - prefer environment variables for security
$client_id = $_ENV['SMARTTHINGS_CLIENT_ID'] ?? $_SERVER['SMARTTHINGS_CLIENT_ID'] ?? null;
$client_secret = $_ENV['SMARTTHINGS_CLIENT_SECRET'] ?? $_SERVER['SMARTTHINGS_CLIENT_SECRET'] ?? null;
$redirect_uri = $_ENV['SMARTTHINGS_REDIRECT_URI'] ?? $_SERVER['SMARTTHINGS_REDIRECT_URI'] ?? null;

// Override tokens directory from environment if specified
$tokens_dir = $_ENV['SMARTTHINGS_TOKENS_DIR'] ?? $_SERVER['SMARTTHINGS_TOKENS_DIR'] ?? $tokens_dir;

// Fallback to file-based config if environment variables not set
if (!$client_id || !$client_secret || !$redirect_uri) {
    if (file_exists($config_file)) {
        $oauth_config = parse_ini_file($config_file, true);
        if ($oauth_config && isset($oauth_config['oauth_app'])) {
            $client_id = $client_id ?: $oauth_config['oauth_app']['client_id'];
            $client_secret = $client_secret ?: $oauth_config['oauth_app']['client_secret'];
            $redirect_uri = $redirect_uri ?: $oauth_config['oauth_app']['redirect_uri'];
        }
    }
    
    // Still missing credentials?
    if (!$client_id || !$client_secret || !$redirect_uri) {
        http_response_code(500);
        echo json_encode([
            'error_code' => 500, 
            'error_message' => 'OAuth configuration not found',
            'help' => 'Set environment variables or ensure config file exists at: ' . $config_file
        ]);
        exit;
    }
}

define('CLIENT_ID', $client_id);
define('CLIENT_SECRET', $client_secret);
define('REDIRECT_URI', $redirect_uri);

// Simple token-based auth (Method 1)
$user_token = $_REQUEST['token'] ?? null;
$api_key = $_REQUEST['api_key'] ?? null;
$setup_mode = $_GET['setup'] ?? null; // Setup mode stays GET-only
$user_id = $_GET['user_id'] ?? null; // Optional for setup mode
$poll_session = $_GET['poll'] ?? null; // New: Poll for API key by session ID
$debug_mode = $_GET['debug'] ?? null; // Debug mode to show all raw device data

// Log authentication attempts for debugging
if ($user_token) {
    error_log("json.php: AUTH_METHOD=personal_token, token_prefix=" . substr($user_token, 0, 8) . "...");
} elseif ($api_key) {
    error_log("json.php: AUTH_METHOD=oauth_api_key, api_key_prefix=" . substr($api_key, 0, 8) . "...");
} elseif ($poll_session) {
    error_log("json.php: AUTH_METHOD=oauth_polling, session_id=" . substr($poll_session, 0, 16) . "...");
} elseif ($debug_mode) {
    error_log("json.php: AUTH_METHOD=debug_mode, debug_param=" . $debug_mode);
}

// Method 1: Personal Access Token
if ($user_token) {
    $smartAPI = new SmartThings\SmartThingsAPI($user_token); // Don't pass refresh token for personal tokens
    
// Method 2: OAuth setup mode
} elseif ($setup_mode) {
    // Check if this is a JSON request for session ID
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        handleSetupJSON($user_id);
        exit;
    }
    
    // If no user_id provided, generate a random one
    if (!$user_id) {
        $user_id = 'user_' . bin2hex(random_bytes(8)) . '_' . time();
    }
    handleOAuthSetup($user_id);
    
// Method 2: OAuth callback or error
} elseif ((isset($_GET['code']) && isset($_GET['state'])) || isset($_GET['error'])) {
    // If SmartThings returned an error, show it clearly
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $error_description = $_GET['error_description'] ?? '';
        $state = $_GET['state'] ?? '';
        $user_id = 'unknown_user';
        if ($state) {
            $state_data = json_decode(base64_decode($state), true);
            $user_id = $state_data['user_id'] ?? 'unknown_user';
        }
        error_log("OAuth Callback ERROR: session_id=$user_id, error=$error, description=$error_description, state=" . substr($state, 0, 20));
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
<html>
<head><title>Authorization Error</title></head>
<body style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;'>
    <h2>❌ Authorization Failed</h2>
    <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>
        <strong>Error from SmartThings:</strong><br>
        <strong>Type:</strong> " . htmlspecialchars($error) . "<br>
        <strong>Description:</strong> " . htmlspecialchars($error_description) . "<br>
        <strong>User ID:</strong> " . htmlspecialchars($user_id) . "<br>
        <strong>State:</strong> " . htmlspecialchars($state) . "<br>
    </div>
    <p>
        <a href='/tests/json.php?setup=1&user_id=" . urlencode($user_id) . "' 
           style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
           🔄 Try Again
        </a>
    </p>
</body>
</html>";
        exit;
    }
    // Extract user_id from state parameter for successful callback
    $state_data = json_decode(base64_decode($_GET['state']), true);
    $user_id = $state_data['user_id'] ?? 'unknown_user';
    handleOAuthCallback($user_id, $_GET['code']);
    
// Method 2: Poll for API key by session ID
} elseif ($poll_session) {
    handleSessionPoll($poll_session);
    
// Method 2: Use stored OAuth tokens
} elseif ($api_key) {
    $smartAPI = loadTokensByApiKey($api_key);
    
} else {
    // No authentication method provided
    error_log("json.php..missing token/credentials");
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        "error_code" => 400,
        "error_message" => "Authentication required. Use ?token=YOUR_TOKEN or ?api_key=YOUR_API_KEY",
        "devices" => [],
        "help" => "For Personal Access Token: https://account.smartthings.com/tokens"
    ]);
    exit;
}

// Handle OAuth setup
function handleOAuthSetup($user_id) {
    // Encode user_id in the state parameter to avoid redirect URI mismatch
    $state_data = json_encode(['user_id' => $user_id, 'random' => bin2hex(random_bytes(8))]);
    $encoded_state = base64_encode($state_data);
    
    $auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => CLIENT_ID,
        'redirect_uri' => REDIRECT_URI,  // No user_id parameter here
        'scope' => 'r:devices:* x:devices:*',
        'state' => $encoded_state  // user_id encoded in state instead
    ]);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
<html>
<head><title>SmartThings Authorization</title></head>
<body style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;'>
    <h2>SmartThings Authorization</h2>
    <p>Session ID: <strong>" . htmlspecialchars($user_id) . "</strong></p>
    
    <div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin: 20px 0; border-radius: 5px;'>
        <h3>🕰️ For Garmin Watch Apps:</h3>
        <p>Your watch app can automatically retrieve the API key using this Session ID!</p>
        <p><strong>Polling URL:</strong></p>
        <code style='word-break: break-all; background: #f5f5f5; padding: 5px;'>" . htmlspecialchars(REDIRECT_URI) . "?poll=" . urlencode($user_id) . "</code>
        <p style='font-size: 14px; margin-top: 10px;'>The watch app should poll this URL every 5-10 seconds until it receives the API key.</p>
    </div>
    
    <p>Click the button below to authorize access to your SmartThings devices:</p>
    <a href='" . htmlspecialchars($auth_url) . "' 
       style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
       🔗 Authorize SmartThings Access
    </a>
    <p style='margin-top: 30px; font-size: 14px; color: #666;'>
        <strong>Note:</strong> You'll receive an API key after authorization that you can use for all future API calls.
    </p>
</body>
</html>";
    exit;
}

// Handle JSON setup request for watch apps
function handleSetupJSON($user_id) {
    global $tokens_dir;
    
    // If no user_id provided, generate a random one
    if (!$user_id) {
        $user_id = 'user_' . bin2hex(random_bytes(8)) . '_' . time();
    }
    
    // Validate user_id format for security
    if (!preg_match('/^user_[a-f0-9]{16}_\d{10}$/', $user_id)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid session_id format. Must be: user_[16hex]_[timestamp]',
            'example' => 'user_a1b2c3d4e5f6g7h8_1735948800'
        ]);
        exit;
    }
    
    // Create session file immediately
    if (!is_dir($tokens_dir)) {
        mkdir($tokens_dir, 0755, true);
    }
    
    $session_file = $tokens_dir . '/session_' . hash('sha256', $user_id) . '.json';
    $session_data = [
        'user_id' => $user_id,
        'api_key' => null, // Will be filled when OAuth completes
        'created' => time(),
        'expires' => time() + 3600 // 1 hour expiry
    ];
    file_put_contents($session_file, json_encode($session_data));
    
    // OAuth Setup logging
    error_log("OAuth Setup: session_id=$user_id, expires=" . (time() + 3600) . ", timestamp=" . time());

    $auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => CLIENT_ID,
        'redirect_uri' => REDIRECT_URI,
        'scope' => 'r:devices:* x:devices:*',
        'state' => base64_encode(json_encode(['user_id' => $user_id, 'random' => bin2hex(random_bytes(8))]))
    ]);

    $session_file = $tokens_dir . '/session_' . hash('sha256', $user_id) . '.json';
    $session_data = [
        'user_id' => $user_id,
        'api_key' => null, // Will be filled when OAuth completes
        'auth_url' => $auth_url,
        'created' => time(),
        'expires' => time() + 3600 // 1 hour expiry
    ];
    file_put_contents($session_file, json_encode($session_data));

    header('Content-Type: application/json');
    echo json_encode([
        'session_id' => $user_id,
        'auth_url' => $auth_url,
        'poll_url' => REDIRECT_URI . '?poll=' . urlencode($user_id),
        'expires_in' => 3600,
        'instructions' => 'Open auth_url in browser, then poll poll_url every 5-10 seconds'
    ]);
}

// Handle OAuth callback
function handleOAuthCallback($user_id, $auth_code) {
    // OAuth Callback logging
    $state = $_GET['state'] ?? '';
    error_log("OAuth Callback: session_id=$user_id, code=" . substr($auth_code, 0, 8) . "..., state=" . substr($state, 0, 20));

    $token_data = [
        'grant_type' => 'authorization_code',
        'redirect_uri' => REDIRECT_URI,  // Exact match with what was sent
        'code' => $auth_code
    ];

    // SmartThings requires Basic Auth for client credentials
    $auth_header = 'Basic ' . base64_encode(CLIENT_ID . ':' . CLIENT_SECRET);

    $ch = curl_init('https://api.smartthings.com/oauth/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: ' . $auth_header
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Add debugging info
    error_log("OAuth Token Exchange Debug:");
    error_log("HTTP Code: " . $http_code);
    error_log("Response: " . $response);
    error_log("cURL Error: " . $curl_error);
    error_log("Request Data: " . print_r($token_data, true));

    if ($http_code === 200) {
        $tokens = json_decode($response, true);

        // OAuth Token Exchange Success logging
        error_log("Token Exchange SUCCESS: session_id=$user_id, access_token=" . substr($tokens['access_token'], 0, 8) . "..., refresh_token=" . substr($tokens['refresh_token'], 0, 8) . "...");

        $api_key = saveUserTokens($user_id, $tokens['access_token'], $tokens['refresh_token']);

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
<html>
<head><title>Authorization Complete</title></head>
<body style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;'>
    <h2>✅ Authorization Complete!</h2>
    <p>Session ID: <strong>" . htmlspecialchars($user_id) . "</strong></p>
    <p>Your SmartThings account has been successfully connected.</p>
    
    <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>
        <h3>🔐 Your Secure API Credential:</h3>
        <p><strong>API Key:</strong> <code style='word-break: break-all;'>" . htmlspecialchars($api_key) . "</code></p>
    </div>
    
    <div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin: 20px 0; border-radius: 5px;'>
        <h3>📱 For Watch Apps:</h3>
        <p>Your watch app has automatically received this API key if it was polling for updates!</p>
        <p>If not, you can manually enter the API key above into your watch app settings.</p>
    </div>
    
    <p>Your Garmin watch can now access your devices using:</p>
    <code style='background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; word-break: break-all;'>
    GET /json.php?api_key=" . htmlspecialchars($api_key) . "
    </code>
    
    <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>
        <strong>🔒 Important Security Notice:</strong><br>
        Keep your API key secret! Anyone with your API key can access your SmartThings devices.
    </div>
</body>
</html>";
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
<html>
<head><title>Authorization Error</title></head>
<body style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;'>
    <h2>❌ Authorization Failed</h2>
    <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>
        <strong>Error Details:</strong><br>
        HTTP Code: " . $http_code . "<br>
        Response: " . htmlspecialchars($response) . "<br>
        cURL Error: " . htmlspecialchars($curl_error) . "<br>
        Code: " . htmlspecialchars($auth_code) . "<br>
        User ID: " . htmlspecialchars($user_id) . "<br>
        Client ID: " . htmlspecialchars(CLIENT_ID) . "
    </div>
    <p>
        <a href='/tests/json.php?setup=1&user_id=" . urlencode($user_id) . "' 
           style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
           🔄 Try Again
        </a>
    </p>
</body>
</html>";
    }
    exit;
}

// Get OAuth client credentials from config file
function getClientCredentials() {
    global $config_file;
    
    if (!file_exists($config_file)) {
        throw new Exception("OAuth configuration file not found: " . $config_file);
    }
    
    $config = parse_ini_file($config_file);
    
    if (!isset($config['client_id']) || !isset($config['client_secret'])) {
        throw new Exception("OAuth client credentials not found in config file");
    }
    
    return [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']
    ];
}

// Get OAuth API wrapper with stored tokens
function loadTokensByApiKey($api_key) {
    global $tokens_dir;
    $tokens_file = $tokens_dir . '/' . hash('sha256', $api_key) . '.json';
    
    if (!file_exists($tokens_file)) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode([
            "error_message" => "Invalid API key. Please complete OAuth setup first.",
            "error_code" => 401,
            "devices" => [],
            "setup_url" => "/json.php?setup=1"
        ]);
        exit;
    }
    
    $tokens = json_decode(file_get_contents($tokens_file), true);
    
    // Verify the API key matches (additional security check)
    if (!isset($tokens['api_key']) || $tokens['api_key'] !== $api_key) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode([
            "error_message" => "Invalid API key.",
            "error_code" => 403,
            "devices" => [],
            "help" => "Use the API key provided during OAuth setup"
        ]);
        exit;
    }
    
    return new SmartThings\SmartThingsAPI($tokens['access_token'], $tokens['refresh_token'], getClientCredentials()['client_id'], getClientCredentials()['client_secret'], $tokens_file);
}

// Save tokens for a user with API key as primary identifier
function saveUserTokens($user_id, $access_token, $refresh_token) {
    global $tokens_dir;
    
    if (!is_dir($tokens_dir)) {
        mkdir($tokens_dir, 0755, true);
    }
    
    // Generate a secure API key for this user
    $api_key = bin2hex(random_bytes(32)); // 64-character random string
    
    // Use API key hash as filename instead of user_id hash
    $tokens_file = $tokens_dir . '/' . hash('sha256', $api_key) . '.json';
    
    $tokens = [
        'user_id' => $user_id, // Keep for reference/debugging
        'api_key' => $api_key,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'created' => time()
    ];
    
    file_put_contents($tokens_file, json_encode($tokens));
    
    // Log the new API key creation
    error_log("API Key Created: session_id=$user_id, api_key=" . substr($api_key, 0, 8) . "..., access_token=" . substr($access_token, 0, 8) . "..., refresh_token=" . substr($refresh_token, 0, 8) . "...");
    
    // Also save a session file for polling (temporary, expires in 1 hour)
    $session_file = $tokens_dir . '/session_' . hash('sha256', $user_id) . '.json';
    $session_data = [
        'user_id' => $user_id,
        'api_key' => $api_key,
        'created' => time(),
        'expires' => time() + 3600 // 1 hour expiry
    ];
    file_put_contents($session_file, json_encode($session_data));
    
    return $api_key;
}

// Handle session polling for API key retrieval
function handleSessionPoll($session_id) {
    global $tokens_dir;
    
    $session_file = $tokens_dir . '/session_' . hash('sha256', $session_id) . '.json';
    
    if (!file_exists($session_file)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'not_found',
            'message' => 'Session not found or expired',
            'session_id' => $session_id
        ]);
        exit;
    }
    
    $session_data = json_decode(file_get_contents($session_file), true);
    
    // Check if session has expired
    if (time() > $session_data['expires']) {
        unlink($session_file); // Clean up expired session
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'expired',
            'message' => 'Session expired. Please start a new setup process.',
            'session_id' => $session_id
        ]);
        exit;
    }
    
    // Check if API key is available (OAuth completed)
    if (isset($session_data['api_key']) && !empty($session_data['api_key'])) {
        // Clean up session file after successful retrieval
        unlink($session_file);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'api_key' => $session_data['api_key'],
            'message' => 'OAuth setup completed successfully',
            'session_id' => $session_id
        ]);
        exit;
    } else {
        // Still waiting for OAuth completion
        // OAuth Polling logging
        error_log("OAuth Poll: session_id=$session_id, status=pending, expires_in=" . ($session_data['expires'] - time()) . ", timestamp=" . time());
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'pending',
            'message' => 'Waiting for OAuth authorization to complete',
            'session_id' => $session_id,
            'expires_in' => $session_data['expires'] - time()
        ]);
        exit;
    }
}
try {
    // Preemptively refresh token if older than 12 hours (for OAuth/api_key usage only)
    if (isset($api_key) && $api_key) {
        $tokens_file = $tokens_dir . '/' . hash('sha256', $api_key) . '.json';
        if (file_exists($tokens_file)) {
            $tokens = json_decode(file_get_contents($tokens_file), true);
            if (isset($tokens['created']) && isset($tokens['refresh_token'])) {
                $token_age = time() - $tokens['created'];
                if ($token_age > 43200) { // 12 hours
                    $client_creds = getClientCredentials();
                    $refreshed = $smartAPI->refreshAccessToken($client_creds['client_id'], $client_creds['client_secret']);
                    if ($refreshed) {
                        error_log("json.php: PREEMPTIVE REFRESH - Token was older than 12 hours, refreshed before API call.");
                    } else {
                        error_log("json.php: PREEMPTIVE REFRESH FAILED - Could not refresh token.");
                    }
                }
            }
        }
    }
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    error_log("json.php: TOP-LEVEL CATCH BLOCK: Exception caught: " . $e->getMessage());
    // If 401 Unauthorized OR 400 Bad Request and we have refresh token, handle as refresh failure
    if (($e->getCode() === 401 || $e->getCode() === 400) && $smartAPI->getRefreshToken()) {
        $refresh_token = $smartAPI->getRefreshToken();
        $timestamp = date('Y-m-d H:i:s');
        error_log("json.php: REFRESH ATTEMPT - Time: {$timestamp}");
        error_log("json.php: REFRESH ATTEMPT - AUTH_METHOD=oauth_api_key, api_key=" . substr($api_key ?? 'N/A', 0, 8) . "...");
        error_log("json.php: REFRESH ATTEMPT - Refresh token (prefix): " . substr($refresh_token, 0, 8) . "...");
        error_log("json.php: REFRESH ATTEMPT - Refresh token (full): " . $refresh_token);
        error_log("json.php: REFRESH ATTEMPT - Original error: " . $e->getMessage());
        try {
            $client_creds = getClientCredentials();
            error_log("json.php: REFRESH ATTEMPT - Client ID: " . $client_creds['client_id']);
            $refreshed = $smartAPI->refreshAccessToken($client_creds['client_id'], $client_creds['client_secret']);
            if ($refreshed) {
                error_log("json.php: REFRESH SUCCESS - AUTH_METHOD=oauth_api_key, api_key=" . substr($api_key ?? 'N/A', 0, 8) . "..., new_access_token obtained at {$timestamp}");
                // Retry the API call with refreshed token
                $devices = $smartAPI->list_devices();
                error_log("json.php: REFRESH SUCCESS - API call succeeded with refreshed token for api_key=" . substr($api_key ?? 'N/A', 0, 8) . "...");
            } else {
                error_log("json.php: REFRESH FAILED - refreshAccessToken returned false");
                // Compose a standard error response for failed refresh
                header('HTTP/1.1 401 Unauthorized');
                $refresh_error_message = "Token refresh failed";
                // Always extract error details from the Exception message
                $exception_message = $e->getMessage();
                error_log("json.php: REFRESH FAILED - Exception message: " . $exception_message);
                $error_details = null;
                if (preg_match('/\{.*\}/', $exception_message, $matches)) {
                    $error_details = json_decode($matches[0], true);
                }
                $response = [
                    "error_message" => "Authentication expired. Please complete OAuth setup again.",
                    "error_code" => $e->getCode(),
                    "devices" => [],
                    "setup_url" => "/json.php?setup=1",
                    "debug" => "Token refresh failed: $exception_message",
                    "refresh_details" => [
                        "timestamp" => $timestamp,
                        "api_key_prefix" => substr($api_key ?? 'N/A', 0, 8),
                        "refresh_token_prefix" => substr($refresh_token, 0, 8),
                        "client_id" => $client_creds['client_id'] ?? 'N/A',
                        "error_code" => $e->getCode()
                    ]
                ];
                if ($error_details && is_array($error_details)) {
                    if (isset($error_details['error'])) {
                        $response['error'] = $error_details['error'];
                    }
                    if (isset($error_details['error_description'])) {
                        $response['error_description'] = $error_details['error_description'];
                    }
                }
                echo json_encode($response);
                exit;
            }
        } catch (Exception $refresh_error) {
            error_log("json.php: REFRESH ERROR - AUTH_METHOD=oauth_api_key, api_key=" . substr($api_key ?? 'N/A', 0, 8) . "..., Exception: " . $refresh_error->getMessage());
            error_log("json.php: REFRESH ERROR - Code: " . $refresh_error->getCode());
            error_log("json.php: REFRESH ERROR - Time: {$timestamp}");
            header('HTTP/1.1 401 Unauthorized');
            $exception_message = $refresh_error->getMessage();
            error_log("json.php: REFRESH ERROR - Exception message: " . $exception_message);
            $error_details = null;
            if (preg_match('/\{.*\}/', $exception_message, $matches)) {
                $error_details = json_decode($matches[0], true);
            }
            $response = [
                "error_message" => "Authentication expired. Please complete OAuth setup again.",
                "error_code" => $refresh_error->getCode(),
                "devices" => [],
                "setup_url" => "/json.php?setup=1",
                "debug" => "Token refresh failed: $exception_message",
                "refresh_details" => [
                    "timestamp" => $timestamp,
                    "api_key_prefix" => substr($api_key ?? 'N/A', 0, 8),
                    "refresh_token_prefix" => substr($refresh_token, 0, 8),
                    "client_id" => $client_creds['client_id'] ?? 'N/A',
                    "error_code" => $refresh_error->getCode()
                ]
            ];
            if ($error_details && is_array($error_details)) {
                if (isset($error_details['error'])) {
                    $response['error'] = $error_details['error'];
                }
                if (isset($error_details['error_description'])) {
                    $response['error_description'] = $error_details['error_description'];
                }
            }
            echo json_encode($response);
            exit;
        }
    } else {
        // Non-401 error or no refresh token available
        error_log("json.php: API_ERROR - AUTH_METHOD=" . ($api_key ? "oauth_api_key" : "personal_token") . ", key=" . substr($api_key ?? $user_token ?? 'N/A', 0, 8) . "..., error=" . $e->getMessage() . ", code=" . $e->getCode());
        header('HTTP/1.1 '.$e->getCode() . ' ' . $e->getMessage());
        echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode(), "devices" => []));
        exit;
    }
}

/*
$tv = $devices[0];
$tv->power_on();
$tv->volume(10);
*/

header('Content-Type: application/json; charset=utf-8');

// Debug mode: Show all raw device data for development purposes
if ($debug_mode) {
    error_log("json.php: DEBUG MODE - Showing all raw device data for development");
    error_log("json.php: DEBUG START - Found " . count($devices) . " total devices");
    
    // Log the full token for debug mode to help identify the user providing the data
    if ($user_token) {
        error_log("json.php: DEBUG AUTH - Personal token: " . $user_token);
    } elseif ($api_key) {
        error_log("json.php: DEBUG AUTH - OAuth API key: " . $api_key);
    }
    
    $debug_devices = array();
    if(count($devices) > 0) {
        foreach ($devices as $device) {
            // Get all available device information
            $device_info = $device->info();
            $debug_device = [
                'deviceId' => $device_info->deviceId ?? 'unknown',
                'name' => $device_info->name ?? 'unknown',
                'label' => $device_info->label ?? 'unknown', 
                'type' => $device_info->type ?? 'unknown',
                'manufacturer' => $device_info->manufacturerName ?? 'unknown',
                'model' => $device_info->model ?? 'unknown',
                'deviceTypeName' => $device_info->deviceTypeName ?? 'unknown',
                'presentationId' => $device_info->presentationId ?? 'unknown',
                'raw_info' => $device_info, // Complete raw device info
                'supported_methods' => [],
                'capabilities' => []
            ];
            
            // Check what methods are available on this device
            $methods_to_check = ['get_value', 'get_level', 'power_on', 'power_off', 'set_level', 'volume'];
            foreach ($methods_to_check as $method) {
                if (method_exists($device, $method)) {
                    $debug_device['supported_methods'][] = $method;
                }
            }
            
            // Try to get capabilities if available
            if (isset($device_info->components)) {
                foreach ($device_info->components as $component) {
                    if (isset($component->capabilities)) {
                        foreach ($component->capabilities as $capability) {
                            $debug_device['capabilities'][] = [
                                'id' => $capability->id ?? 'unknown',
                                'version' => $capability->version ?? 'unknown'
                            ];
                        }
                    }
                }
            }
            
            // Try to get current values if possible
            try {
                if (method_exists($device, 'get_value')) {
                    $debug_device['current_value'] = $device->get_value();
                }
            } catch (Exception $e) {
                $debug_device['current_value'] = 'Error: ' . $e->getMessage();
            }
            
            try {
                if (method_exists($device, 'get_level')) {
                    $debug_device['current_level'] = $device->get_level();
                }
            } catch (Exception $e) {
                $debug_device['current_level'] = 'Error: ' . $e->getMessage();
            }
            
            $debug_devices[] = $debug_device;
            
            // Log each device's information to the error log for analysis
            error_log("json.php: DEBUG DEVICE - ID: " . ($device_info->deviceId ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Name: " . ($device_info->name ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Label: " . ($device_info->label ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Type: " . ($device_info->type ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Manufacturer: " . ($device_info->manufacturerName ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Model: " . ($device_info->model ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - DeviceTypeName: " . ($device_info->deviceTypeName ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - PresentationId: " . ($device_info->presentationId ?? 'unknown'));
            error_log("json.php: DEBUG DEVICE - Capabilities: " . json_encode($debug_device['capabilities']));
            error_log("json.php: DEBUG DEVICE - SupportedMethods: " . json_encode($debug_device['supported_methods']));
            error_log("json.php: DEBUG DEVICE - FullInfo: " . json_encode($device_info));
            error_log("json.php: DEBUG DEVICE - END");
        }
    }
    
    error_log("json.php: DEBUG COMPLETE - Logged " . count($debug_devices) . " devices to error log");
    
    echo json_encode([
        "debug_mode" => true,
        "total_devices" => count($devices),
        "message" => "This is debug output showing ALL devices before filtering. Share this with the developer to add support for your unsupported devices.",
        "usage" => "Add ?debug=1 to your URL to see this output",
        "error_code" => 200,
        "error_message" => "",
        "raw_devices" => $debug_devices
    ], JSON_PRETTY_PRINT);
    exit;
}

// Normal mode: Show only supported devices
$devices_array = array();
if(count($devices) > 0)
{
    foreach ($devices as $device)
    {
        if(method_exists($device, 'get_value'))
        {
            $device_basic_data = array(
                'id' => $device->info()->deviceId,
                'name' => $device->info()->name,
                'label' => $device->info()->label,
                'type' => $device->info()->type,
                'value' => $device->get_value()
            );
            if(method_exists($device, 'get_level'))
            {
                $device_basic_data['level'] = $device->get_level();
            }
            $devices_array[] = $device_basic_data;
        }
    }
}
echo json_encode(Array("error_code" => 200, "error_message" => "", "devices" => $devices_array), JSON_PRETTY_PRINT);
