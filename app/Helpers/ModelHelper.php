<?php

namespace App\Helpers;

use App\Modules\AiModule\Engines\ProviderEngine;

class ModelHelper
{
    public static $defaultModel = 'j.1.0';
    public static $defaultProvider = 'DeepSeek';

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
    public static $packageNames = [
        'basic' => 'Basic Plan',
        'grow' => 'Grow Plan',
        'master' => 'Master Plan',
    ];

    public static function tierMap()
    {
        return [
            'basic' => ['j.1.0'],
            'grow' => ['j.1.0', 'j.2.0'],
            'master' => self::getAllModels(),
        ];
    }

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


    public static function DefaultProvider(string $message, string $modelSlug, int $conversationId = 0): string
    {
        ProviderEngine::initialize($modelSlug);

        $call = ProviderEngine::query($message, $conversationId, true, $modelSlug);

        return $call;
    }

    public static function getModelClass(string $modelName): ?string
    {
        return ProviderEngine::$providers[$modelName] ?? null;
    }
}
