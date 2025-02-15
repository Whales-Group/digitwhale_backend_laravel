<?php

namespace App\Models;

use App\Common\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'document_type_id',
        'value',
        'document_url',
        'status',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => Status::class,
    ];

    /**
     * Get the user associated with this document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document type associated with this document.
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    
}