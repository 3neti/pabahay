<?php

namespace LBHurtado\Mortgage\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_calls',
        'tool_results',
        'ai_provider',
        'ai_model',
        'tokens_used',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'tool_results' => 'array',
        'tokens_used' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    public function hasToolCalls(): bool
    {
        return !empty($this->tool_calls);
    }
}
