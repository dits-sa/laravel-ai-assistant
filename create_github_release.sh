#!/bin/bash

# Laravel AI Assistant - Create GitHub Release
# This script helps you create a GitHub release for the package

echo "🚀 Laravel AI Assistant - GitHub Release Creator"
echo "==============================================="
echo ""

# Check if we're in the package directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from the package directory"
    echo "cd packages/laravel-ai-assistant"
    exit 1
fi

echo "✅ Package directory detected"
echo ""

# Get current version from composer.json
CURRENT_VERSION=$(grep '"version"' composer.json | sed 's/.*"version": *"\([^"]*\)".*/\1/')
if [ -z "$CURRENT_VERSION" ]; then
    CURRENT_VERSION="1.0.0"
fi

echo "📦 Current version: $CURRENT_VERSION"
echo ""

# Get release notes
echo "📝 Release Notes:"
echo "================="
echo ""
echo "## What's New in v$CURRENT_VERSION"
echo ""
echo "### ✨ Features"
echo "- Dynamic schema analysis for Laravel models"
echo "- AI metadata generation with comprehensive descriptions"
echo "- Dynamic API endpoints for all model operations"
echo "- AICapable trait for models to enable AI capabilities"
echo "- Security middleware with rate limiting and token validation"
echo "- Conversation management with persistent chat history"
echo "- Artisan commands for package management"
echo "- React frontend components for AI chat interface"
echo "- Complete documentation and installation guides"
echo "- Support for any Laravel application structure"
echo ""
echo "### 🔧 Technical Details"
echo "- Laravel 9, 10, and 11 support"
echo "- PHP 8.1+ required"
echo "- MIT License"
echo "- Production ready with comprehensive testing"
echo ""
echo "### 📚 Documentation"
echo "- Complete README with examples"
echo "- Installation guide"
echo "- API documentation"
echo "- Frontend integration examples"
echo ""
echo "### 🚀 Installation"
echo "\`\`\`bash"
echo "composer require dits-sa/laravel-ai-assistant"
echo "php artisan ai:install"
echo "\`\`\`"
echo ""
echo "### 🔗 Links"
echo "- [GitHub Repository](https://github.com/dits-sa/laravel-ai-assistant)"
echo "- [Documentation](https://github.com/dits-sa/laravel-ai-assistant#readme)"
echo "- [Issues](https://github.com/dits-sa/laravel-ai-assistant/issues)"
echo ""

# Create release
echo "🎉 Creating GitHub release..."
echo ""

# Check if gh CLI is installed
if command -v gh &> /dev/null; then
    echo "Using GitHub CLI to create release..."
    gh release create "v$CURRENT_VERSION" \
        --title "Laravel AI Assistant v$CURRENT_VERSION" \
        --notes-file <(cat << 'EOF'
## What's New in v1.0.0

### ✨ Features
- Dynamic schema analysis for Laravel models
- AI metadata generation with comprehensive descriptions
- Dynamic API endpoints for all model operations
- AICapable trait for models to enable AI capabilities
- Security middleware with rate limiting and token validation
- Conversation management with persistent chat history
- Artisan commands for package management
- React frontend components for AI chat interface
- Complete documentation and installation guides
- Support for any Laravel application structure

### 🔧 Technical Details
- Laravel 9, 10, and 11 support
- PHP 8.1+ required
- MIT License
- Production ready with comprehensive testing

### 📚 Documentation
- Complete README with examples
- Installation guide
- API documentation
- Frontend integration examples

### 🚀 Installation
```bash
composer require dits-sa/laravel-ai-assistant
php artisan ai:install
```

### 🔗 Links
- [GitHub Repository](https://github.com/dits-sa/laravel-ai-assistant)
- [Documentation](https://github.com/dits-sa/laravel-ai-assistant#readme)
- [Issues](https://github.com/dits-sa/laravel-ai-assistant/issues)
EOF
)
    
    if [ $? -eq 0 ]; then
        echo "✅ GitHub release created successfully!"
        echo "🔗 View release: https://github.com/dits-sa/laravel-ai-assistant/releases"
    else
        echo "❌ Failed to create release with GitHub CLI"
        echo "Please create the release manually at: https://github.com/dits-sa/laravel-ai-assistant/releases"
    fi
else
    echo "GitHub CLI not found. Please create the release manually:"
    echo ""
    echo "1. Go to: https://github.com/dits-sa/laravel-ai-assistant/releases"
    echo "2. Click 'Create a new release'"
    echo "3. Use the release notes above"
    echo "4. Tag version: v$CURRENT_VERSION"
    echo "5. Release title: Laravel AI Assistant v$CURRENT_VERSION"
    echo "6. Click 'Publish release'"
fi

echo ""
echo "🎉 Release process complete!"
echo ""
echo "Next steps:"
echo "1. Publish to Packagist: https://packagist.org"
echo "2. Share with the community"
echo "3. Monitor for issues and feedback"
echo ""
echo "Package URL: https://github.com/dits-sa/laravel-ai-assistant"
echo "Installation: composer require dits-sa/laravel-ai-assistant"
echo ""
echo "Happy coding! 🚀"
