<?php
/**
 * Simple OAuth Token Exchange
 * 
 * This script will help you exchange an authorization code for tokens
 * when you already have the authorization code from the redirect.
 */

require __DIR__ . '/vendor/autoload.php';

echo "SmartThings OAuth Token Exchange\n";
echo "===============================\n\n";

// Load config
$config = parse_ini_file(__DIR__ . '/oauth_tokens.ini', true);
$client_id = $config['oauth_app']['client_id'];
$client_secret = $config['oauth_app']['client_secret'];
$redirect_uri = $config['oauth_app']['redirect_uri'];

echo "Current configuration:\n";
echo "Client ID: $client_id\n";
echo "Redirect URI: $redirect_uri\n\n";

echo "Authorization URL:\n";
$auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => 'r:devices:* x:devices:*',
    'state' => bin2hex(random_bytes(16))
]);
echo "$auth_url\n\n";

echo "Instructions:\n";
echo "1. Open the URL above in your browser\n";
echo "2. Authorize the application\n";
echo "3. Copy the authorization code from the redirect URL\n";
echo "4. Enter it below\n\n";

echo "Authorization Code: ";
$auth_code = trim(fgets(STDIN));

if (empty($auth_code)) {
    echo "No authorization code provided.\n";
    exit(1);
}

// Exchange code for tokens
$client = new \GuzzleHttp\Client([
    'timeout' => 30.0,
    'http_errors' => false
]);

$response = $client->request('POST', 'https://api.smartthings.com/oauth/token', [
    'form_params' => [
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'code' => $auth_code
    ]
]);

$status_code = $response->getStatusCode();
$body = json_decode($response->getBody()->getContents(), true);

echo "\nResponse Status: $status_code\n";
echo "Response Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n\n";

if ($status_code === 200 && isset($body['access_token'])) {
    echo "✅ Success! OAuth tokens received.\n";
    echo "\nTokens received:\n";
    echo "- Access Token: " . substr($body['access_token'], 0, 20) . "...\n";
    echo "- Refresh Token: " . substr($body['refresh_token'], 0, 20) . "...\n";
    echo "- Expires in: " . ($body['expires_in'] ?? 'unknown') . " seconds\n\n";
    
    echo "⚠️  NOTE: This script is for testing purposes.\n";
    echo "In production, tokens are stored per-user in individual files.\n";
    echo "Use the main json.php endpoint for OAuth setup:\n";
    echo "GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID\n";
    
} else {
    echo "❌ Failed to get tokens. Check the error response above.\n";
}
?>
