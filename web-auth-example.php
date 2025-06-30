<!DOCTYPE html>
<html>
<head>
    <title>SmartThings Device Control</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .auth-section { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .devices { margin-top: 20px; }
        .device { background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .button:hover { background: #0056b3; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>SmartThings Device Control</h1>
    
    <?php
    session_start();
    
    // Your SmartThings App Credentials (these stay on your server)
    define('CLIENT_ID', 'your_smartthings_client_id_here');
    define('CLIENT_SECRET', 'your_smartthings_client_secret_here');
    define('REDIRECT_URI', 'https://yoursite.com/smartthings-auth.php'); // Update this to your actual URL
    
    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Handle OAuth callback
    if (isset($_GET['code']) && !isset($_SESSION['access_token'])) {
        $auth_code = $_GET['code'];
        
        // Exchange authorization code for access token
        $token_data = [
            'grant_type' => 'authorization_code',
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'redirect_uri' => REDIRECT_URI,
            'code' => $auth_code
        ];
        
        $ch = curl_init('https://api.smartthings.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $tokens = json_decode($response, true);
            $_SESSION['access_token'] = $tokens['access_token'];
            $_SESSION['refresh_token'] = $tokens['refresh_token'];
            echo '<div class="success">✅ Successfully authenticated with SmartThings!</div>';
        } else {
            echo '<div class="error">❌ Authentication failed. Please try again.</div>';
        }
    }
    
    // If not authenticated, show login button
    if (!isset($_SESSION['access_token'])) {
        $auth_url = 'https://api.smartthings.com/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => CLIENT_ID,
            'redirect_uri' => REDIRECT_URI,
            'scope' => 'r:devices:* x:devices:*',
            'state' => bin2hex(random_bytes(16))
        ]);
        ?>
        
        <div class="auth-section">
            <h2>Connect Your SmartThings Account</h2>
            <p>To control your SmartThings devices, you need to connect your SmartThings account.</p>
            <p><strong>What you'll need:</strong></p>
            <ul>
                <li>✅ Your SmartThings username/email</li>
                <li>✅ Your SmartThings password</li>
            </ul>
            <p>Click the button below to securely log in through SmartThings:</p>
            <a href="<?= htmlspecialchars($auth_url) ?>" class="button">🔗 Connect SmartThings Account</a>
        </div>
        
        <?php
    } else {
        // User is authenticated, show devices
        ?>
        
        <div class="success">
            ✅ Connected to SmartThings! 
            <a href="?logout=1" style="margin-left: 20px;">Logout</a>
        </div>
        
        <h2>Your SmartThings Devices</h2>
        
        <?php
        // Fetch devices using the stored access token
        $ch = curl_init('https://api.smartthings.com/v1/devices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['access_token'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            $devices = $data['items'] ?? [];
            
            echo '<div class="devices">';
            foreach ($devices as $device) {
                $deviceId = htmlspecialchars($device['deviceId']);
                $name = htmlspecialchars($device['name']);
                $label = htmlspecialchars($device['label']);
                $type = htmlspecialchars($device['type']);
                
                echo "<div class='device'>";
                echo "<h3>$label</h3>";
                echo "<p><strong>Name:</strong> $name</p>";
                echo "<p><strong>Type:</strong> $type</p>";
                echo "<p><strong>ID:</strong> $deviceId</p>";
                
                // Add device control buttons here if needed
                echo "<button class='button' onclick=\"controlDevice('$deviceId', 'on')\">Turn On</button> ";
                echo "<button class='button' onclick=\"controlDevice('$deviceId', 'off')\">Turn Off</button>";
                
                echo "</div>";
            }
            echo '</div>';
        } else {
            echo '<div class="error">❌ Failed to fetch devices. Your session may have expired.</div>';
        }
    }
    ?>
    
    <script>
    function controlDevice(deviceId, command) {
        // You can implement device control functionality here
        alert('Would control device ' + deviceId + ' with command: ' + command);
    }
    </script>
</body>
</html>
