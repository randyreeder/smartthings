<?php

// Security: Prevent direct access from outside tests directory
if (basename(__DIR__) !== 'tests') {
    http_response_code(403);
    exit('Access denied');
}

// Security: Validate that we're not being accessed via directory traversal
$script_path = realpath($_SERVER['SCRIPT_FILENAME']);
$tests_dir = realpath(__DIR__);
if (strpos($script_path, $tests_dir) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Disable PHP warnings and deprecation notices for clean JSON output
error_reporting(E_ERROR | E_PARSE);

/*
 * SmartThings Device Control API for Garmin Watch - Multiple Authentication Methods
 * 
 * This script supports device control with two authentication methods:
 * 
 * Method 1 - Personal Access Token (Simple):
 * GET/POST /set.php?token=YOUR_TOKEN&device_id=DEVICE_ID&value=VALUE
 * 
 * Method 2 - Secure OAuth (No PAT needed):
 * GET/POST /set.php?api_key=API_KEY&device_id=DEVICE_ID&value=VALUE
 * 
 * Parameters:
 * - device_id: The SmartThings device ID to control
 * - value: The value to set (on/off for switches, 0-100 for dimmers)
 * - what: 'value' (default) or 'level' - what property to set
 * 
 * Users who don't have OAuth credentials can get them by visiting:
 * GET /json.php?setup=1
 */

// Detect environment and load the appropriate Composer autoload file
if((strpos(__DIR__, '/Users/') === 0 || strpos(__DIR__, '/home/') === 0 && !strpos(__DIR__, '/home1/')))
{
    require_once __DIR__ . '/../vendor/autoload.php'; // Local path
}
else {
    require_once __DIR__ . '/../../../git/smartthings/vendor/autoload.php'; // Production path
}

// Load OAuth app credentials - prefer environment variables for security
$client_id = $_ENV['SMARTTHINGS_CLIENT_ID'] ?? null;
$client_secret = $_ENV['SMARTTHINGS_CLIENT_SECRET'] ?? null;
$redirect_uri = $_ENV['SMARTTHINGS_REDIRECT_URI'] ?? null;

// Fallback to file-based config if environment variables not set
if (!$client_id || !$client_secret || !$redirect_uri) {
    $oauth_config = parse_ini_file(__DIR__ . '/../oauth_tokens.ini', true);
    if (!$oauth_config || !isset($oauth_config['oauth_app'])) {
        http_response_code(500);
        echo json_encode(['error_code' => 500, 'error_message' => 'OAuth configuration not found']);
        exit;
    }
    $client_id = $oauth_config['oauth_app']['client_id'];
    $client_secret = $oauth_config['oauth_app']['client_secret'];
    $redirect_uri = $oauth_config['oauth_app']['redirect_uri'];
}

define('CLIENT_ID', $client_id);
define('CLIENT_SECRET', $client_secret);
define('REDIRECT_URI', $redirect_uri);

// Authentication parameters
$user_token = $_REQUEST['token'] ?? null;
$api_key = $_REQUEST['api_key'] ?? null;

// Control parameters
$device_id = $_REQUEST['device_id'] ?? null;
$value = $_REQUEST['value'] ?? null;
$what = $_REQUEST['what'] ?? 'value'; // 'value' or 'level'

// Validate required parameters
if (!$device_id || $value === null) {
    http_response_code(400);
    echo json_encode([
        'error_code' => 400,
        'error_message' => 'Missing required parameters: device_id and value are required'
    ]);
    exit;
}

// Log control attempts for debugging
if ($user_token) {
    error_log("set.php..token:" . substr($user_token, 0, 8) . "..device_id:" . $device_id . "..value:" . $value);
} elseif ($api_key) {
    error_log("set.php..api_key:" . substr($api_key, 0, 8) . "..device_id:" . $device_id . "..value:" . $value);
}

// Authentication - simplified logic
if ($user_token) {
    // Method 1: Personal Access Token
    $smartAPI = new SmartThings\SmartThingsAPI($user_token, $user_token);
} elseif ($api_key) {
    // Method 2: OAuth with stored tokens
    $smartAPI = loadTokensByApiKey($api_key);
} else {
    // No authentication method provided
    error_log("set.php..missing token/credentials");
    http_response_code(400);
    echo json_encode([
        "error_code" => 400,
        "error_message" => "Authentication required. Use ?token=YOUR_TOKEN or ?api_key=YOUR_API_KEY",
        "help" => "For Personal Access Token: https://account.smartthings.com/tokens"
    ]);
    exit;
}

// Load stored tokens by API key
function loadTokensByApiKey($api_key) {
    $tokens_file = __DIR__ . '/../user_tokens/' . hash('sha256', $api_key) . '.json';
    
    if (!file_exists($tokens_file)) {
        http_response_code(401);
        echo json_encode([
            "error_message" => "Invalid API key. Please complete OAuth setup first.",
            "error_code" => 401,
            "setup_url" => "/json.php?setup=1"
        ]);
        exit;
    }
    
    $tokens = json_decode(file_get_contents($tokens_file), true);
    
    // Verify the API key matches (additional security check)
    if (!isset($tokens['api_key']) || $tokens['api_key'] !== $api_key) {
        http_response_code(403);
        echo json_encode([
            "error_message" => "Invalid API key.",
            "error_code" => 403,
            "help" => "Use the API key provided during OAuth setup"
        ]);
        exit;
    }
    
    return new SmartThings\SmartThingsAPI($tokens['access_token'], $tokens['refresh_token']);
}
// Get the device and perform the control action
try {
    $device = $smartAPI->getDeviceById($device_id);
} catch (Exception $e) {
    error_log("set.php..get device error.." . $e->getMessage() . ".." . $e->getCode());
    http_response_code($e->getCode());
    echo json_encode([
        "error_message" => $e->getMessage(), 
        "error_code" => $e->getCode()
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Set the device value
try {
    if($what == 'value') {
        $device->set_value($value);
    } elseif($what == 'level') {
        $device->set_level(intval($value));
    } else {
        http_response_code(400);
        echo json_encode([
            "error_message" => "Invalid 'what' parameter. Use 'value' or 'level'", 
            "error_code" => 400
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("set.php..set value error.." . $e->getMessage() . ".." . $e->getCode());
    http_response_code($e->getCode());
    echo json_encode([
        "error_message" => $e->getMessage(), 
        "error_code" => $e->getCode()
    ]);
    exit;
}

// Get updated device list to return current states
try {
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    error_log("set.php..list devices error.." . $e->getMessage() . ".." . $e->getCode());
    http_response_code($e->getCode());
    echo json_encode([
        "error_message" => $e->getMessage(), 
        "error_code" => $e->getCode()
    ]);
    exit;
}

// Build response with updated device states
$devices_array = array();
if(count($devices) > 0) {
    foreach ($devices as $device) {
        if(method_exists($device, 'get_value')) {
            $device_details = array(
                'id' => $device->info()->deviceId,
                'name' => $device->info()->name,
                'label' => $device->info()->label,
                'type' => $device->info()->type,
                'value' => $device->get_value()
            );
            if(method_exists($device, 'get_level')) {
                $device_details['level'] = $device->get_level();
            }
            $devices_array[] = $device_details;
        }
    }
}

echo json_encode([
    "error_code" => 200, 
    "error_message" => "", 
    "message" => "Device $device_id $what set to $value",
    "devices" => $devices_array
], JSON_PRETTY_PRINT);
