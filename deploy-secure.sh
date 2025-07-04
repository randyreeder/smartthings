#!/bin/bash

# SmartThings API Secure Deployment Script
# This script sets up a secure deployment structure for production

set -e  # Exit on any error

# Configuration - UPDATE THESE PATHS FOR YOUR SERVER
DEFAULT_HOME_DIR="${HOME:-/home1/rreeder}"
WEB_ROOT="${SMARTTHINGS_WEB_ROOT:-$DEFAULT_HOME_DIR/public_html/weather/smartthings}"
CONFIG_DIR="${SMARTTHINGS_CONFIG_DIR:-$DEFAULT_HOME_DIR/smartthings_config}"
TOKENS_DIR="${SMARTTHINGS_TOKEN_DIR:-$DEFAULT_HOME_DIR/smartthings_config/tokens}"

echo "🚀 SmartThings API Secure Deployment"
echo "=================================="
echo "📂 Home directory: $DEFAULT_HOME_DIR"
echo "🌐 Web root: $WEB_ROOT"
echo "🔐 Config directory: $CONFIG_DIR"
echo "🎫 Tokens directory: $TOKENS_DIR"
echo ""

# Create secure directories
echo "📁 Creating secure directories..."
mkdir -p "$WEB_ROOT" "$CONFIG_DIR" "$TOKENS_DIR"
chmod 755 "$WEB_ROOT"
chmod 700 "$CONFIG_DIR" "$TOKENS_DIR"

# Copy API files to web root
echo "📄 Copying API files to web root..."
cp tests/json.php tests/set.php "$WEB_ROOT/"
chmod 644 "$WEB_ROOT"/*.php

# Copy config files if they exist
echo "🔐 Setting up configuration..."
for config_file in bearer.ini userinfo.ini; do
    if [ -f "$config_file" ]; then
        cp "$config_file" "$CONFIG_DIR/"
        chmod 600 "$CONFIG_DIR/$config_file"
        echo "   ✅ $config_file → $CONFIG_DIR/"
    fi
done

echo ""
echo "🎉 Deployment completed!"
echo ""
echo "📋 Directory structure:"
echo "   🌐 API endpoints: $WEB_ROOT"
echo "   🔐 Config files: $CONFIG_DIR"
echo "   🎫 Token storage: $TOKENS_DIR"
echo "   📦 Code/vendor: ~/git/smartthings/ (unchanged)"
echo ""
echo "📋 Next steps:"
echo "1. Test API: https://yourdomain.com/smartthings/json.php"
echo "2. Set up OAuth credentials in $CONFIG_DIR/bearer.ini"
echo "3. Update Garmin app URLs if needed"
echo ""
echo "🔐 All sensitive files are outside the web root!"
