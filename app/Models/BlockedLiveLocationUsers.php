<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedLiveLocationUsers extends Model
{
    protected $fillable = [
        'user_id',
        'blocked_user',
        'status',  // active or inactive
        'reason'
        
    ];

     /**
     * Get the user that owns the account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

