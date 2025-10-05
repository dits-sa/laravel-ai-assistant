<?php

namespace LaravelAIAssistant\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAICacheCommand extends Command
{
    protected $signature = 'ai:clear-cache 
                            {--all : Clear all AI-related cache}
                            {--metadata : Clear only metadata cache}
                            {--tokens : Clear only token cache}';

    protected $description = 'Clear AI Assistant cache';

    public function handle()
    {
        $this->info('🧹 Clearing AI Assistant cache...');
        
        $cleared = 0;
        
        if ($this->option('all') || $this->option('metadata')) {
            $this->info('📊 Clearing metadata cache...');
            Cache::forget('ai_assistant_schema_analysis');
            Cache::forget('ai_assistant_metadata');
            $cleared += 2;
        }
        
        if ($this->option('all') || $this->option('tokens')) {
            $this->info('🔑 Clearing token cache...');
            // Clear all AI tokens (this is a simple implementation)
            $keys = Cache::getRedis()->keys('ai_token_*');
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                $cleared++;
            }
        }
        
        if (!$this->option('all') && !$this->option('metadata') && !$this->option('tokens')) {
            // Clear all AI-related cache by default
            $this->info('📊 Clearing metadata cache...');
            Cache::forget('ai_assistant_schema_analysis');
            Cache::forget('ai_assistant_metadata');
            $cleared += 2;
            
            $this->info('🔑 Clearing token cache...');
            $keys = Cache::getRedis()->keys('ai_token_*');
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                $cleared++;
            }
        }
        
        $this->info("✅ Cleared {$cleared} cache entries");
        
        return 0;
    }
}
