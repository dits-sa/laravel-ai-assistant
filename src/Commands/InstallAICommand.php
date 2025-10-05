<?php

namespace LaravelAIAssistant\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallAICommand extends Command
{
    protected $signature = 'ai:install 
                            {--force : Force installation even if already installed}
                            {--publish-config : Publish configuration file}
                            {--publish-migrations : Publish migration files}';

    protected $description = 'Install AI Assistant package';

    public function handle()
    {
        $this->info('🚀 Installing AI Assistant package...');
        
        // Check if already installed
        if (File::exists(config_path('ai-assistant.php')) && !$this->option('force')) {
            $this->warn('AI Assistant is already installed. Use --force to reinstall.');
            return 0;
        }
        
        // Publish configuration
        if ($this->option('publish-config') || $this->confirm('Publish configuration file?', true)) {
            $this->info('📄 Publishing configuration file...');
            $this->call('vendor:publish', [
                '--provider' => 'LaravelAIAssistant\\Providers\\AIAssistantServiceProvider',
                '--tag' => 'ai-assistant-config'
            ]);
        }
        
        // Publish migrations
        if ($this->option('publish-migrations') || $this->confirm('Publish migration files?', true)) {
            $this->info('🗄️ Publishing migration files...');
            $this->call('vendor:publish', [
                '--provider' => 'LaravelAIAssistant\\Providers\\AIAssistantServiceProvider',
                '--tag' => 'ai-assistant-migrations'
            ]);
        }
        
        // Run migrations
        if ($this->confirm('Run migrations?', true)) {
            $this->info('🔄 Running migrations...');
            $this->call('migrate');
        }
        
        // Generate initial metadata
        if ($this->confirm('Generate initial AI metadata?', true)) {
            $this->info('📊 Generating initial metadata...');
            $this->call('ai:generate-metadata');
        }
        
        $this->info('✅ AI Assistant installed successfully!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Add the AICapable trait to your models:');
        $this->line('   use LaravelAIAssistant\\Traits\\AICapable;');
        $this->line('   class YourModel extends Model { use AICapable; }');
        $this->line('');
        $this->line('2. Configure your models in config/ai-assistant.php');
        $this->line('3. Generate metadata: php artisan ai:generate-metadata');
        $this->line('4. Set up your VPS for real-time AI service');
        $this->line('5. Integrate the frontend components');
        
        return 0;
    }
}
