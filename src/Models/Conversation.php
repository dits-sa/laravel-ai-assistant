<?php

namespace LaravelAIAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'user_id');
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Get the latest message for the conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->latest();
    }

    /**
     * Get the first message for the conversation.
     */
    public function firstMessage(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest();
    }

    /**
     * Get conversation summary.
     */
    public function getSummaryAttribute(): string
    {
        $firstMessage = $this->firstMessage()->first();
        
        if (!$firstMessage) {
            return 'Empty conversation';
        }
        
        $content = $firstMessage->content;
        
        return strlen($content) > 100 
            ? substr($content, 0, 100) . '...' 
            : $content;
    }

    /**
     * Get message count.
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }

    /**
     * Get last activity.
     */
    public function getLastActivityAttribute(): ?string
    {
        $lastMessage = $this->latestMessage()->first();
        
        return $lastMessage ? $lastMessage->created_at->toISOString() : $this->updated_at->toISOString();
    }
}
