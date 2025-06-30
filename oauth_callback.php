<?php
/**
 * SmartThings OAuth Token Generator
 * 
 * This script helps you obtain access_token and refresh_token using OAuth 2.0 flow.
 * 
 * Prerequisites:
 * 1. Create a SmartThings Developer account at https://developer.smartthings.com/
 * 2. Create a new SmartApp project
 * 3. Get your client_id and client_secret from the app settings
 * 4. Set your redirect URI in the app settings (use the URL where this script is hosted)
 */

require __DIR__ . '/vendor/autoload.php';

// Configuration - Update these with your app details
$client_id = 'your_client_id_here';
$client_secret = 'your_client_secret_here';
$redirect_uri = 'http://localhost:8080/oauth_callback.php'; // Update this to your actual URL
$scope = 'r:devices:* x:devices:*'; // Adjust scopes as needed

// Step 1: Authorization URL
if (!isset($_GET['code']) && !isset($_GET['error'])) {
    $auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => $scope,
        'state' => bin2hex(random_bytes(16)) // CSRF protection
    ]);
    
    echo "<h1>SmartThings OAuth Setup</h1>";
    echo "<p>Click the link below to authorize your application:</p>";
    echo "<a href='$auth_url' target='_blank'>Authorize SmartThings App</a>";
    echo "<br><br>";
    echo "<p><strong>Instructions:</strong></p>";
    echo "<ol>";
    echo "<li>Click the authorization link above</li>";
    echo "<li>Log in with your SmartThings credentials</li>";
    echo "<li>Grant permissions to your application</li>";
    echo "<li>You'll be redirected back here with the authorization code</li>";
    echo "<li>The script will then exchange the code for tokens</li>";
    echo "</ol>";
    exit;
}

// Step 2: Handle the callback and exchange code for tokens
if (isset($_GET['error'])) {
    echo "<h1>Authorization Error</h1>";
    echo "<p>Error: " . htmlspecialchars($_GET['error']) . "</p>";
    echo "<p>Description: " . htmlspecialchars($_GET['error_description'] ?? '') . "</p>";
    exit;
}

if (isset($_GET['code'])) {
    $authorization_code = $_GET['code'];
    
    // Exchange authorization code for access token
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
        echo "<h1>Success! OAuth Tokens Retrieved</h1>";
        echo "<h2>Your OAuth Configuration:</h2>";
        echo "<pre>";
        echo "[oauth]\n";
        echo "access_token = \"" . $body['access_token'] . "\"\n";
        echo "refresh_token = \"" . $body['refresh_token'] . "\"\n";
        echo "client_id = \"" . $client_id . "\"\n";
        echo "client_secret = \"" . $client_secret . "\"\n";
        echo "</pre>";
        
        echo "<h2>Instructions:</h2>";
        echo "<ol>";
        echo "<li>Copy the configuration above</li>";
        echo "<li>Paste it into your <code>oauth_tokens.ini</code> file</li>";
        echo "<li>Your SmartThings API should now work with OAuth authentication</li>";
        echo "</ol>";
        
        echo "<h2>Token Details:</h2>";
        echo "<ul>";
        echo "<li><strong>Access Token:</strong> " . substr($body['access_token'], 0, 20) . "... (expires in " . ($body['expires_in'] ?? 'unknown') . " seconds)</li>";
        echo "<li><strong>Refresh Token:</strong> " . substr($body['refresh_token'], 0, 20) . "... (use this to get new access tokens)</li>";
        echo "<li><strong>Token Type:</strong> " . ($body['token_type'] ?? 'Bearer') . "</li>";
        echo "<li><strong>Scope:</strong> " . ($body['scope'] ?? $scope) . "</li>";
        echo "</ul>";
        
        // Optionally save to file automatically
        $config_content = "[oauth]\n";
        $config_content .= "access_token = \"" . $body['access_token'] . "\"\n";
        $config_content .= "refresh_token = \"" . $body['refresh_token'] . "\"\n";
        $config_content .= "client_id = \"" . $client_id . "\"\n";
        $config_content .= "client_secret = \"" . $client_secret . "\"\n";
        
        if (file_put_contents(__DIR__ . '/oauth_tokens.ini', $config_content)) {
            echo "<p><strong>✅ Configuration automatically saved to oauth_tokens.ini</strong></p>";
        } else {
            echo "<p><strong>⚠️ Could not automatically save configuration. Please copy manually.</strong></p>";
        }
        
    } else {
        echo "<h1>Token Exchange Failed</h1>";
        echo "<p>Status Code: $status_code</p>";
        echo "<p>Response: <pre>" . json_encode($body, JSON_PRETTY_PRINT) . "</pre></p>";
    }
}
?>
