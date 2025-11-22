<?php

namespace LBHurtado\Mortgage\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'context',
        'metadata',
        'started_at',
        'last_activity_at',
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function addMessage(string $role, ?string $content = null, ?array $toolCalls = null, ?array $toolResults = null): ConversationMessage
    {
        return $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'tool_calls' => $toolCalls,
            'tool_results' => $toolResults,
        ]);
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function scopeActive($query, int $minutes = 30)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function getMessagesForAI(): array
    {
        return $this->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }
}
