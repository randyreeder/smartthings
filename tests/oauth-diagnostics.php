<!DOCTYPE html>
<html>
<head>
    <title>SmartThings OAuth Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .test-case { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .uri { background: #e9ecef; padding: 8px; border-radius: 3px; font-family: monospace; word-break: break-all; }
        .button { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 SmartThings OAuth Redirect URI Diagnostics</h1>
    
    <div class="warning">
        <strong>⚠️ Redirect URI Validation Error</strong><br>
        Your SmartThings app is rejecting the redirect URI. Let's test different possibilities to find the correct one.
    </div>
    
    <h2>Possible Redirect URIs to Test:</h2>
    
    <div class="test-case">
        <h3>Test 1: Exact Path (Most Likely)</h3>
        <div class="uri">https://reederhome.net/weather/smartthings/json.php</div>
        <a href="test-redirect.php?test=1" class="button">🧪 Test This URI</a>
    </div>
    
    <div class="test-case">
        <h3>Test 2: With tests/ subdirectory</h3>
        <div class="uri">https://reederhome.net/weather/smartthings/tests/json.php</div>
        <a href="test-redirect.php?test=2" class="button">🧪 Test This URI</a>
    </div>
    
    <div class="test-case">
        <h3>Test 3: Different file name</h3>
        <div class="uri">https://reederhome.net/weather/smartthings/json5.php</div>
        <a href="test-redirect.php?test=3" class="button">🧪 Test This URI</a>
    </div>
    
    <div class="test-case">
        <h3>Test 4: Root smartthings directory</h3>
        <div class="uri">https://reederhome.net/weather/smartthings/</div>
        <a href="test-redirect.php?test=4" class="button">🧪 Test This URI</a>
    </div>
    
    <div class="test-case">
        <h3>Test 5: Callback specific file</h3>
        <div class="uri">https://reederhome.net/weather/smartthings/oauth_callback.php</div>
        <a href="test-redirect.php?test=5" class="button">🧪 Test This URI</a>
    </div>
    
    <h2>📋 Manual Check Instructions:</h2>
    <ol>
        <li>Go to <strong>https://smartthings.developer.samsung.com/workspace</strong></li>
        <li>Find your app with Client ID: <code>8ef1188e-5685-47c9-84cf-e0498bea8c62</code></li>
        <li>Look at the <strong>"Redirect URIs"</strong> section</li>
        <li>Copy the exact URI listed there</li>
        <li>Come back and test with that exact URI</li>
    </ol>
    
    <h2>🔧 Custom URI Test:</h2>
    <form method="get" action="test-redirect.php">
        <label for="custom_uri">Enter the exact redirect URI from your SmartThings app:</label><br>
        <input type="text" id="custom_uri" name="custom_uri" 
               style="width: 100%; padding: 10px; margin: 10px 0;" 
               placeholder="https://reederhome.net/...">
        <input type="hidden" name="test" value="custom">
        <br>
        <button type="submit" class="button">🧪 Test Custom URI</button>
    </form>
</body>
</html>
