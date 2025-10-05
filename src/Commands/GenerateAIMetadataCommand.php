<?php

namespace LaravelAIAssistant\Commands;

use Illuminate\Console\Command;
use LaravelAIAssistant\Services\SchemaAnalyzer;
use LaravelAIAssistant\Services\AIMetadataGenerator;

class GenerateAIMetadataCommand extends Command
{
    protected $signature = 'ai:generate-metadata 
                            {--output= : Output file path}
                            {--format=json : Output format (json, yaml)}
                            {--force : Force regeneration even if cache exists}';

    protected $description = 'Generate AI metadata for the application';

    public function handle(SchemaAnalyzer $schemaAnalyzer, AIMetadataGenerator $metadataGenerator)
    {
        $this->info('🤖 Generating AI Assistant metadata...');
        
        if ($this->option('force')) {
            $this->info('🔄 Clearing existing cache...');
            \Cache::forget('ai_assistant_schema_analysis');
            \Cache::forget('ai_assistant_metadata');
        }
        
        $this->info('📊 Analyzing application schema...');
        $schema = $schemaAnalyzer->analyzeApplication();
        $this->info("✅ Found " . count($schema['models']) . " models");
        
        $this->info('🔧 Generating AI metadata...');
        $metadata = $metadataGenerator->generateMetadata();
        
        $outputPath = $this->option('output') ?: storage_path('app/ai-metadata.json');
        $format = $this->option('format');
        
        if ($format === 'yaml') {
            if (!function_exists('yaml_emit')) {
                $this->error('YAML extension not available. Please install php-yaml extension.');
                return 1;
            }
            $content = yaml_emit($metadata);
            $outputPath = str_replace('.json', '.yaml', $outputPath);
        } else {
            $content = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        
        file_put_contents($outputPath, $content);
        
        $this->info("✅ AI metadata generated successfully: {$outputPath}");
        $this->info("📋 Available tools: " . count($metadata['available_tools']));
        $this->info("🔗 API endpoints: " . count($metadata['api_endpoints']));
        $this->info("📊 Models: " . count($metadata['models']));
        $this->info("🗄️ Tables: " . count($metadata['tables']));
        
        // Display some examples
        if (!empty($metadata['available_tools'])) {
            $this->info("\n🛠️ Available tools:");
            foreach (array_slice($metadata['available_tools'], 0, 5) as $tool) {
                $this->line("  • {$tool['name']}: {$tool['description']}");
            }
            if (count($metadata['available_tools']) > 5) {
                $this->line("  ... and " . (count($metadata['available_tools']) - 5) . " more");
            }
        }
        
        return 0;
    }
}
