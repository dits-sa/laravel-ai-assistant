<?php

namespace LaravelAIAssistant\Services;

use Illuminate\Support\Facades\Cache;

class AIMetadataGenerator
{
    private SchemaAnalyzer $schemaAnalyzer;

    public function __construct(SchemaAnalyzer $schemaAnalyzer)
    {
        $this->schemaAnalyzer = $schemaAnalyzer;
    }

    /**
     * Generate comprehensive AI metadata for the application.
     */
    public function generateMetadata(): array
    {
        $cacheKey = 'ai_assistant_metadata';
        
        return Cache::remember($cacheKey, config('ai-assistant.metadata.cache_ttl', 3600), function () {
            $schema = $this->schemaAnalyzer->analyzeApplication();
            
            return [
                'application_info' => $this->getApplicationInfo(),
                'models' => $this->formatModelsForAI($schema['models']),
                'tables' => $this->formatTablesForAI($schema['tables']),
                'relationships' => $this->formatRelationshipsForAI($schema['relationships']),
                'available_tools' => $this->generateAvailableTools($schema['models']),
                'api_endpoints' => $this->generateAPIEndpoints($schema['models']),
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Get application information.
     */
    private function getApplicationInfo(): array
    {
        return [
            'name' => config('app.name', 'Laravel Application'),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env', 'production'),
            'timezone' => config('app.timezone', 'UTC'),
            'locale' => config('app.locale', 'en'),
            'url' => config('app.url', 'http://localhost'),
            'ai_assistant_version' => '1.0.0',
        ];
    }

    /**
     * Format models for AI consumption.
     */
    private function formatModelsForAI(array $models): array
    {
        $formatted = [];
        $excludedModels = config('ai-assistant.models.exclude', []);
        $includedOnly = config('ai-assistant.models.include_only', []);
        
        foreach ($models as $modelClass => $modelData) {
            $modelName = $modelData['name'];
            
            // Skip excluded models
            if (in_array($modelName, $excludedModels)) {
                continue;
            }
            
            // If include_only is specified, only include those models
            if (!empty($includedOnly) && !in_array($modelName, $includedOnly)) {
                continue;
            }
            
            $formatted[$modelName] = [
                'class' => $modelClass,
                'table' => $modelData['table'],
                'description' => $this->getModelDescription($modelData),
                'fields' => $this->formatFieldsForAI($modelData),
                'relationships' => $this->formatModelRelationships($modelData['relationships']),
                'capabilities' => $modelData['ai_capabilities'],
                'scopes' => $modelData['scopes'],
                'accessors' => $modelData['accessors'],
                'mutators' => $modelData['mutators'],
                'primary_key' => $modelData['primary_key'],
                'timestamps' => $modelData['timestamps'],
                'soft_deletes' => $modelData['soft_deletes'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Get model description.
     */
    private function getModelDescription(array $modelData): string
    {
        $modelName = $modelData['name'];
        $customDescription = config("ai-assistant.models.custom_descriptions.{$modelName}");
        
        if ($customDescription) {
            return $customDescription;
        }
        
        return $modelData['description'] ?? "The {$modelName} model represents data stored in the '{$modelData['table']}' table.";
    }

    /**
     * Format fields for AI consumption.
     */
    private function formatFieldsForAI(array $modelData): array
    {
        $fields = [];
        
        foreach ($modelData['fillable'] as $field) {
            $fields[$field] = [
                'name' => $field,
                'type' => $this->getFieldType($field, $modelData),
                'description' => $this->generateFieldDescription($field, $modelData),
                'required' => $this->isFieldRequired($field, $modelData),
                'unique' => $this->isFieldUnique($field, $modelData),
                'nullable' => $this->isFieldNullable($field, $modelData),
            ];
        }
        
        return $fields;
    }

    /**
     * Get field type for AI.
     */
    private function getFieldType(string $field, array $modelData): string
    {
        $cast = $modelData['casts'][$field] ?? 'string';
        
        return match($cast) {
            'integer', 'int' => 'integer',
            'float', 'double', 'decimal' => 'number',
            'boolean', 'bool' => 'boolean',
            'array', 'json' => 'array',
            'datetime', 'date' => 'string',
            'timestamp' => 'string',
            default => 'string'
        };
    }

    /**
     * Generate field description.
     */
    private function generateFieldDescription(string $field, array $modelData): string
    {
        $type = $this->getFieldType($field, $modelData);
        
        return match($type) {
            'integer' => "The {$field} field (integer value)",
            'number' => "The {$field} field (numeric value)",
            'boolean' => "The {$field} field (true/false value)",
            'array' => "The {$field} field (array of values)",
            'string' => "The {$field} field (text value)",
            default => "The {$field} field"
        };
    }

    /**
     * Check if field is required.
     */
    private function isFieldRequired(string $field, array $modelData): bool
    {
        // This would need to be implemented based on database constraints
        // For now, we'll return false
        return false;
    }

    /**
     * Check if field is unique.
     */
    private function isFieldUnique(string $field, array $modelData): bool
    {
        // This would need to be implemented based on database constraints
        // For now, we'll return false
        return false;
    }

    /**
     * Check if field is nullable.
     */
    private function isFieldNullable(string $field, array $modelData): bool
    {
        // This would need to be implemented based on database constraints
        // For now, we'll return true
        return true;
    }

    /**
     * Format model relationships for AI.
     */
    private function formatModelRelationships(array $relationships): array
    {
        $formatted = [];
        
        foreach ($relationships as $name => $relationship) {
            $formatted[$name] = [
                'name' => $name,
                'type' => $relationship['type'],
                'description' => $this->generateRelationshipDescription($name, $relationship),
                'parameters' => $relationship['parameters'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Generate relationship description.
     */
    private function generateRelationshipDescription(string $name, array $relationship): string
    {
        $type = $relationship['type'];
        
        return match($type) {
            'belongsTo' => "The {$name} relationship (belongs to another model)",
            'hasMany' => "The {$name} relationship (has many related records)",
            'hasOne' => "The {$name} relationship (has one related record)",
            'belongsToMany' => "The {$name} relationship (many-to-many with another model)",
            'morphTo' => "The {$name} relationship (polymorphic - can belong to different model types)",
            'morphMany' => "The {$name} relationship (polymorphic - has many related records of different types)",
            default => "The {$name} relationship"
        };
    }

    /**
     * Format tables for AI consumption.
     */
    private function formatTablesForAI(array $tables): array
    {
        $formatted = [];
        
        foreach ($tables as $tableName => $tableData) {
            $formatted[$tableName] = [
                'name' => $tableName,
                'description' => $tableData['description'],
                'columns' => $this->formatTableColumns($tableData['columns']),
                'indexes' => $tableData['indexes'],
                'foreign_keys' => $tableData['foreign_keys'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Format table columns for AI.
     */
    private function formatTableColumns(array $columns): array
    {
        $formatted = [];
        
        foreach ($columns as $column) {
            $formatted[$column['name']] = [
                'name' => $column['name'],
                'type' => $column['type'],
                'nullable' => $column['null'],
                'key' => $column['key'],
                'default' => $column['default'],
                'extra' => $column['extra'],
            ];
        }
        
        return $formatted;
    }

    /**
     * Format relationships for AI consumption.
     */
    private function formatRelationshipsForAI(array $relationships): array
    {
        // This would format the relationships between models
        // For now, we'll return an empty array
        return [];
    }

    /**
     * Generate available tools for AI.
     */
    private function generateAvailableTools(array $models): array
    {
        $tools = [];
        
        foreach ($models as $modelClass => $modelData) {
            $modelName = strtolower($modelData['name']);
            $capabilities = $modelData['ai_capabilities'];
            
            // List tool
            if ($capabilities['can_list'] ?? false) {
                $tools[] = [
                    'name' => "list_{$modelName}",
                    'description' => "Get a list of {$modelData['name']} records with optional filtering and pagination",
                    'category' => 'data_retrieval',
                    'model' => $modelClass,
                    'operation' => 'list',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'filters' => [
                                'type' => 'object',
                                'description' => 'Filters to apply to the query',
                                'properties' => $this->generateFilterProperties($modelData)
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of records to return (default: 15)',
                                'minimum' => 1,
                                'maximum' => 100
                            ],
                            'offset' => [
                                'type' => 'integer',
                                'description' => 'Number of records to skip (default: 0)',
                                'minimum' => 0
                            ],
                            'with' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Relationships to eager load'
                            ],
                            'order_by' => [
                                'type' => 'string',
                                'description' => 'Field to order by'
                            ],
                            'order_direction' => [
                                'type' => 'string',
                                'enum' => ['asc', 'desc'],
                                'description' => 'Order direction'
                            ]
                        ]
                    ]
                ];
            }
            
            // Search tool
            if ($capabilities['can_search'] ?? false) {
                $tools[] = [
                    'name' => "search_{$modelName}",
                    'description' => "Search {$modelData['name']} records using text search",
                    'category' => 'data_retrieval',
                    'model' => $modelClass,
                    'operation' => 'search',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Search query text'
                            ],
                            'fields' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Specific fields to search in (optional)',
                                'default' => $modelData['fillable']
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of results',
                                'default' => 10
                            ]
                        ],
                        'required' => ['query']
                    ]
                ];
            }
            
            // Create tool
            if ($capabilities['can_create'] ?? false) {
                $tools[] = [
                    'name' => "create_{$modelName}",
                    'description' => "Create a new {$modelData['name']} record",
                    'category' => 'data_creation',
                    'model' => $modelClass,
                    'operation' => 'create',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $this->generateCreateProperties($modelData),
                        'required' => $this->getRequiredFields($modelData)
                    ]
                ];
            }
            
            // Update tool
            if ($capabilities['can_update'] ?? false) {
                $tools[] = [
                    'name' => "update_{$modelName}",
                    'description' => "Update an existing {$modelData['name']} record",
                    'category' => 'data_modification',
                    'model' => $modelClass,
                    'operation' => 'update',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge([
                            'id' => [
                                'type' => 'string',
                                'description' => 'ID of the record to update'
                            ]
                        ], $this->generateCreateProperties($modelData)),
                        'required' => ['id']
                    ]
                ];
            }
            
            // Delete tool
            if ($capabilities['can_delete'] ?? false) {
                $tools[] = [
                    'name' => "delete_{$modelName}",
                    'description' => "Delete a {$modelData['name']} record",
                    'category' => 'data_deletion',
                    'model' => $modelClass,
                    'operation' => 'delete',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                                'description' => 'ID of the record to delete'
                            ]
                        ],
                        'required' => ['id']
                    ]
                ];
            }
        }
        
        // Add custom tools
        $customTools = config('ai-assistant.custom_tools', []);
        $tools = array_merge($tools, $customTools);
        
        return $tools;
    }

    /**
     * Generate filter properties for a model.
     */
    private function generateFilterProperties(array $modelData): array
    {
        $properties = [];
        
        foreach ($modelData['fillable'] as $field) {
            $properties[$field] = [
                'type' => $this->getFieldType($field, $modelData),
                'description' => "Filter by {$field}"
            ];
        }
        
        return $properties;
    }

    /**
     * Generate create properties for a model.
     */
    private function generateCreateProperties(array $modelData): array
    {
        $properties = [];
        
        foreach ($modelData['fillable'] as $field) {
            $properties[$field] = [
                'type' => $this->getFieldType($field, $modelData),
                'description' => $this->generateFieldDescription($field, $modelData),
                'required' => $this->isFieldRequired($field, $modelData)
            ];
        }
        
        return $properties;
    }

    /**
     * Get required fields for a model.
     */
    private function getRequiredFields(array $modelData): array
    {
        $required = [];
        
        foreach ($modelData['fillable'] as $field) {
            if ($this->isFieldRequired($field, $modelData)) {
                $required[] = $field;
            }
        }
        
        return $required;
    }

    /**
     * Generate API endpoints for AI.
     */
    private function generateAPIEndpoints(array $models): array
    {
        $endpoints = [];
        
        foreach ($models as $modelClass => $modelData) {
            $modelName = strtolower($modelData['name']);
            $capabilities = $modelData['ai_capabilities'];
            
            if ($capabilities['can_list'] ?? false) {
                $endpoints[] = [
                    'method' => 'GET',
                    'path' => "/api/ai/models/{$modelName}",
                    'description' => "List {$modelData['name']} records",
                    'parameters' => ['filters', 'limit', 'offset', 'with', 'order_by', 'order_direction']
                ];
            }
            
            if ($capabilities['can_search'] ?? false) {
                $endpoints[] = [
                    'method' => 'GET',
                    'path' => "/api/ai/models/{$modelName}/search",
                    'description' => "Search {$modelData['name']} records",
                    'parameters' => ['query', 'fields', 'limit']
                ];
            }
            
            if ($capabilities['can_create'] ?? false) {
                $endpoints[] = [
                    'method' => 'POST',
                    'path' => "/api/ai/models/{$modelName}",
                    'description' => "Create new {$modelData['name']} record"
                ];
            }
            
            if ($capabilities['can_update'] ?? false) {
                $endpoints[] = [
                    'method' => 'PUT',
                    'path' => "/api/ai/models/{$modelName}/{id}",
                    'description' => "Update {$modelData['name']} record"
                ];
            }
            
            if ($capabilities['can_delete'] ?? false) {
                $endpoints[] = [
                    'method' => 'DELETE',
                    'path' => "/api/ai/models/{$modelName}/{id}",
                    'description' => "Delete {$modelData['name']} record"
                ];
            }
        }
        
        return $endpoints;
    }
}
