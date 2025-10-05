<?php

namespace LaravelAIAssistant\Tests;

use Orchestra\Testbench\TestCase;
use LaravelAIAssistant\Providers\AIAssistantServiceProvider;
use LaravelAIAssistant\Services\SchemaAnalyzer;
use LaravelAIAssistant\Services\AIMetadataGenerator;

class AIAssistantTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            AIAssistantServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function test_package_is_loaded()
    {
        $this->assertTrue(class_exists('LaravelAIAssistant\Services\SchemaAnalyzer'));
        $this->assertTrue(class_exists('LaravelAIAssistant\Services\AIMetadataGenerator'));
    }

    public function test_schema_analyzer_can_analyze_application()
    {
        $analyzer = app(SchemaAnalyzer::class);
        $schema = $analyzer->analyzeApplication();
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('models', $schema);
        $this->assertArrayHasKey('tables', $schema);
        $this->assertArrayHasKey('relationships', $schema);
        $this->assertArrayHasKey('generated_at', $schema);
    }

    public function test_metadata_generator_can_generate_metadata()
    {
        $generator = app(AIMetadataGenerator::class);
        $metadata = $generator->generateMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('application_info', $metadata);
        $this->assertArrayHasKey('models', $metadata);
        $this->assertArrayHasKey('tables', $metadata);
        $this->assertArrayHasKey('available_tools', $metadata);
        $this->assertArrayHasKey('api_endpoints', $metadata);
        $this->assertArrayHasKey('generated_at', $metadata);
    }

    public function test_ai_capable_trait_exists()
    {
        $this->assertTrue(trait_exists('LaravelAIAssistant\Traits\AICapable'));
    }

    public function test_commands_are_registered()
    {
        $this->artisan('ai:generate-metadata --help')
            ->assertExitCode(0);
            
        $this->artisan('ai:clear-cache --help')
            ->assertExitCode(0);
            
        $this->artisan('ai:install --help')
            ->assertExitCode(0);
    }
}
