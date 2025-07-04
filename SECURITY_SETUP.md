# SmartThings API Security Configuration

## Current Structure (Less Secure)
```
/var/www/html/
├── tests/
│   ├── json.php      ← Web accessible
│   └── set.php       ← Web accessible
├── oauth_tokens.ini  ← VULNERABLE!
├── bearer.ini        ← VULNERABLE!
└── composer.json     ← VULNERABLE!
```

## Recommended Structure (More Secure)
```
/var/www/
├── config/
│   ├── oauth_tokens.ini     ← Outside web root
│   ├── bearer.ini           ← Outside web root
│   └── user_tokens/         ← Outside web root
├── html/
│   └── tests/
│       ├── json.php         ← Web accessible only
│       └── set.php          ← Web accessible only
└── vendor/                  ← Outside web root
```

## Implementation Steps

1. **Move sensitive files outside web root:**
   ```bash
   mkdir -p /var/www/config
   mv oauth_tokens.ini /var/www/config/
   mv bearer.ini /var/www/config/
   mv user_tokens/ /var/www/config/
   ```

2. **Update file paths in PHP:**
   ```php
   // Instead of: __DIR__ . '/../oauth_tokens.ini'
   $config_path = '/var/www/config/oauth_tokens.ini';
   
   // Instead of: __DIR__ . '/../user_tokens/'
   $tokens_dir = '/var/www/config/user_tokens/';
   ```

3. **Set proper permissions:**
   ```bash
   chmod 600 /var/www/config/*.ini
   chown www-data:www-data /var/www/config/
   ```
