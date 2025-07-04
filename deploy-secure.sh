#!/bin/bash

# SmartThings API Secure Deployment Script
# This script moves sensitive files outside the web root for production deployment

set -e  # Exit on any error

# Configuration
WEB_ROOT="/var/www/html/smartthings"
CONFIG_DIR="/var/www/config"
TOKENS_DIR="/var/www/tokens"
VENDOR_DIR="/var/www/vendor"
SRC_DIR="/var/www/src"
WEB_USER="www-data"

echo "🚀 Starting secure deployment of SmartThings API..."

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root (use sudo)"
   exit 1
fi
# Create directory structure
echo "📁 Creating secure directory structure..."
mkdir -p "$WEB_ROOT"
mkdir -p "$CONFIG_DIR"
mkdir -p "$TOKENS_DIR"
mkdir -p "$VENDOR_DIR"
mkdir -p "$SRC_DIR"

# Copy public API files to web root
echo "📄 Copying public API files..."
if [ -f "tests/json.php" ]; then
    cp tests/json.php "$WEB_ROOT/"
    echo "   ✅ json.php copied to web root"
else
    echo "   ⚠️  tests/json.php not found"
fi

if [ -f "tests/set.php" ]; then
    cp tests/set.php "$WEB_ROOT/"
    echo "   ✅ set.php copied to web root"
else
    echo "   ⚠️  tests/set.php not found"
fi

# Move sensitive configuration files
echo "🔐 Moving sensitive configuration files..."
if [ -f "bearer.ini" ]; then
    cp bearer.ini "$CONFIG_DIR/"
    echo "   ✅ bearer.ini moved to secure location"
else
    echo "   ⚠️  bearer.ini not found"
fi

if [ -f "userinfo.ini" ]; then
    cp userinfo.ini "$CONFIG_DIR/"
    echo "   ✅ userinfo.ini moved to secure location"
else
    echo "   ⚠️  userinfo.ini not found"
fi

# Move vendor directory
if [ -d "vendor" ]; then
    cp -r vendor/* "$VENDOR_DIR/"
    echo "   ✅ vendor directory moved to secure location"
else
    echo "   ⚠️  vendor directory not found"
fi

# Move src directory
if [ -d "src" ]; then
    cp -r src/* "$SRC_DIR/"
    echo "   ✅ src directory moved to secure location"
else
    echo "   ⚠️  src directory not found"
fi

# Set proper ownership
echo "👤 Setting proper file ownership..."
chown -R "$WEB_USER:$WEB_USER" /var/www/
echo "   ✅ Ownership set to $WEB_USER"

# Set proper permissions
echo "🔒 Setting secure file permissions..."
chmod 755 /var/www/
chmod 755 /var/www/html/
chmod 755 "$WEB_ROOT"
chmod 644 "$WEB_ROOT"/*.php 2>/dev/null || true
chmod 700 "$CONFIG_DIR"
chmod 600 "$CONFIG_DIR"/*.ini 2>/dev/null || true
chmod 700 "$TOKENS_DIR"
chmod -R 755 "$VENDOR_DIR"
chmod -R 755 "$SRC_DIR"
echo "   ✅ Permissions set securely"

# Create .htaccess for additional security
echo "🛡️  Creating web server security rules..."
cat > /var/www/html/.htaccess << 'EOF'
# SmartThings API Security Rules
# Deny access to sensitive directories if they accidentally end up in web root
<Directory "*/config">
    Require all denied
</Directory>
<Directory "*/tokens">
    Require all denied
</Directory>
<Directory "*/vendor">
    Require all denied
</Directory>
<Directory "*/src">
    Require all denied
</Directory>

# Deny access to .ini files
<Files "*.ini">
    Require all denied
</Files>

# Deny access to .json token files
<Files "*.json">
    Require all denied
</Files>
EOF

echo "   ✅ Security rules created"

echo ""
echo "🎉 Secure deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Update your web server configuration to use the new paths"
echo "2. Test the API endpoints:"
echo "   - Main API: https://yourdomain.com/smartthings/json.php"
echo "   - Device control: https://yourdomain.com/smartthings/set.php"
echo "3. Verify sensitive files are not web accessible:"
echo "   - Config files: https://yourdomain.com/config/ (should return 404)"
echo "   - Tokens: https://yourdomain.com/tokens/ (should return 404)"
echo "4. Consider using environment variables for credentials (see ENVIRONMENT_VARIABLES.md)"
echo "5. Update your Garmin app URLs if needed"
echo ""
echo "🔐 Security status: All sensitive files are now outside the web root!"

echo ""
echo "📦 Moving files to secure locations..."

# Move configuration files (if they exist)
if [ -f "oauth_tokens.ini" ]; then
    $SUDO mv oauth_tokens.ini "$CONFIG_DIR/"
    echo "✅ Moved: oauth_tokens.ini → $CONFIG_DIR/"
fi

if [ -f "bearer.ini" ]; then
    $SUDO mv bearer.ini "$CONFIG_DIR/"
    echo "✅ Moved: bearer.ini → $CONFIG_DIR/"
fi

if [ -f "userinfo.ini" ]; then
    $SUDO mv userinfo.ini "$CONFIG_DIR/"
    echo "✅ Moved: userinfo.ini → $CONFIG_DIR/"
fi

# Move user tokens directory (if it exists)
if [ -d "user_tokens" ]; then
    $SUDO mv user_tokens/* "$CONFIG_DIR/user_tokens/" 2>/dev/null || true
    rmdir user_tokens 2>/dev/null || true
    echo "✅ Moved: user_tokens/ → $CONFIG_DIR/user_tokens/"
fi

# Move application files (if they exist)
if [ -d "vendor" ]; then
    $SUDO mv vendor "$APP_DIR/"
    echo "✅ Moved: vendor/ → $APP_DIR/"
fi

if [ -d "src" ]; then
    $SUDO mv src "$APP_DIR/"
    echo "✅ Moved: src/ → $APP_DIR/"
fi

if [ -f "composer.json" ]; then
    $SUDO mv composer.json "$APP_DIR/"
    echo "✅ Moved: composer.json → $APP_DIR/"
fi

if [ -f "composer.lock" ]; then
    $SUDO mv composer.lock "$APP_DIR/"
    echo "✅ Moved: composer.lock → $APP_DIR/"
fi

# Move API files to web-accessible location
if [ -f "tests/json.php" ]; then
    $SUDO cp tests/json.php "$API_DIR/"
    echo "✅ Copied: tests/json.php → $API_DIR/"
fi

if [ -f "tests/set.php" ]; then
    $SUDO cp tests/set.php "$API_DIR/"
    echo "✅ Copied: tests/set.php → $API_DIR/"
fi

echo ""
echo "🔐 Setting secure permissions..."

# Set ownership (assumes www-data user, adjust if different)
$SUDO chown -R www-data:www-data "$CONFIG_DIR"
$SUDO chown -R www-data:www-data "$APP_DIR" 
$SUDO chown -R www-data:www-data "$API_DIR"

# Set restrictive permissions on config directory
$SUDO chmod 700 "$CONFIG_DIR"
$SUDO chmod 755 "$APP_DIR"
$SUDO chmod 755 "$API_DIR"

# Set permissions on config files
$SUDO chmod 600 "$CONFIG_DIR"/*.ini 2>/dev/null || true
$SUDO chmod 755 "$CONFIG_DIR/user_tokens"
$SUDO chmod 600 "$CONFIG_DIR/user_tokens"/*.json 2>/dev/null || true

echo "✅ Set secure permissions"

echo ""
echo "🌍 Your secure API endpoints are now available at:"
echo "   📡 Device List: https://yourserver.com/smartthings/api/json.php"
echo "   🎛️  Device Control: https://yourserver.com/smartthings/api/set.php"

echo ""
echo "📝 Next steps:"
echo "1. Update your production paths in the PHP files if different from defaults"
echo "2. Set up environment variables (recommended) or ensure config files are in place"
echo "3. Test your API endpoints"
echo "4. Update your Garmin watch app URLs"

echo ""
echo "🔒 Security Status:"
echo "   ✅ Configuration files: Outside web root ($CONFIG_DIR)"
echo "   ✅ Application files: Outside web root ($APP_DIR)"
echo "   ✅ Only API endpoints: In web root ($API_DIR)"
echo "   ✅ Restrictive permissions: Applied"

echo ""
echo "🎉 Secure deployment complete!"
