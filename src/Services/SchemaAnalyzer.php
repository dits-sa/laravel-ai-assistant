<?php

namespace LaravelAIAssistant\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;

class SchemaAnalyzer
{
    private array $models = [];
    private array $tables = [];
    private array $relationships = [];

    /**
     * Analyze the entire Laravel application schema.
     */
    public function analyzeApplication(): array
    {
        $cacheKey = 'ai_assistant_schema_analysis';
        
        return Cache::remember($cacheKey, config('ai-assistant.metadata.cache_ttl', 3600), function () {
            $this->discoverModels();
            $this->analyzeTables();
            $this->analyzeRelationships();
            
            return [
                'models' => $this->models,
                'tables' => $this->tables,
                'relationships' => $this->relationships,
                'generated_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Discover all Eloquent models in the application.
     */
    private function discoverModels(): void
    {
        $modelPaths = [
            app_path('Models'),
            app_path('Models/*'),
        ];

        // Also check modules if they exist
        if (is_dir(base_path('Modules'))) {
            $moduleDirs = glob(base_path('Modules/*/app/Models'), GLOB_ONLYDIR);
            $modelPaths = array_merge($modelPaths, $moduleDirs);
        }

        foreach ($modelPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.php');
                foreach ($files as $file) {
                    $this->analyzeModelFile($file);
                }
            }
        }
    }

    /**
     * Analyze a single model file.
     */
    private function analyzeModelFile(string $filePath): void
    {
        $fileName = basename($filePath, '.php');
        $relativePath = str_replace([app_path(), base_path() . '/Modules/'], '', $filePath);
        $relativePath = str_replace(['/app/Models/', '/Models/'], '\\', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);
        
        // Try different namespace possibilities
        $possibleClasses = [
            "App\\Models\\{$fileName}",
            "App\\{$fileName}",
            "Modules\\" . str_replace('\\Models\\', '\\Models\\', $relativePath),
        ];

        foreach ($possibleClasses as $className) {
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->isSubclassOf(Model::class) && !$reflection->isAbstract()) {
                    $this->analyzeModel($className);
                    break;
                }
            }
        }
    }

    /**
     * Analyze a specific model class.
     */
    private function analyzeModel(string $modelClass): void
    {
        try {
            $model = new $modelClass;
            $reflection = new ReflectionClass($modelClass);
            
            $this->models[$modelClass] = [
                'name' => class_basename($modelClass),
                'class' => $modelClass,
                'table' => $model->getTable(),
                'fillable' => $model->getFillable(),
                'hidden' => $model->getHidden(),
                'casts' => $model->getCasts(),
                'timestamps' => $model->usesTimestamps(),
                'soft_deletes' => method_exists($model, 'getDeletedAtColumn'),
                'primary_key' => $model->getKeyName(),
                'key_type' => $model->getKeyType(),
                'incrementing' => $model->getIncrementing(),
                'relationships' => $this->getModelRelationships($reflection),
                'scopes' => $this->getModelScopes($reflection),
                'accessors' => $this->getModelAccessors($reflection),
                'mutators' => $this->getModelMutators($reflection),
                'ai_capabilities' => $this->getAICapabilities($model),
                'description' => $this->generateModelDescription($model),
            ];
        } catch (\Exception $e) {
            // Skip models that can't be instantiated
            \Log::warning("Could not analyze model {$modelClass}: " . $e->getMessage());
        }
    }

    /**
     * Get model relationships.
     */
    private function getModelRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $returnType = $method->getReturnType();
            
            $returnTypeName = null;
            if ($returnType) {
                if ($returnType instanceof \ReflectionNamedType) {
                    $returnTypeName = $returnType->getName();
                } elseif ($returnType instanceof \ReflectionUnionType) {
                    $types = $returnType->getTypes();
                    $returnTypeName = !empty($types) ? $types[0]->getName() : 'mixed';
                } elseif ($returnType instanceof \ReflectionIntersectionType) {
                    $types = $returnType->getTypes();
                    $returnTypeName = !empty($types) ? $types[0]->getName() : 'mixed';
                }
            }
            
            if ($returnTypeName && 
                (str_contains($returnTypeName, 'Relation') || 
                 str_contains($returnTypeName, 'BelongsTo') ||
                 str_contains($returnTypeName, 'HasMany') ||
                 str_contains($returnTypeName, 'HasOne') ||
                 str_contains($returnTypeName, 'BelongsToMany') ||
                 str_contains($returnTypeName, 'MorphTo') ||
                 str_contains($returnTypeName, 'MorphMany'))) {
                
                $relationships[$methodName] = [
                    'type' => $this->getRelationshipType($returnTypeName),
                    'return_type' => $returnTypeName,
                    'parameters' => $this->getMethodParameters($method)
                ];
            }
        }
        
        return $relationships;
    }

    /**
     * Get model scopes.
     */
    private function getModelScopes(ReflectionClass $reflection): array
    {
        $scopes = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            if (str_starts_with($methodName, 'scope')) {
                $scopeName = Str::camel(substr($methodName, 5));
                $scopes[$scopeName] = [
                    'method' => $methodName,
                    'parameters' => $this->getMethodParameters($method)
                ];
            }
        }
        
        return $scopes;
    }

    /**
     * Get model accessors.
     */
    private function getModelAccessors(ReflectionClass $reflection): array
    {
        $accessors = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            if (str_starts_with($methodName, 'get') && str_ends_with($methodName, 'Attribute')) {
                $attributeName = Str::snake(substr($methodName, 3, -9));
                $accessors[$attributeName] = [
                    'method' => $methodName,
                    'parameters' => $this->getMethodParameters($method)
                ];
            }
        }
        
        return $accessors;
    }

    /**
     * Get model mutators.
     */
    private function getModelMutators(ReflectionClass $reflection): array
    {
        $mutators = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            if (str_starts_with($methodName, 'set') && str_ends_with($methodName, 'Attribute')) {
                $attributeName = Str::snake(substr($methodName, 3, -9));
                $mutators[$attributeName] = [
                    'method' => $methodName,
                    'parameters' => $this->getMethodParameters($method)
                ];
            }
        }
        
        return $mutators;
    }

    /**
     * Get AI capabilities for a model.
     */
    private function getAICapabilities(Model $model): array
    {
        $capabilities = config('ai-assistant.capabilities.default', []);
        $modelName = class_basename($model);
        $perModelCapabilities = config("ai-assistant.capabilities.per_model.{$modelName}", []);
        
        // Merge per-model capabilities
        $capabilities = array_merge($capabilities, $perModelCapabilities);
        
        // Check if model has AI capabilities trait
        if (in_array('LaravelAIAssistant\\Traits\\AICapable', class_uses_recursive($model))) {
            $capabilities = array_merge($capabilities, [
                'has_ai_trait' => true,
                'custom_actions' => $this->getCustomAIActions($model)
            ]);
        }
        
        return $capabilities;
    }

    /**
     * Get custom AI actions from the model.
     */
    private function getCustomAIActions(Model $model): array
    {
        $actions = [];
        $reflection = new ReflectionClass($model);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            if (str_starts_with($methodName, 'ai')) {
                $actionName = Str::snake(substr($methodName, 2));
                $actions[$actionName] = [
                    'method' => $methodName,
                    'parameters' => $this->getMethodParameters($method)
                ];
            }
        }
        
        return $actions;
    }

    /**
     * Analyze database tables.
     */
    private function analyzeTables(): void
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $this->tables[$tableName] = $this->getTableStructure($tableName);
            }
        } catch (\Exception $e) {
            \Log::warning("Could not analyze database tables: " . $e->getMessage());
        }
    }

    /**
     * Get table structure.
     */
    private function getTableStructure(string $tableName): array
    {
        try {
            $columns = DB::select("DESCRIBE `{$tableName}`");
            $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
            $foreignKeys = $this->getForeignKeys($tableName);
            
            return [
                'columns' => array_map(function($column) {
                    return [
                        'name' => $column->Field,
                        'type' => $column->Type,
                        'null' => $column->Null === 'YES',
                        'key' => $column->Key,
                        'default' => $column->Default,
                        'extra' => $column->Extra,
                    ];
                }, $columns),
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
                'description' => $this->generateTableDescription($tableName, $columns)
            ];
        } catch (\Exception $e) {
            return [
                'columns' => [],
                'indexes' => [],
                'foreign_keys' => [],
                'description' => "Table '{$tableName}' (structure unavailable)"
            ];
        }
    }

    /**
     * Get foreign keys for a table.
     */
    private function getForeignKeys(string $tableName): array
    {
        try {
            $foreignKeys = DB::select("
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME,
                    CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);
            
            return $foreignKeys;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate human-readable table description.
     */
    private function generateTableDescription(string $tableName, array $columns): string
    {
        $description = "Table '{$tableName}' contains ";
        
        $columnNames = array_column($columns, 'Field');
        $hasId = in_array('id', $columnNames);
        $hasTimestamps = in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);
        $hasSoftDeletes = in_array('deleted_at', $columnNames);
        
        if ($hasId) $description .= "records with unique IDs, ";
        if ($hasTimestamps) $description .= "with creation and update timestamps, ";
        if ($hasSoftDeletes) $description .= "with soft delete capability, ";
        
        // Add specific column descriptions
        $specificColumns = array_filter($columnNames, fn($col) => !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at']));
        if (!empty($specificColumns)) {
            $description .= "including columns: " . implode(', ', $specificColumns);
        }
        
        return rtrim($description, ', ') . ".";
    }

    /**
     * Generate model description.
     */
    private function generateModelDescription(Model $model): string
    {
        $name = class_basename($model);
        $table = $model->getTable();
        $fillable = $model->getFillable();
        
        $description = "The {$name} model represents data stored in the '{$table}' table. ";
        
        if (!empty($fillable)) {
            $description .= "It has the following editable fields: " . implode(', ', $fillable) . ". ";
        }
        
        if ($model->usesTimestamps()) {
            $description .= "It automatically tracks creation and update times. ";
        }
        
        if (method_exists($model, 'getDeletedAtColumn')) {
            $description .= "It supports soft deletion (records are marked as deleted rather than removed). ";
        }
        
        return rtrim($description, '. ') . ".";
    }

    /**
     * Get relationship type from return type.
     */
    private function getRelationshipType(string $returnType): string
    {
        return match(true) {
            str_contains($returnType, 'BelongsTo') => 'belongsTo',
            str_contains($returnType, 'HasMany') => 'hasMany',
            str_contains($returnType, 'HasOne') => 'hasOne',
            str_contains($returnType, 'BelongsToMany') => 'belongsToMany',
            str_contains($returnType, 'MorphTo') => 'morphTo',
            str_contains($returnType, 'MorphMany') => 'morphMany',
            default => 'relation'
        };
    }

    /**
     * Get method parameters.
     */
    private function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = null;
            
            if ($type) {
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                } elseif ($type instanceof \ReflectionUnionType) {
                    // For union types, get the first type or create a generic description
                    $types = $type->getTypes();
                    $typeName = !empty($types) ? $types[0]->getName() : 'mixed';
                } elseif ($type instanceof \ReflectionIntersectionType) {
                    // For intersection types, get the first type or create a generic description
                    $types = $type->getTypes();
                    $typeName = !empty($types) ? $types[0]->getName() : 'mixed';
                }
            }
            
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $typeName,
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }
        
        return $parameters;
    }

    /**
     * Analyze relationships between models.
     */
    private function analyzeRelationships(): void
    {
        // This would analyze the relationships between models
        // For now, we'll leave it as an empty array
        $this->relationships = [];
    }
}
