<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ModelMessage extends Model
{
    use SoftDeletes;

    protected $table = 'model_messages';
    protected $fillable = ['conversation_id', 'model_version', 'user_id', 'message', 'is_edited', 'is_model'];

    protected $casts = [
        'conversation_id' => 'integer',
        'user_id' => 'integer',
        'is_edited' => 'boolean',
        "is_model" => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static $rules = [
        'conversation_id' => ['required', 'integer', 'exists:model_conversations,id'],
        'model_version' => ['required', 'string', 'max:255'],
        'user_id' => ['nullable', 'integer', 'exists:users,id'],
        'message' => ['required', 'string'],
        'is_edited' => ['boolean'],
        'is_model' => ['boolean'],

    ];

    protected static function booted(): void
    {
        static::saving(function (ModelMessage $message) {
            $message->validate();
        });
    }

    // --- Relationships ---

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ModelConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    // --- Scopes ---

    public function scopeFromModel($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeFromUser($query)
    {
        return $query->whereNotNull('user_id');
    }

    // --- Accessors & Mutators ---

    protected function message(): Attribute
    {
        return Attribute::make(
            set: fn($value) => trim($value)
        );
    }

    // --- Custom Methods ---

    public function validate(): void
    {
        $validator = Validator::make($this->attributes, static::$rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function isFromModel(): bool
    {
        return $this->is_model;
    }

    public function edit(string $newMessage): self
    {
        $this->message = $newMessage;
        $this->is_edited = true;
        return $this;
    }

    public function getSender(): string
    {
        return $this->isFromModel() ? $this->model_version : $this->user->name ?? 'Unknown User';
    }
}