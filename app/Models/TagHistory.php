<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagHistory extends Model
{
    protected $fillable = ["tag_id", "user_id", "old_tag", "new_tag"];

    // Define the inverse relationship to Tag
    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
