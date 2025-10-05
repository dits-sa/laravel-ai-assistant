<?php

namespace LaravelAIAssistant\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

trait AICapable
{
    /**
     * AI search method - can be overridden in models.
     */
    public function aiSearch(string $query, array $fields = []): Collection
    {
        $searchFields = empty($fields) ? $this->getFillable() : $fields;
        $queryBuilder = static::query();
        
        foreach ($searchFields as $field) {
            $queryBuilder->orWhere($field, 'like', "%{$query}%");
        }
        
        return $queryBuilder->get();
    }

    /**
     * AI create method - can be overridden in models.
     */
    public function aiCreate(array $data): static
    {
        return static::create($data);
    }

    /**
     * AI update method - can be overridden in models.
     */
    public function aiUpdate(array $data): static
    {
        $this->update($data);
        return $this->fresh();
    }

    /**
     * AI delete method - can be overridden in models.
     */
    public function aiDelete(): bool
    {
        return $this->delete();
    }

    /**
     * AI list method - can be overridden in models.
     */
    public static function aiList(array $filters = [], int $limit = 15, int $offset = 0): Collection
    {
        $query = static::query();
        
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->offset($offset)->limit($limit)->get();
    }

    /**
     * AI show method - get a single record with relationships.
     */
    public static function aiShow($id, array $with = []): ?static
    {
        $query = static::query();
        
        if (!empty($with)) {
            $query->with($with);
        }
        
        return $query->find($id);
    }

    /**
     * Get AI-friendly description of the model.
     */
    public function getAIDescription(): string
    {
        $className = class_basename(static::class);
        $table = $this->getTable();
        
        return "The {$className} model represents data stored in the '{$table}' table.";
    }

    /**
     * Get AI-friendly field descriptions.
     */
    public function getAIFieldDescriptions(): array
    {
        $descriptions = [];
        
        foreach ($this->getFillable() as $field) {
            $descriptions[$field] = $this->getFieldDescription($field);
        }
        
        return $descriptions;
    }

    /**
     * Get description for a specific field.
     */
    private function getFieldDescription(string $field): string
    {
        $casts = $this->getCasts();
        $type = $casts[$field] ?? 'string';
        
        return match($type) {
            'integer', 'int' => "The {$field} field (integer value)",
            'float', 'double', 'decimal' => "The {$field} field (numeric value)",
            'boolean', 'bool' => "The {$field} field (true/false value)",
            'array', 'json' => "The {$field} field (array of values)",
            'datetime', 'date' => "The {$field} field (date/time value)",
            'timestamp' => "The {$field} field (timestamp value)",
            default => "The {$field} field (text value)"
        };
    }

    /**
     * Get AI-friendly relationship descriptions.
     */
    public function getAIRelationshipDescriptions(): array
    {
        $descriptions = [];
        $reflection = new \ReflectionClass($this);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $returnType = $method->getReturnType();
            
            if ($returnType && 
                (str_contains($returnType->getName(), 'Relation') || 
                 str_contains($returnType->getName(), 'BelongsTo') ||
                 str_contains($returnType->getName(), 'HasMany'))) {
                
                $descriptions[$methodName] = $this->getRelationshipDescription($methodName, $returnType->getName());
            }
        }
        
        return $descriptions;
    }

    /**
     * Get relationship description.
     */
    private function getRelationshipDescription(string $name, string $returnType): string
    {
        return match(true) {
            str_contains($returnType, 'BelongsTo') => "The {$name} relationship (belongs to another model)",
            str_contains($returnType, 'HasMany') => "The {$name} relationship (has many related records)",
            str_contains($returnType, 'HasOne') => "The {$name} relationship (has one related record)",
            str_contains($returnType, 'BelongsToMany') => "The {$name} relationship (many-to-many with another model)",
            str_contains($returnType, 'MorphTo') => "The {$name} relationship (polymorphic - can belong to different model types)",
            str_contains($returnType, 'MorphMany') => "The {$name} relationship (polymorphic - has many related records of different types)",
            default => "The {$name} relationship"
        };
    }

    /**
     * Get AI capabilities for this model.
     */
    public function getAICapabilities(): array
    {
        return [
            'can_search' => method_exists($this, 'aiSearch'),
            'can_create' => method_exists($this, 'aiCreate'),
            'can_update' => method_exists($this, 'aiUpdate'),
            'can_delete' => method_exists($this, 'aiDelete'),
            'can_list' => method_exists($this, 'aiList'),
            'can_show' => method_exists($this, 'aiShow'),
        ];
    }

    /**
     * Get AI-friendly model summary.
     */
    public function getAISummary(): array
    {
        return [
            'id' => $this->getKey(),
            'type' => class_basename(static::class),
            'description' => $this->getAIDescription(),
            'fields' => $this->getAIFieldDescriptions(),
            'relationships' => $this->getAIRelationshipDescriptions(),
            'capabilities' => $this->getAICapabilities(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
