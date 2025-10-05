# Laravel AI Assistant

A dynamic AI assistant package for Laravel applications that automatically discovers schema and provides real-time AI capabilities.

## 🚀 Features

- **Dynamic Schema Discovery**: Automatically analyzes your Laravel models and database structure
- **AI Metadata Generation**: Creates comprehensive metadata for AI consumption
- **Real-time Communication**: WebSocket support for instant AI responses
- **Tool Discovery**: Automatically creates CRUD tools for each model
- **Secure Authentication**: Token-based authentication with IP restrictions
- **Conversation Management**: Persistent chat history and context
- **Extensible**: Easy to add custom tools and capabilities

## 📦 Installation

### Method 1: Install from Packagist (Recommended)

```bash
composer require your-username/laravel-ai-assistant
```

### Method 2: Install from GitHub

```bash
composer require your-username/laravel-ai-assistant:dev-main
```

### Method 3: Install from Local Path

```bash
# Add repository
composer config repositories.local path ./packages/laravel-ai-assistant

# Install package
composer require your-username/laravel-ai-assistant:dev-main
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

The package automatically creates dynamic API endpoints:

- `GET /api/ai/metadata` - Get AI metadata
- `GET /api/ai/models/{modelName}` - List model records
- `GET /api/ai/models/{modelName}/search` - Search model records
- `POST /api/ai/models/{modelName}` - Create model record
- `PUT /api/ai/models/{modelName}` - Update model record
- `DELETE /api/ai/models/{modelName}` - Delete model record

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

### VPS Setup

1. **Deploy AI Service** to your VPS
2. **Configure WebSocket** for real-time communication
3. **Set up OpenAI** integration
4. **Connect to Laravel** API

### Frontend Integration

```tsx
import { DynamicAIChat } from '@/components/AI/DynamicAIChat';

function App() {
    const [showAI, setShowAI] = useState(false);
    
    return (
        <div>
            <button onClick={() => setShowAI(true)}>
                Open AI Assistant
            </button>
            
            <DynamicAIChat 
                isOpen={showAI} 
                onClose={() => setShowAI(false)} 
            />
        </div>
    );
}
```

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

## 🆘 Support

- GitHub Issues
- Documentation Wiki
- Community Discord
- Email Support

## 🔄 Changelog

### v1.0.0
- Initial release
- Dynamic schema discovery
- AI metadata generation
- Real-time communication
- Tool discovery
- Security features
- Conversation management

---

**Made with ❤️ for the Laravel community**
