# SmartThings API Security Configuration

## Security Objective
- ✅ **Allow PHP scripts** to access parent directories and configuration files
- ❌ **Block web browsers** from directly viewing sensitive files like `.ini`, `.json`, etc.
- ✅ **Maintain functionality** while protecting sensitive data

## Current Implementation

### Web Server Protection (.htaccess files)
```apache
# In project root (.htaccess)
<FilesMatch "\.(ini|json|log|env)$">
    Require all denied      # Block web access to config files
</FilesMatch>

# In tests/ directory (.htaccess)  
<FilesMatch "\.php$">
    Require all granted     # Allow PHP execution
</FilesMatch>
```

### What's Protected vs Accessible

**❌ Blocked from web browsers:**
```
https://yourserver.com/oauth_tokens.ini     → 403 Forbidden
https://yourserver.com/bearer.ini           → 403 Forbidden  
https://yourserver.com/user_tokens/          → 403 Forbidden
https://yourserver.com/composer.json        → 403 Forbidden
```

**✅ Accessible to PHP scripts:**
```php
// These work fine from your PHP code:
parse_ini_file(__DIR__ . '/../oauth_tokens.ini');
file_get_contents(__DIR__ . '/../user_tokens/token.json');
require_once __DIR__ . '/../vendor/autoload.php';
```

**✅ Accessible to web browsers:**
```
https://yourserver.com/tests/json.php       → Works
https://yourserver.com/tests/set.php        → Works
```
