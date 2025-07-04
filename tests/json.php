<?php

// Security: Prevent direct web access to this script if accessed as a file
if (basename($_SERVER['SCRIPT_NAME']) !== basename(__FILE__)) {
    // This is being accessed through directory traversal or file inclusion
    http_response_code(403);
    exit('Access denied');
}

// Disable PHP warnings and deprecation notices for clean JSON output
error_reporting(E_ERROR | E_PARSE);

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
$is_production = !((strpos(__DIR__, '/Users/') === 0 || strpos(__DIR__, '/home/') === 0 && !strpos(__DIR__, '/home1/')));

if ($is_production) {
    // Production paths (outside web root) - UPDATE THESE FOR YOUR SERVER
    $home_dir = getenv('HOME') ?: '/home1/rreeder';
    $config_file = getenv('SMARTTHINGS_CONFIG_FILE') ?: $home_dir . '/smartthings_config/bearer.ini';
    $tokens_dir = getenv('SMARTTHINGS_TOKEN_DIR') ?: $home_dir . '/smartthings_config/tokens';
    $autoload_file = getenv('SMARTTHINGS_VENDOR_PATH') ?: $home_dir . '/git/smartthings/vendor/autoload.php';
    $smartthings_src = getenv('SMARTTHINGS_SRC_PATH') ?: $home_dir . '/git/smartthings/src/smartThings';
} else {
    // Local development paths (relative)
    $config_file = __DIR__ . '/../bearer.ini';
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

// Log authentication attempts for debugging
if ($user_token) {
    error_log("json.php..token:" . substr($user_token, 0, 8) . "...");
} elseif ($api_key) {
    error_log("json.php..api_key:" . substr($api_key, 0, 8) . "...");
}

// Method 1: Personal Access Token
if ($user_token) {
    $smartAPI = new SmartThings\SmartThingsAPI($user_token, $user_token);
    
// Method 2: OAuth setup mode
} elseif ($setup_mode) {
    // If no user_id provided, generate a random one
    if (!$user_id) {
        $user_id = 'user_' . bin2hex(random_bytes(8)) . '_' . time();
    }
    handleOAuthSetup($user_id);
    
// Method 2: OAuth callback
} elseif (isset($_GET['code']) && isset($_GET['state'])) {
    // Extract user_id from state parameter
    $state_data = json_decode(base64_decode($_GET['state']), true);
    $user_id = $state_data['user_id'] ?? 'unknown_user';
    handleOAuthCallback($user_id, $_GET['code']);
    
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

// Handle OAuth callback
function handleOAuthCallback($user_id, $auth_code) {
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
        $api_key = saveUserTokens($user_id, $tokens['access_token'], $tokens['refresh_token']);
        
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
<html>
<head><title>Authorization Complete</title></head>
<body style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px;'>
    <h2>✅ Authorization Complete!</h2>
    <p>User ID: <strong>" . htmlspecialchars($user_id) . "</strong></p>
    <p>Your SmartThings account has been successfully connected.</p>
    
    <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>
        <h3>🔐 Your Secure API Credential:</h3>
        <p><strong>API Key:</strong> <code style='word-break: break-all;'>" . htmlspecialchars($api_key) . "</code></p>
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

// Load stored tokens by API key
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
    
    return new SmartThings\SmartThingsAPI($tokens['access_token'], $tokens['refresh_token']);
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
    return $api_key;
}
try {
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    error_log("json.php...." . $e->getMessage() . "..." . $e->getCode());
    header('HTTP/1.1 '.$e->getCode() . ' ' . $e->getMessage());
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode(), "devices" => []));
    exit;
}

/*
$tv = $devices[0];
$tv->power_on();
$tv->volume(10);
*/

header('Content-Type: application/json; charset=utf-8');

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