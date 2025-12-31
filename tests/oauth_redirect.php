<?php
// This is a simple PHP script to facilitate OAuth redirection to SmartThings from a Garmin watch to a browser
// to prevent it from opening the smartthings app instead of authenticating via OAuth in the browser.
// It accepts a 'target' parameter in the query string which is the OAuth URL to redirect to.
// The script displays a message and provides a link to continue.

// Example: oauth_redirect.php?target=https://account.smartthings.com/oauth/authorize&state=xyz

// Get the target OAuth URL from the query string
$target = isset($_GET['target']) ? $_GET['target'] : '';

// Optionally, add more logic to build the URL from other parameters (e.g., state, client_id)
// $state = isset($_GET['state']) ? $_GET['state'] : '';
// $client_id = isset($_GET['client_id']) ? $_GET['client_id'] : '';
// if ($client_id && $state) { ... }

if (!$target) {
    echo '<h2>Error: No target URL provided.</h2>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Continue to SmartThings OAuth</title>
</head>
<body>
    <h2>Continue to SmartThings OAuth</h2>
    <p>
        Open this link to authenticate with SmartThings.<br>
        <a href="<?php echo htmlspecialchars($target); ?>">click here to continue</a>.<br>
        <em>For best results, open this page in your browser.</em>
    </p>
</body>
</html>
