<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveLocationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'visibility_timer',
        'visibility',
        'profile_picture_visibility',
        'notify_on_visible'

    ];

    protected $casts = [
        'visibility_timer' => 'integer',
        'visibility' => 'boolean',
        'profile_picture_visibility' => 'boolean',
        'notify_on_visible' => 'boolean',
    ];


    /**
     * Get the user that owns the account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
