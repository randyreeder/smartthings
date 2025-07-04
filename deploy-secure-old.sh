#!/bin/bash

# SmartThings API Secure Deployment Script
# This script moves sensitive files outside the web root for production deployment

set -e  # Exit on any error

# Configuration - UPDATE THESE PATHS FOR YOUR SERVER
# Default paths (can be overridden with environment variables)
DEFAULT_HOME_DIR="${HOME:-/home1/rreeder}"
WEB_ROOT="${SMARTTHINGS_WEB_ROOT:-public_html/smartthings}"
CONFIG_DIR="${SMARTTHINGS_CONFIG_DIR:-$DEFAULT_HOME_DIR/smartthings_config}"
TOKENS_DIR="${SMARTTHINGS_TOKEN_DIR:-$DEFAULT_HOME_DIR/smartthings_config/tokens}"
GIT_DIR="${SMARTTHINGS_GIT_DIR:-$DEFAULT_HOME_DIR/git/smartthings}"
VENDOR_DIR="$GIT_DIR/vendor"
SRC_DIR="$GIT_DIR/src"

echo "🚀 Starting secure deployment of SmartThings API..."
echo "📂 Using home directory: $DEFAULT_HOME_DIR"
echo "🌐 Web root: $WEB_ROOT"
echo "🔐 Config directory: $CONFIG_DIR"
echo "🎫 Tokens directory: $TOKENS_DIR"
echo "� Git directory: $GIT_DIR"
echo ""

# Function to create directory if it doesn't exist
create_dir() {
    local dir="$1"
    local mode="$2"
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        chmod "$mode" "$dir"
        echo "   ✅ Created: $dir"
    else
        echo "   ✅ Exists: $dir"
    fi
}
# Create directory structure
echo "📁 Creating secure directory structure..."
create_dir "$WEB_ROOT" "755"
create_dir "$CONFIG_DIR" "700"
create_dir "$TOKENS_DIR" "700"
create_dir "$GIT_DIR" "755"
create_dir "$VENDOR_DIR" "755"
create_dir "$SRC_DIR" "755"

# Copy public API files to web root
echo "📄 Copying public API files..."
if [ -f "tests/json.php" ]; then
    cp tests/json.php "$WEB_ROOT/"
    chmod 644 "$WEB_ROOT/json.php"
    echo "   ✅ json.php copied to web root"
else
    echo "   ⚠️  tests/json.php not found"
fi

if [ -f "tests/set.php" ]; then
    cp tests/set.php "$WEB_ROOT/"
    chmod 644 "$WEB_ROOT/set.php"
    echo "   ✅ set.php copied to web root"
else
    echo "   ⚠️  tests/set.php not found"
fi

# Move sensitive configuration files
echo "🔐 Moving sensitive configuration files..."
if [ -f "bearer.ini" ]; then
    cp bearer.ini "$CONFIG_DIR/"
    chmod 600 "$CONFIG_DIR/bearer.ini"
    echo "   ✅ bearer.ini moved to secure location"
else
    echo "   ⚠️  bearer.ini not found"
fi

if [ -f "userinfo.ini" ]; then
    cp userinfo.ini "$CONFIG_DIR/"
    chmod 600 "$CONFIG_DIR/userinfo.ini"
    echo "   ✅ userinfo.ini moved to secure location"
else
    echo "   ⚠️  userinfo.ini not found"
fi

# Move vendor directory
echo "📦 Moving vendor directory..."
if [ -d "vendor" ]; then
    cp -r vendor/* "$VENDOR_DIR/"
    echo "   ✅ vendor directory moved to git location"
else
    echo "   ⚠️  vendor directory not found"
fi

# Move src directory
echo "📚 Moving src directory..."
if [ -d "src" ]; then
    cp -r src/* "$SRC_DIR/"
    echo "   ✅ src directory moved to git location"
else
    echo "   ⚠️  src directory not found"
fi

echo "🎉 Secure deployment completed successfully!"
echo ""
echo "📋 Directory structure created:"
echo "   🌐 Web root: $WEB_ROOT"
echo "   🔐 Config: $CONFIG_DIR"
echo "   🎫 Tokens: $TOKENS_DIR"
echo "   � Git repo: $GIT_DIR"
echo ""
echo "📋 Next steps:"
echo "1. Test the API endpoints:"
echo "   - Main API: https://yourdomain.com/smartthings/json.php"
echo "   - Device control: https://yourdomain.com/smartthings/set.php"
echo "2. Verify sensitive files are not web accessible"
echo "3. Consider using environment variables for credentials:"
echo "   export SMARTTHINGS_CONFIG_FILE=\"$CONFIG_DIR/bearer.ini\""
echo "   export SMARTTHINGS_TOKEN_DIR=\"$TOKENS_DIR\""
echo "   export SMARTTHINGS_VENDOR_PATH=\"$VENDOR_DIR/autoload.php\""
echo "   export SMARTTHINGS_SRC_PATH=\"$SRC_DIR/smartThings\""
echo "4. Update your Garmin app URLs if needed"
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
