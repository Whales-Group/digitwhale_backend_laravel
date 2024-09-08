<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TagHistory;

class Tag extends Model
{
    protected $fillable = ["tag", "user_id"];

    public function history()
    {
        return $this->hasMany(TagHistory::class);
    }

    protected static function booted()
    {
        static::updated(function ($tag) {
            // Log to tag history only if the tag was actually changed
            if ($tag->isDirty("tag")) {
                TagHistory::create([
                    "tag_id" => $tag->id,
                    "user_id" => $tag->user_id,
                    "old_tag" => $tag->getOriginal("tag"),
                    "new_tag" => $tag->tag,
                ]);
            }
        });
    }
}
