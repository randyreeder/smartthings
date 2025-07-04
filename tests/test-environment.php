<?php
// test-environment.php - TEST ONLY! DELETE AFTER VERIFICATION!

echo "<h2>🔧 SmartThings Environment Variables Test</h2>";
echo "<p><strong>⚠️ WARNING:</strong> Delete this file after testing for security!</p>";

echo "<h3>Environment Variable Status:</h3>";
echo "<table border='1' style='border-collapse: collapse; font-family: monospace;'>";

$vars_to_check = [
    'SMARTTHINGS_CLIENT_ID' => 'SmartThings OAuth Client ID',
    'SMARTTHINGS_CLIENT_SECRET' => 'SmartThings OAuth Client Secret', 
    'SMARTTHINGS_REDIRECT_URI' => 'SmartThings OAuth Redirect URI',
    'SMARTTHINGS_TOKENS_DIR' => 'Custom Token Storage Directory (Optional)'
];

foreach ($vars_to_check as $var => $description) {
    $env_value = $_ENV[$var] ?? null;
    $server_value = $_SERVER[$var] ?? null;
    $value = $env_value ?: $server_value;
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$var</strong></td>";
    echo "<td style='padding: 8px;'>$description</td>";
    
    if ($value) {
        $display_value = (strlen($value) > 20) ? substr($value, 0, 20) . '...' : $value;
        echo "<td style='padding: 8px; color: green;'>✅ SET ($display_value)</td>";
    } else {
        echo "<td style='padding: 8px; color: red;'>❌ NOT SET</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Test fallback to config file
echo "<h3>Fallback Configuration File:</h3>";
$config_file = __DIR__ . '/../oauth_tokens.ini';
if (file_exists($config_file)) {
    echo "✅ <code>oauth_tokens.ini</code> exists at: <code>$config_file</code><br>";
    
    $config = parse_ini_file($config_file, true);
    if ($config && isset($config['oauth_app'])) {
        echo "✅ oauth_app section found in config file<br>";
        echo "✅ Client ID: " . (isset($config['oauth_app']['client_id']) ? 'SET' : 'NOT SET') . "<br>";
        echo "✅ Client Secret: " . (isset($config['oauth_app']['client_secret']) ? 'SET' : 'NOT SET') . "<br>";
        echo "✅ Redirect URI: " . (isset($config['oauth_app']['redirect_uri']) ? 'SET' : 'NOT SET') . "<br>";
    } else {
        echo "❌ oauth_app section not found in config file<br>";
    }
} else {
    echo "❌ <code>oauth_tokens.ini</code> not found at: <code>$config_file</code><br>";
}

echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>If environment variables are SET:</strong> Your server is configured correctly! 🎉</li>";
echo "<li><strong>If environment variables are NOT SET:</strong> Follow the setup guide in <code>ENVIRONMENT_VARIABLES.md</code></li>";
echo "<li><strong>🔒 IMPORTANT:</strong> Delete this test file when done: <code>rm " . basename(__FILE__) . "</code></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>🔧 Configuration Methods Summary:</h3>";
echo "<p><strong>Method 1 (Most Secure):</strong> Set system environment variables</p>";
echo "<p><strong>Method 2 (Good):</strong> Set Apache environment variables in virtual host</p>";
echo "<p><strong>Method 3 (Basic):</strong> Set environment variables in .htaccess (if supported)</p>";
echo "<p><strong>Fallback:</strong> Use oauth_tokens.ini file (less secure, but works)</p>";
?>
