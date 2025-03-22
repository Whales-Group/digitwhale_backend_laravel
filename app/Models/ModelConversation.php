<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ModelConversation extends Model
{
    use  SoftDeletes;

    protected $table = 'model_conversations';
    protected $fillable = ['user_id', 'title', 'status'];

    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
    ];

    protected static $rules = [
        'user_id' => ['required', 'integer', 'exists:users,id'],
        'title' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:active,archived,deleted'],
    ];

    protected $attributes = [
        'status' => 'active', // Default status
    ];

    protected static function booted(): void
    {
        static::saving(function (ModelConversation $conversation) {
            $conversation->validate();
        });
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ModelMessage::class, 'conversation_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // --- Custom Methods ---

    public function validate(): void
    {
        $validator = Validator::make($this->attributes, static::$rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function archive(): self
    {
        $this->status = 'archived';
        return $this;
    }

    public function deleteConversation(): self
    {
        $this->status = 'deleted';
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function messageCount(): int
    {
        return $this->messages()->count();
    }
}