<?php

namespace App\Helpers;

use App\Exceptions\AppException;
use App\Modules\AiModule\Providers\DeepSeek;
use App\Modules\AiModule\Providers\Gemini;
use App\Modules\AiModule\Providers\MetaAI;
use App\Modules\AiModule\Providers\OpenChat;

class ModelHelper
{
  public static $defaultModel = 'j.1.0';
  public static $defaultProvider = 'OpenChat';

  public static $models = [
    [
      'conversation_models' =>
        [
          "v1" => 'j.1.0',
          "v2" => 'j.2.0',
        ],
    ],
    [
      'transaction_analysis_models' =>
        [
          "v1" => 'vsx.1.0',
        ],
    ],
  ];

  public static function tierMap()
  {
    return [
      'basic' => ['j.1.0'],
      'grow' => ['j.1.0', 'j.2.0'],
      'master' => self::getAllModels(),
    ];
  }

  public static $packageNames = [
    'basic' => 'Basic Plan',
    'grow' => 'Grow Plan',
    'master' => 'Master Plan',
  ];

  public static function getAllModels(): array
  {
    $allModels = [];
    foreach (self::$models as $category) {
      foreach ($category as $type => $versions) {
        $allModels = array_merge($allModels, array_values($versions));
      }
    }
    return $allModels;
  }

  public static $providers = [
    "DeepSeek" => DeepSeek::class,
    "Gemini" => Gemini::class,
    "OpenChat" => OpenChat::class,
    "MetaAI" => MetaAI::class,
  ];
  public static function DefaultProvider(string $message, int $conversationId = 0): string
  {
    $modelClass = self::$providers[self::$defaultProvider] ?? null;

    if (!$modelClass || !class_exists($modelClass)) {
      throw new AppException("Invalid default model class: " . ($modelClass ?? 'none'));
    }
    call_user_func([$modelClass, 'initialize']);

    return call_user_func([$modelClass, 'query'], $message, $conversationId);
  }
  public static function getModelClass(string $modelName): ?string
  {
    return self::$providers[$modelName] ?? null;
  }
}