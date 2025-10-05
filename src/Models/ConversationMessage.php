<?php

namespace LaravelAIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'user_id');
    }

    /**
     * Check if message is from user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant.
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Check if message is from system.
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    /**
     * Get formatted content.
     */
    public function getFormattedContentAttribute(): string
    {
        // Basic formatting for display
        $content = $this->content;
        
        // Convert markdown-like formatting
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        $content = preg_replace('/`(.*?)`/', '<code>$1</code>', $content);
        
        // Convert line breaks
        $content = nl2br($content);
        
        return $content;
    }

    /**
     * Get tool calls from metadata.
     */
    public function getToolCallsAttribute(): array
    {
        return $this->metadata['tool_calls'] ?? [];
    }

    /**
     * Get tool results from metadata.
     */
    public function getToolResultsAttribute(): array
    {
        return $this->metadata['tool_results'] ?? [];
    }

    /**
     * Check if message has tool calls.
     */
    public function hasToolCalls(): bool
    {
        return !empty($this->tool_calls);
    }

    /**
     * Check if message has tool results.
     */
    public function hasToolResults(): bool
    {
        return !empty($this->tool_results);
    }
}
