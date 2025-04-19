<?php

namespace App\Modules\AiModule;

use App\Exceptions\AppException;
use App\Helpers\ResponseHelper;
use App\Modules\AiModule\Engines\AICoreEngine;
use App\Modules\UtilsModule\Services\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiModuleMain
{
    protected $aiCoreEngine;

    public function __construct(PackageService $packageService)
    {
        $this->aiCoreEngine = new AICoreEngine($packageService);
    }

    /**
     * Handle chat requests.
     */
    public function chat(): JsonResponse
    {
        try {
            $conversationId = request()->get('conversation_id');
            $modelSlug = request()->get('model');
            $userMessage = request()->get('message');

            if (empty($conversationId) || empty($modelSlug) || empty($userMessage)) {
                throw new \InvalidArgumentException("Missing required parameters: conversation_id, model, or message.");
            }

            $response = $this->aiCoreEngine->chat((int)$conversationId, $modelSlug, $userMessage);
            return ResponseHelper::success(data: $response);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Start a new conversation.
     */
    public function startConversation(): JsonResponse
    {
        try {
            $title = request()->input('title');
            $userId = Auth::id();

            if (empty($userId)) {
                throw new \InvalidArgumentException("No authenticated user found.");
            }

            $conversation = $this->aiCoreEngine->startConversation((int)$userId, $title);
            return ResponseHelper::success(data: $conversation);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Retrieve conversation messages.
     */
    public function getConversationMessages(): JsonResponse
    {
        try {
            $conversationId = request()->query('conversation_id');
            $perPage = request()->query('per_page', 10);

            if (empty($conversationId)) {
                throw new \InvalidArgumentException("Conversation ID is required.");
            }

            $history = $this->aiCoreEngine->getUserConversationsWithMessages((int)$conversationId, (int)$perPage);
            return ResponseHelper::success(data: $history);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Retrieve all conversation.
     */
    public function getAllConversation(): JsonResponse
    {
        try {
            $history = $this->aiCoreEngine->getAllConversation();
            return ResponseHelper::success(data: $history);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(): JsonResponse
    {
        try {
            $conversationId = request()->input('conversationId');
            $userId = Auth::id();

            if (empty($conversationId) || empty($userId)) {
                throw new \InvalidArgumentException("Both conversation ID and user authentication are required.");
            }

            $isDeleted = $this->aiCoreEngine->deleteConversation((int)$conversationId, (int)$userId);
            return ResponseHelper::success(data: ['deleted' => $isDeleted]);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Recover a deleted conversation.
     */
    public function recoverConversation(): JsonResponse
    {
        try {
            $conversationId = request()->input('conversationId');
            $userId = Auth::id();

            if (empty($conversationId) || empty($userId)) {
                throw new \InvalidArgumentException("Both conversation ID and user authentication are required.");
            }

            $conversation = $this->aiCoreEngine->recoverConversation((int)$conversationId, (int)$userId);
            return ResponseHelper::success(data: $conversation);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }

    /**
     * Select a model based on user preferences and subscription.
     */
    public function selectModel(): JsonResponse
    {
        try {
            $preferredModel = request()->input('preferredModel');
            $userId = Auth::id();

            if (empty($userId)) {
                throw new \InvalidArgumentException("No authenticated user found.");
            }

            $selectedModel = $this->aiCoreEngine->selectModel((int)$userId, $preferredModel);
            return ResponseHelper::success(data: $selectedModel);
        } catch (\Throwable $e) {
            throw new AppException($e->getMessage());
        }
    }
}
