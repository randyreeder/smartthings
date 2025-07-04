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
    'SMARTTHINGS_CONFIG_FILE' => 'Custom Config File Path (Optional)',
    'SMARTTHINGS_TOKEN_DIR' => 'Custom Token Storage Directory (Optional)',
    'SMARTTHINGS_VENDOR_PATH' => 'Custom Vendor Autoload Path (Optional)',
    'SMARTTHINGS_SRC_PATH' => 'Custom Source Code Path (Optional)'
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

// Test production path detection
$is_production = !((strpos(__DIR__, '/Users/') === 0 || strpos(__DIR__, '/home/') === 0 && !strpos(__DIR__, '/home1/')));

if ($is_production) {
    $home_dir = getenv('HOME') ?: '/home1/rreeder';
    $config_file = getenv('SMARTTHINGS_CONFIG_FILE') ?: $home_dir . '/smartthings_config/bearer.ini';
} else {
    $config_file = __DIR__ . '/../bearer.ini';
}

echo "<p><strong>Production mode:</strong> " . ($is_production ? "✅ YES" : "❌ NO (development)") . "</p>";
if ($is_production) {
    echo "<p><strong>Home directory:</strong> $home_dir</p>";
}

if (file_exists($config_file)) {
    echo "✅ <code>bearer.ini</code> exists at: <code>$config_file</code><br>";
    
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
    echo "❌ <code>bearer.ini</code> not found at: <code>$config_file</code><br>";
    echo "<p><em>This is normal if you haven't run the deployment script yet.</em></p>";
}

echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>If environment variables are SET:</strong> Your server is configured correctly! 🎉</li>";
echo "<li><strong>If environment variables are NOT SET:</strong> Follow the setup guide in <code>ENVIRONMENT_VARIABLES.md</code></li>";
echo "<li><strong>To deploy securely:</strong> Run <code>./deploy-secure.sh</code> to move files outside web root</li>";
echo "<li><strong>🔒 IMPORTANT:</strong> Delete this test file when done: <code>rm " . basename(__FILE__) . "</code></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>🔧 Configuration Methods Summary:</h3>";
echo "<p><strong>Method 1 (Most Secure):</strong> Set system environment variables</p>";
echo "<p><strong>Method 2 (Good):</strong> Set Apache environment variables in virtual host</p>";
echo "<p><strong>Method 3 (Basic):</strong> Set environment variables in .htaccess (if supported)</p>";
echo "<p><strong>Fallback:</strong> Use bearer.ini file outside web root (deploy-secure.sh does this)</p>";
echo "<p><strong>Directory Structure:</strong> ~/smartthings_config/ and ~/smartthings_app/ (outside web root)</p>";
?>
