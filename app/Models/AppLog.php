<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppLog extends Model
{
    protected $table = "logs";

    protected $fillable = [
        'channel',
        'message',
        'context',
        'created_at',
    ];

    public static function log($channel, $message, $context = [])
    {
        self::create([
            'channel' => $channel,
            'message' => $message,
            'context' => json_encode($context),
            'created_at' => now(),
        ]);
    }

    public static function error($message, $context = [])
    {
        self::log('error', $message, $context);
    }

    public static function info($message, $context = [])
    {
        self::log('info', $message, $context);
    }

    public static function debug($message, $context = [])
    {
        self::log('debug', $message, $context);
    }
}
