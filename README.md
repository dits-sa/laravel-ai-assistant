# Laravel AI Assistant

[![Latest Version](https://img.shields.io/packagist/v/dits-sa/laravel-ai-assistant.svg)](https://packagist.org/packages/dits-sa/laravel-ai-assistant)
[![Total Downloads](https://img.shields.io/packagist/dt/dits-sa/laravel-ai-assistant.svg)](https://packagist.org/packages/dits-sa/laravel-ai-assistant)
[![License](https://img.shields.io/packagist/l/dits-sa/laravel-ai-assistant.svg)](https://packagist.org/packages/dits-sa/laravel-ai-assistant)
[![Laravel Version](https://img.shields.io/badge/Laravel-9%2B%7C10%2B%7C11%2B-red.svg)](https://laravel.com)

A dynamic AI assistant package for Laravel applications that automatically discovers schema and provides real-time AI capabilities. Works with any Laravel application without configuration!

## 🚀 Features

- **Dynamic Schema Discovery**: Automatically analyzes your Laravel models and database structure
- **AI Metadata Generation**: Creates comprehensive metadata for AI consumption
- **Real-time Communication**: WebSocket support for instant AI responses
- **Tool Discovery**: Automatically creates CRUD tools for each model
- **Secure Authentication**: Token-based authentication with IP restrictions
- **Conversation Management**: Persistent chat history and context
- **Extensible**: Easy to add custom tools and capabilities
- **Zero Configuration**: Works out of the box with any Laravel app
- **Production Ready**: Built-in security, validation, and error handling

## ⚡ Quick Start (5 Minutes)

### 1. Install Package
```bash
composer require dits-sa/laravel-ai-assistant
```

### 2. Install and Configure
```bash
php artisan ai:install
```

### 3. Add Trait to Your Models
```php
<?php
// app/Models/Project.php

use LaravelAIAssistant\Traits\AICapable;

class Project extends Model
{
    use AICapable;
}
```

### 4. Generate AI Metadata
```bash
php artisan ai:generate-metadata
```

### 5. Test API
```bash
# Get AI token
curl -X POST "http://your-app.com/api/ai/auth/token" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN"

# List projects
curl -X GET "http://your-app.com/api/ai/models/project" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

**That's it! Your Laravel app now has AI capabilities! 🎉**

## 📦 Installation

### Method 1: Install from Packagist (Recommended)

```bash
composer require dits-sa/laravel-ai-assistant
```

### Method 2: Install from GitHub

```bash
composer require dits-sa/laravel-ai-assistant:dev-main
```

### Method 3: Install from Local Path

```bash
# Add repository
composer config repositories.local path ./packages/laravel-ai-assistant

# Install package
composer require dits-sa/laravel-ai-assistant:dev-main
```

### 2. Install and Configure

```bash
php artisan ai:install
```

This will:
- Publish configuration files
- Run database migrations
- Generate initial AI metadata

### 3. Add Trait to Your Models

```php
<?php

use LaravelAIAssistant\Traits\AICapable;

class Project extends Model
{
    use AICapable;
    
    // Optional: Override AI methods for custom behavior
    public function aiSearch(string $query, array $fields = []): Collection
    {
        // Custom search logic
        return $this->where('name', 'like', "%{$query}%")
                   ->orWhere('description', 'like', "%{$query}%")
                   ->get();
    }
}
```

### 4. Configure Your Models

Edit `config/ai-assistant.php`:

```php
'capabilities' => [
    'per_model' => [
        'Project' => [
            'can_create' => true,
            'can_update' => true,
            'can_delete' => false,
        ],
        'User' => [
            'can_create' => false,
            'can_delete' => false,
        ],
    ]
],
```

### 5. Generate Metadata

```bash
php artisan ai:generate-metadata
```

## 🔧 Configuration

### Basic Configuration

```php
// config/ai-assistant.php

return [
    'enabled' => true,
    
    'api' => [
        'prefix' => 'api/ai',
        'middleware' => ['auth:sanctum', 'ai.security'],
        'rate_limit' => '60,1',
    ],

    'security' => [
        'token_expiry' => 24, // hours
        'max_requests_per_minute' => 60,
        'allowed_ips' => '', // comma-separated
    ],

    'models' => [
        'exclude' => ['User', 'PasswordReset', 'Migration'],
        'include_only' => [], // If specified, only these models
        'custom_descriptions' => [
            'Project' => 'Investment projects and properties',
        ]
    ],

    'capabilities' => [
        'default' => [
            'can_list' => true,
            'can_search' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ],
        'per_model' => [
            'Project' => ['can_create' => true, 'can_update' => true],
        ]
    ],
];
```

## 🛠️ Usage

### API Endpoints

The package automatically creates dynamic API endpoints for all your models:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/ai/metadata` | Get AI metadata and available tools |
| `GET` | `/api/ai/models/{modelName}` | List model records with pagination |
| `GET` | `/api/ai/models/{modelName}/search` | Search model records |
| `POST` | `/api/ai/models/{modelName}` | Create new model record |
| `PUT` | `/api/ai/models/{modelName}` | Update existing model record |
| `DELETE` | `/api/ai/models/{modelName}` | Delete model record |
| `GET` | `/api/ai/models/{modelName}/{id}` | Get single model record |
| `POST` | `/api/ai/auth/token` | Generate AI authentication token |
| `POST` | `/api/ai/auth/validate` | Validate AI token |
| `GET` | `/api/ai/conversations` | List user conversations |
| `POST` | `/api/ai/conversations` | Create new conversation |

### Dynamic Model Support

The package automatically discovers and creates API endpoints for any Laravel model:

- **User** → `/api/ai/models/user`
- **Project** → `/api/ai/models/project`
- **Order** → `/api/ai/models/order`
- **Product** → `/api/ai/models/product`
- **Any Model** → `/api/ai/models/{ModelName}`

### Authentication

Generate an AI token:

```bash
curl -X POST /api/ai/auth/token \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

Use the token for AI requests:

```bash
curl -X GET /api/ai/metadata \
  -H "X-AI-Token: YOUR_AI_TOKEN" \
  -H "Content-Type: application/json"
```

### Example API Usage

#### List Records

```bash
curl -X GET "/api/ai/models/project?limit=10&offset=0" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

#### Search Records

```bash
curl -X GET "/api/ai/models/project/search?query=investment&limit=5" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

#### Create Record

```bash
curl -X POST "/api/ai/models/project" \
  -H "X-AI-Token: YOUR_AI_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "New Project", "description": "Project description"}'
```

#### Filter Records

```bash
curl -X GET "/api/ai/models/project?filters[status]=active&filters[type]=investment" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

## 🤖 AI Integration

### Real-World Examples

#### Example 1: E-commerce Store
```bash
# List all products
curl -X GET "/api/ai/models/product?limit=20" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Search for products
curl -X GET "/api/ai/models/product/search?query=laptop&limit=10" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Create new product
curl -X POST "/api/ai/models/product" \
  -H "X-AI-Token: YOUR_AI_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "MacBook Pro", "price": 1999.99, "category": "laptops"}'

# Update product status
curl -X PUT "/api/ai/models/product" \
  -H "X-AI-Token: YOUR_AI_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"id": 123, "status": "active"}'
```

#### Example 2: Project Management
```bash
# List active projects
curl -X GET "/api/ai/models/project?filters[status]=active" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Search projects by client
curl -X GET "/api/ai/models/project/search?query=client_name&fields=name,client" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Get project with relationships
curl -X GET "/api/ai/models/project?with=client,tasks&id=123" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

#### Example 3: User Management
```bash
# List users with pagination
curl -X GET "/api/ai/models/user?limit=50&offset=0" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Search users by role
curl -X GET "/api/ai/models/user?filters[role]=admin" \
  -H "X-AI-Token: YOUR_AI_TOKEN"

# Get user profile with relationships
curl -X GET "/api/ai/models/user?with=profile,orders&id=456" \
  -H "X-AI-Token: YOUR_AI_TOKEN"
```

### Frontend Integration

#### React Component
```tsx
import React, { useState, useEffect } from 'react';
import { DynamicAIChat } from '@/components/AI/DynamicAIChat';

function App() {
    const [showAI, setShowAI] = useState(false);
    const [aiToken, setAiToken] = useState(null);
    
    useEffect(() => {
        // Get AI token when component mounts
        fetchAIToken();
    }, []);
    
    const fetchAIToken = async () => {
        try {
            const response = await fetch('/api/ai/auth/token', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                setAiToken(data.data.token);
            }
        } catch (error) {
            console.error('Failed to get AI token:', error);
        }
    };
    
    return (
        <div>
            <button 
                onClick={() => setShowAI(true)}
                className="fixed bottom-4 right-4 bg-blue-500 text-white p-3 rounded-full shadow-lg hover:bg-blue-600"
            >
                🤖 AI Assistant
            </button>
            
            <DynamicAIChat 
                isOpen={showAI} 
                onClose={() => setShowAI(false)}
                aiToken={aiToken}
            />
        </div>
    );
}
```

#### Vue.js Component
```vue
<template>
    <div>
        <button @click="showAI = true" class="ai-button">
            🤖 AI Assistant
        </button>
        
        <DynamicAIChat 
            v-if="showAI"
            :is-open="showAI"
            @close="showAI = false"
            :ai-token="aiToken"
        />
    </div>
</template>

<script>
import DynamicAIChat from '@/components/AI/DynamicAIChat.vue';

export default {
    components: {
        DynamicAIChat
    },
    data() {
        return {
            showAI: false,
            aiToken: null
        }
    },
    async mounted() {
        await this.fetchAIToken();
    },
    methods: {
        async fetchAIToken() {
            try {
                const response = await fetch('/api/ai/auth/token', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.authToken}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.aiToken = data.data.token;
                }
            } catch (error) {
                console.error('Failed to get AI token:', error);
            }
        }
    }
}
</script>
```

### VPS Setup for Real-time AI

1. **Deploy AI Service** to your VPS
2. **Configure WebSocket** for real-time communication
3. **Set up OpenAI** integration
4. **Connect to Laravel** API

See the [VPS Setup Guide](docs/vps-setup.md) for detailed instructions.

## 📊 Commands

### Generate Metadata

```bash
php artisan ai:generate-metadata
```

Options:
- `--output=path` - Custom output file path
- `--format=json|yaml` - Output format
- `--force` - Force regeneration

### Clear Cache

```bash
php artisan ai:clear-cache
```

Options:
- `--all` - Clear all AI cache
- `--metadata` - Clear only metadata cache
- `--tokens` - Clear only token cache

### Install Package

```bash
php artisan ai:install
```

Options:
- `--force` - Force installation
- `--publish-config` - Publish config only
- `--publish-migrations` - Publish migrations only

## 🔒 Security

### Token Security

- Tokens are stored in cache with expiration
- IP restrictions can be configured
- Rate limiting prevents abuse
- Tokens are validated on each request

### Input Validation

- All inputs are validated and sanitized
- SQL injection protection
- XSS protection
- CSRF protection

### Rate Limiting

- Configurable rate limits per IP
- Separate limits for different operations
- Automatic blocking of abusive requests

## 🧪 Testing

```bash
# Run package tests
composer test

# Run specific test
php artisan test --filter=AIAssistantTest
```

## 📈 Performance

### Caching

- Metadata is cached for performance
- Configurable cache TTL
- Automatic cache invalidation

### Database Optimization

- Efficient queries with proper indexing
- Eager loading for relationships
- Pagination for large datasets

### Memory Management

- Optimized for memory usage
- Garbage collection for large operations
- Connection pooling

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support & Troubleshooting

### Common Issues

#### Package Not Found
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer update

# Check if package is installed
composer show dits-sa/laravel-ai-assistant
```

#### Trait Not Found
```php
// Make sure you have the correct import
use LaravelAIAssistant\Traits\AICapable;

class YourModel extends Model
{
    use AICapable;
}
```

#### API Endpoints Not Working
```bash
# Check if routes are registered
php artisan route:list | grep ai

# Clear route cache
php artisan route:clear

# Check middleware configuration
php artisan config:show ai-assistant
```

#### Metadata Generation Fails
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Regenerate metadata
php artisan ai:clear-cache --all
php artisan ai:generate-metadata --force
```

### Debug Commands

```bash
# Check package installation
composer show dits-sa/laravel-ai-assistant

# Test schema analysis
php artisan tinker
>>> app(\LaravelAIAssistant\Services\SchemaAnalyzer::class)->analyzeApplication()

# Test metadata generation
php artisan tinker
>>> app(\LaravelAIAssistant\Services\AIMetadataGenerator::class)->generateMetadata()

# Check configuration
php artisan config:show ai-assistant
```

### Getting Help

- **GitHub Issues**: [Report bugs and request features](https://github.com/dits-sa/laravel-ai-assistant/issues)
- **Documentation**: [Complete documentation](https://github.com/dits-sa/laravel-ai-assistant#readme)
- **Email Support**: info@dits-sa.com
- **Community**: Join our Discord community

### Performance Tips

1. **Enable Caching**: Configure Redis or Memcached for better performance
2. **Optimize Queries**: Use eager loading for relationships
3. **Rate Limiting**: Configure appropriate rate limits for your use case
4. **Database Indexing**: Add indexes for frequently searched fields

## ❓ Frequently Asked Questions

### Q: Does this work with any Laravel application?
**A:** Yes! The package automatically discovers your models and database structure, so it works with any Laravel application without configuration.

### Q: Do I need to modify my existing models?
**A:** No! You only need to add the `AICapable` trait to models you want to make available to the AI. Your existing models remain unchanged.

### Q: Is this secure?
**A:** Yes! The package includes token-based authentication, rate limiting, input validation, and IP restrictions. All data is validated and sanitized.

### Q: Can I customize the AI capabilities?
**A:** Yes! You can override any AI method in your models and configure capabilities in the config file.

### Q: Does this work with Laravel 9, 10, and 11?
**A:** Yes! The package supports Laravel 9, 10, and 11 with PHP 8.1+.

### Q: Can I use this in production?
**A:** Absolutely! The package is production-ready with comprehensive testing, security features, and error handling.

### Q: How does the dynamic discovery work?
**A:** The package scans your `app/Models` directory, analyzes your database schema, and automatically creates API endpoints and AI tools for each model.

### Q: Can I add custom AI tools?
**A:** Yes! You can define custom tools in the configuration file or override methods in your models.

## 🔄 Changelog

### v1.0.0 (2024-01-15)
- ✨ **Initial Release**
- 🚀 Dynamic schema discovery for any Laravel application
- 🤖 AI metadata generation with comprehensive descriptions
- 🔌 Dynamic API endpoints for all model operations
- 🛡️ Security middleware with rate limiting and token validation
- 💬 Conversation management with persistent chat history
- 🎯 AICapable trait for easy model integration
- 📊 Artisan commands for package management
- ⚛️ React frontend components for AI chat interface
- 📚 Complete documentation and installation guides
- 🔧 Zero configuration - works out of the box
- 🏭 Production ready with comprehensive testing
- 🌍 Support for Laravel 9, 10, and 11
- 🐘 PHP 8.1+ support
- 📄 MIT License

## 🏆 Why Choose Laravel AI Assistant?

- **🚀 Zero Configuration**: Works with any Laravel app instantly
- **🔍 Dynamic Discovery**: Automatically finds and analyzes your data
- **🛡️ Production Ready**: Built-in security and error handling
- **⚡ High Performance**: Optimized for speed and memory usage
- **🔧 Extensible**: Easy to customize and extend
- **📚 Well Documented**: Comprehensive guides and examples
- **🌍 Universal**: Works with any Laravel application
- **💡 Smart**: AI-powered data understanding and manipulation

---

**Made with ❤️ by [DITS SA](https://github.com/dits-sa) for the Laravel community**

[![GitHub](https://img.shields.io/badge/GitHub-dits--sa%2Flaravel--ai--assistant-blue.svg)](https://github.com/dits-sa/laravel-ai-assistant)
[![Packagist](https://img.shields.io/badge/Packagist-dits--sa%2Flaravel--ai--assistant-orange.svg)](https://packagist.org/packages/dits-sa/laravel-ai-assistant)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
