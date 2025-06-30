<!DOCTYPE html>
<html>
<head>
    <title>OAuth Code Tester</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 OAuth Authorization Code Tester</h1>
    
    <div class="info">
        <strong>Instructions:</strong><br>
        1. First visit the <a href="json-local-test.php?setup=1&user_id=production_test_user">OAuth setup page</a><br>
        2. Complete the SmartThings authorization<br>
        3. Copy both the 'code' and 'state' parameters from your production server URL<br>
        4. Paste them below to test the token exchange
    </div>
    
    <form method="get" action="json-local-test.php">
        <div class="form-group">
            <label for="code">Authorization Code:</label>
            <input type="text" id="code" name="code" placeholder="Paste the authorization code here..." required>
        </div>
        
        <div class="form-group">
            <label for="state">State Parameter:</label>
            <input type="text" id="state" name="state" placeholder="Paste the state parameter here..." required>
        </div>
        
        <button type="submit">🔄 Test OAuth Token Exchange</button>
    </form>
    
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <h3>Quick Links:</h3>
        <ul>
            <li><a href="json-local-test.php?setup=1&user_id=production_test_user">🔗 Start OAuth Setup</a></li>
            <li><a href="json-local-test.php?user_id=production_test_user">📱 Test API Call (after setup)</a></li>
        </ul>
    </div>
</body>
</html>
