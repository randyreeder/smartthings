<?php
// OAuth redirect page that fetches the OAuth URL from the server using session ID
$target = '';
$session = isset($_GET['session']) ? $_GET['session'] : '';
$debug_info = '';

if ($session) {
    // Fetch the OAuth URL from the server using the session ID
    $serverUrl = "https://reederhome.net/weather/smartthings/json.php?get_auth_url=1&session=" . urlencode($session) . "&unlock_key=demo";
    $debug_info .= "Requesting: " . $serverUrl . "\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'OAuth Redirect Page'
        ]
    ]);
    
    $response = @file_get_contents($serverUrl, false, $context);
    $debug_info .= "Response: " . ($response ?: 'No response') . "\n";
    
    if ($response) {
        $data = @json_decode($response, true);
        $debug_info .= "Decoded JSON: " . print_r($data, true) . "\n";
        if ($data && isset($data['auth_url'])) {
            $target = $data['auth_url'];
            // Debug: Check for client_id in the auth_url
            $parsed_url = parse_url($target);
            parse_str($parsed_url['query'] ?? '', $query_params);
            if (empty($query_params['client_id'])) {
                $debug_info .= "ERROR: client_id is missing or empty in the auth_url!\n";
            } else {
                $debug_info .= "client_id found in auth_url: " . $query_params['client_id'] . "\n";
            }
        }
    }
}

if (!$target) {
    echo '<h2>Error: Unable to retrieve OAuth URL.</h2>';
    echo '<p>Session: ' . htmlspecialchars($session) . '</p>';
    echo '<pre>Debug Info:\n' . htmlspecialchars($debug_info) . '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Continue to SmartThings OAuth</title>
    <!-- Removed meta refresh for reliability -->
</head>
<body>
    <h2>Continue to SmartThings OAuth</h2>
    <p>
        You are about to authenticate with SmartThings.<br>
        <strong>If you are not redirected automatically,</strong> <a id="manual-link" href="<?php echo htmlspecialchars($target); ?>">click here to continue</a>.<br>
        <em>For best results, open this page in your browser.</em>
    </p>
    <script type="text/javascript">
        // Reliable JS redirect
        setTimeout(function() {
            window.location.href = document.getElementById('manual-link').href;
        }, 2000);
    </script>
</body>
</html>
