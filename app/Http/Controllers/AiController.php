<?php

namespace App\Http\Controllers;

use App\Modules\AiModule\AiModuleMain;
use Illuminate\Http\JsonResponse;

class AiController extends Controller
{
    protected $aiModuleMain;

    public function __construct(AiModuleMain $aiModuleMain)
    {
        // Inject the AiModuleMain instance
        $this->aiModuleMain = $aiModuleMain;
    }

    /**
     * Handle chat requests.
     */
    public function chat(): JsonResponse
    {
        return $this->aiModuleMain->chat();
    }

    /**
     * Start a new conversation.
     */
    public function startConversation(): JsonResponse
    {
        return $this->aiModuleMain->startConversation();
    }

    /**
     * Retrieve conversation messages.
     */
    public function getConversationMessages(): JsonResponse
    {
        return $this->aiModuleMain->getConversationMessages();
    }


    /**
     * Retrieve all conversation.
     */
    public function getAllConversation(): JsonResponse
    {
        return $this->aiModuleMain->getAllConversation();
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(): JsonResponse
    {
        return $this->aiModuleMain->deleteConversation();
    }

    /**
     * Recover a deleted conversation.
     */
    public function recoverConversation(): JsonResponse
    {
        return $this->aiModuleMain->recoverConversation();
    }

    /**
     * Select a model based on user preferences and subscription.
     */
    public function selectModel(): JsonResponse
    {
        return $this->aiModuleMain->selectModel();
    }
}
