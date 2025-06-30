<?php
/**
 * SmartThings OAuth Token Generator - Command Line Version
 * 
 * This script helps you obtain OAuth tokens via command line.
 * Run this script and follow the instructions.
 */

require __DIR__ . '/vendor/autoload.php';

echo "SmartThings OAuth Token Generator\n";
echo "=================================\n\n";

// Get client credentials
echo "Enter your SmartThings app credentials:\n";
echo "Client ID: ";
$client_id = trim(fgets(STDIN));

echo "Client Secret: ";
$client_secret = trim(fgets(STDIN));

echo "Redirect URI (default: http://localhost:8080/oauth_callback): ";
$redirect_uri = trim(fgets(STDIN));
if (empty($redirect_uri)) {
    $redirect_uri = 'http://localhost:8080/oauth_callback';
}

$scope = 'r:devices:* x:devices:*';
$state = bin2hex(random_bytes(16));

// Generate authorization URL
$auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => $scope,
    'state' => $state
]);

echo "\n1. Open this URL in your browser:\n";
echo "$auth_url\n\n";

echo "2. Log in with your SmartThings credentials and authorize the app\n";
echo "3. After authorization, you'll be redirected to a URL that looks like:\n";
echo "   http://localhost:8080/oauth_callback?code=AUTHORIZATION_CODE&state=...\n\n";

echo "4. Copy the AUTHORIZATION_CODE from the URL and paste it here:\n";
echo "Authorization Code: ";
$authorization_code = trim(fgets(STDIN));

if (empty($authorization_code)) {
    echo "Error: No authorization code provided.\n";
    exit(1);
}

// Exchange code for tokens
echo "\nExchanging authorization code for tokens...\n";

$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://api.smartthings.com/',
    'timeout' => 30.0,
    'http_errors' => false
]);

$response = $client->request('POST', 'oauth/token', [
    'form_params' => [
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'code' => $authorization_code
    ]
]);

$status_code = $response->getStatusCode();
$body = json_decode($response->getBody()->getContents(), true);

if ($status_code === 200 && isset($body['access_token'])) {
    echo "\n✅ Success! Tokens retrieved.\n\n";
    
    echo "\n⚠️  NOTICE: This legacy script creates a single shared token.\n";
    echo "The current implementation uses per-user tokens for better security.\n";
    echo "Consider using the main OAuth flow instead:\n";
    echo "GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID\n\n";
    
    $config_content = "[oauth_app]\n";
    $config_content .= "client_id = \"" . $client_id . "\"\n";
    $config_content .= "client_secret = \"" . $client_secret . "\"\n";
    $config_content .= "redirect_uri = \"" . $redirect_uri . "\"\n\n";
    $config_content .= "[legacy_oauth_tokens]\n";
    $config_content .= "access_token = \"" . $body['access_token'] . "\"\n";
    $config_content .= "refresh_token = \"" . $body['refresh_token'] . "\"\n";
    
    echo "OAuth Configuration:\n";
    echo "-------------------\n";
    echo $config_content;
    echo "\n";
    
    // Save to file
    $config_file = __DIR__ . '/oauth_tokens.ini';
    if (file_put_contents($config_file, $config_content)) {
        echo "✅ Configuration saved to: $config_file\n";
    } else {
        echo "⚠️ Could not save to file. Please copy the configuration above manually.\n";
    }
    
    echo "\nToken Details:\n";
    echo "- Access Token: " . substr($body['access_token'], 0, 20) . "...\n";
    echo "- Refresh Token: " . substr($body['refresh_token'], 0, 20) . "...\n";
    echo "- Expires in: " . ($body['expires_in'] ?? 'unknown') . " seconds\n";
    echo "- Token Type: " . ($body['token_type'] ?? 'Bearer') . "\n";
    
} else {
    echo "\n❌ Failed to get tokens.\n";
    echo "Status Code: $status_code\n";
    echo "Response: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
}
?>
