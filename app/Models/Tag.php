<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TagHistory;

class Tag extends Model
{
    protected $fillable = ["tag", "user_id"];

    protected $hidden = ["id", "user_id", "updated_at"];

    public function history()
    {
        return $this->hasMany(TagHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }
}
