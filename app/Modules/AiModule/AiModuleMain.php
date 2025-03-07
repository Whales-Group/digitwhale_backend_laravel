<?php

namespace App\Modules\AiModule;

use App\Exceptions\AppException;
use App\Helpers\ResponseHelper;
use App\Modules\AiModule\Services\GeminiService\GeminiConversationService;
use Illuminate\Http\JsonResponse;

class AiModuleMain
{
 public function processQuery(): JsonResponse
 {
  try {
   $queryText = request()->get('queryText');

   if (empty($queryText)) {
    throw new \Exception("Query text is required.");
   }

   $textResponse = GeminiConversationService::query($queryText);
   return ResponseHelper::success(data: ['text' => $textResponse]);
  } catch (\Throwable $e) {
   throw new AppException($e->getMessage());
  }
 }
}