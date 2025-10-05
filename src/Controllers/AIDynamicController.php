<?php

namespace LaravelAIAssistant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use LaravelAIAssistant\Services\SchemaAnalyzer;
use LaravelAIAssistant\Services\AIMetadataGenerator;

class AIDynamicController extends Controller
{
    private SchemaAnalyzer $schemaAnalyzer;
    private AIMetadataGenerator $metadataGenerator;

    public function __construct(
        SchemaAnalyzer $schemaAnalyzer,
        AIMetadataGenerator $metadataGenerator
    ) {
        $this->schemaAnalyzer = $schemaAnalyzer;
        $this->metadataGenerator = $metadataGenerator;
    }

    /**
     * Get AI metadata for the application.
     */
    public function getMetadata(): JsonResponse
    {
        try {
            $metadata = $this->metadataGenerator->generateMetadata();
            
            return response()->json([
                'success' => true,
                'data' => $metadata
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate metadata',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dynamic model operations.
     */
    public function handleModelOperation(Request $request, string $modelName): JsonResponse
    {
        try {
            $modelClass = $this->getModelClass($modelName);
            
            if (!$modelClass) {
                return response()->json([
                    'success' => false,
                    'error' => 'Model not found',
                    'message' => "Model '{$modelName}' not found in the application"
                ], 404);
            }

            $operation = $request->get('operation', 'list');
            
            return match($operation) {
                'list' => $this->listModel($modelClass, $request),
                'search' => $this->searchModel($modelClass, $request),
                'create' => $this->createModel($modelClass, $request),
                'update' => $this->updateModel($modelClass, $request),
                'delete' => $this->deleteModel($modelClass, $request),
                'show' => $this->showModel($modelClass, $request),
                default => response()->json([
                    'success' => false,
                    'error' => 'Invalid operation',
                    'message' => "Operation '{$operation}' is not supported"
                ], 400)
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List model records.
     */
    private function listModel(string $modelClass, Request $request): JsonResponse
    {
        $query = $modelClass::query();
        
        // Apply filters
        if ($request->has('filters')) {
            $query = $this->applyFilters($query, $request->get('filters'));
        }
        
        // Apply relationships
        if ($request->has('with')) {
            $query->with($request->get('with'));
        }
        
        // Apply ordering
        if ($request->has('order_by')) {
            $direction = $request->get('order_direction', 'asc');
            $query->orderBy($request->get('order_by'), $direction);
        }
        
        // Apply pagination
        $limit = min($request->get('limit', 15), 100); // Max 100 records
        $offset = $request->get('offset', 0);
        
        $results = $query->offset($offset)->limit($limit)->get();
        $total = $query->count();
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Search model records.
     */
    private function searchModel(string $modelClass, Request $request): JsonResponse
    {
        $query = $request->get('query');
        $fields = $request->get('fields', []);
        $limit = min($request->get('limit', 10), 50); // Max 50 for search
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'error' => 'Query is required',
                'message' => 'Search query parameter is required'
            ], 400);
        }
        
        $model = new $modelClass;
        
        if (method_exists($model, 'aiSearch')) {
            // Use custom search method if available
            $results = $model->aiSearch($query, $fields);
        } else {
            // Default search implementation
            $searchFields = empty($fields) ? $model->getFillable() : $fields;
            $searchQuery = $modelClass::query();
            
            foreach ($searchFields as $field) {
                $searchQuery->orWhere($field, 'like', "%{$query}%");
            }
            
            $results = $searchQuery->limit($limit)->get();
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $query,
            'fields_searched' => $fields,
            'total' => $results->count()
        ]);
    }

    /**
     * Create model record.
     */
    private function createModel(string $modelClass, Request $request): JsonResponse
    {
        $model = new $modelClass;
        
        if (method_exists($model, 'aiCreate')) {
            // Use custom create method if available
            $result = $model->aiCreate($request->all());
        } else {
            // Default create implementation
            $fillable = $model->getFillable();
            $data = $request->only($fillable);
            
            $result = $modelClass::create($data);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Record created successfully'
        ], 201);
    }

    /**
     * Update model record.
     */
    private function updateModel(string $modelClass, Request $request): JsonResponse
    {
        $id = $request->get('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'error' => 'ID is required',
                'message' => 'Record ID is required for update operation'
            ], 400);
        }
        
        $model = $modelClass::findOrFail($id);
        
        if (method_exists($model, 'aiUpdate')) {
            // Use custom update method if available
            $result = $model->aiUpdate($request->all());
        } else {
            // Default update implementation
            $fillable = $model->getFillable();
            $data = $request->only($fillable);
            
            $model->update($data);
            $result = $model->fresh();
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Record updated successfully'
        ]);
    }

    /**
     * Delete model record.
     */
    private function deleteModel(string $modelClass, Request $request): JsonResponse
    {
        $id = $request->get('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'error' => 'ID is required',
                'message' => 'Record ID is required for delete operation'
            ], 400);
        }
        
        $model = $modelClass::findOrFail($id);
        
        if (method_exists($model, 'aiDelete')) {
            // Use custom delete method if available
            $result = $model->aiDelete();
        } else {
            // Default delete implementation
            $model->delete();
            $result = true;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully'
        ]);
    }

    /**
     * Show single model record.
     */
    private function showModel(string $modelClass, Request $request): JsonResponse
    {
        $id = $request->get('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'error' => 'ID is required',
                'message' => 'Record ID is required for show operation'
            ], 400);
        }
        
        $query = $modelClass::query();
        
        // Apply relationships
        if ($request->has('with')) {
            $query->with($request->get('with'));
        }
        
        $model = $query->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $model
        ]);
    }

    /**
     * Get model class from model name.
     */
    private function getModelClass(string $modelName): ?string
    {
        $possibleClasses = [
            "App\\Models\\{$modelName}",
            "App\\Models\\" . ucfirst($modelName),
            "App\\{$modelName}",
            "App\\" . ucfirst($modelName)
        ];
        
        // Check modules if they exist
        if (is_dir(base_path('Modules'))) {
            $moduleDirs = glob(base_path('Modules/*/app/Models'), GLOB_ONLYDIR);
            foreach ($moduleDirs as $moduleDir) {
                $moduleName = basename(dirname(dirname($moduleDir)));
                $possibleClasses = array_merge($possibleClasses, [
                    "Modules\\{$moduleName}\\Models\\{$modelName}",
                    "Modules\\{$moduleName}\\Models\\" . ucfirst($modelName),
                ]);
            }
        }
        
        foreach ($possibleClasses as $class) {
            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                return $class;
            }
        }
        
        return null;
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator'])) {
                    $operator = $value['operator'];
                    $filterValue = $value['value'];
                    
                    switch ($operator) {
                        case 'equals':
                            $query->where($field, $filterValue);
                            break;
                        case 'not_equals':
                            $query->where($field, '!=', $filterValue);
                            break;
                        case 'greater_than':
                            $query->where($field, '>', $filterValue);
                            break;
                        case 'less_than':
                            $query->where($field, '<', $filterValue);
                            break;
                        case 'like':
                            $query->where($field, 'like', "%{$filterValue}%");
                            break;
                        case 'in':
                            $query->whereIn($field, $filterValue);
                            break;
                        case 'not_in':
                            $query->whereNotIn($field, $filterValue);
                            break;
                        case 'between':
                            $query->whereBetween($field, $filterValue);
                            break;
                        case 'date_between':
                            $query->whereDate($field, '>=', $filterValue[0])
                                  ->whereDate($field, '<=', $filterValue[1]);
                            break;
                        case 'is_null':
                            $query->whereNull($field);
                            break;
                        case 'is_not_null':
                            $query->whereNotNull($field);
                            break;
                    }
                } else {
                    // Simple array means "in" operation
                    $query->whereIn($field, $value);
                }
            } else {
                // Simple value means "equals" operation
                $query->where($field, $value);
            }
        }
        
        return $query;
    }
}
