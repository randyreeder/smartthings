<?php

// Security: Prevent direct web access to this script if accessed as a file
if (basename($_SERVER['SCRIPT_NAME']) !== basename(__FILE__)) {
    // This is being accessed through directory traversal or file inclusion
    http_response_code(403);
    exit('Access denied');
}

// Disable PHP warnings and deprecation notices for clean JSON output
error_reporting(E_ERROR | E_PARSE);

// Log all errors to help debug issues
ini_set('log_errors', 1);
ini_set('display_errors', 0);

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
    error_log("set.php..token:" . substr($user_token, 0, 8) . "..device_id:" . $device_id . "..value:" . $value . "..what:" . $what);
} elseif ($api_key) {
    error_log("set.php..api_key:" . substr($api_key, 0, 8) . "..device_id:" . $device_id . "..value:" . $value . "..what:" . $what);
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
    global $tokens_dir;
    $tokens_file = $tokens_dir . '/' . hash('sha256', $api_key) . '.json';
    
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
    
    return new SmartThings\SmartThingsAPI($tokens['access_token'], $tokens['refresh_token'], CLIENT_ID, CLIENT_SECRET, $tokens_file);
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
    error_log("set.php..attempting to set $what to $value on device " . get_class($device));
    if($what == 'value') {
        $result = $device->set_value($value);
        error_log("set.php..set_value returned: " . ($result ? 'true' : 'false'));
        if (!$result) {
            http_response_code(400);
            echo json_encode([
                "error_message" => "Failed to set device value to '$value'. The device may not support this value or the command was rejected.",
                "error_code" => 400,
                "device_id" => $device_id,
                "attempted_value" => $value
            ]);
            exit;
        }
    } elseif($what == 'level') {
        $result = $device->set_level(intval($value));
        error_log("set.php..set_level returned: " . ($result ? 'true' : 'false'));
        if (!$result) {
            http_response_code(400);
            echo json_encode([
                "error_message" => "Failed to set device level to '$value'. The device may not support level control or the command was rejected.",
                "error_code" => 400,
                "device_id" => $device_id,
                "attempted_level" => intval($value)
            ]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "error_message" => "Invalid 'what' parameter. Use 'value' or 'level'", 
            "error_code" => 400
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("set.php..set value error.." . $e->getMessage() . ".." . $e->getCode() . ".." . $e->getTraceAsString());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "error_message" => $e->getMessage(), 
        "error_code" => $e->getCode() ?: 500
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
