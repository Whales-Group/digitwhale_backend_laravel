<?php

namespace App\Modules\AiModule\Engines;

use App\Helpers\ModelHelper;
use App\Models\ModelConversation;
use App\Models\ModelMessage;
use App\Models\Subscription;
use App\Modules\AiModule\AiModels\ConversationalModel\ConversationalModelMain;
use App\Modules\AiModule\AiModels\TransactionAccessmentModel\TransactionAccessmentModelMain;
use App\Modules\AiModule\Providers\DeepSeek;
use App\Modules\AiModule\Providers\Gemini;
use App\Modules\AiModule\Providers\MetaAi;
use App\Modules\AiModule\Providers\ProviderEngine;
use App\Modules\UtilsModule\Services\PackageService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AICoreEngine
{
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function startConversation(int $userId, ?string $title = null): ModelConversation
    {
        try {
            return DB::transaction(function () use ($userId, $title) {
                $conversation = new ModelConversation([
                    'user_id' => $userId,
                    'title' => $title ?? "New Chat [" . now()->toDateTimeString() . "]",
                ]);
                $conversation->save();
                return $conversation;
            });
        } catch (Exception $e) {
            Log::error("Failed to start conversation for user $userId: " . $e->getMessage());
            throw new Exception("Unable to start conversation: " . $e->getMessage());
        }
    }

    public function getAllConversation(): mixed
    {
        try {
            $userId = auth()->id();
            $perPage = request('per_page', 10);
            $page = request('page', 1);

            // Paginate the user's conversations
            $paginator = ModelConversation::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Add messages to each conversation
            $paginator->getCollection()->transform(function ($conversation) use ($userId) {
                $this->checkUserOwnership($conversation->id, $userId);

                $conversation->messages = ModelMessage::where('conversation_id', $conversation->id)
                    ->orderBy('created_at', 'asc')
                    ->get();

                return $conversation;
            });

            return $paginator;
        } catch (Exception $e) {
            Log::error("Failed to retrieve paginated conversations: " . $e->getMessage());
            throw new Exception("Unable to retrieve conversations.");
        }
    }

    private function checkUserOwnership(int $conversationId, int $userId): void
    {
        $conversation = ModelConversation::find($conversationId);
        if ($conversation->user_id !== $userId) {
            throw new Exception("User $userId does not own conversation $conversationId.");
        }
    }

    public function getUserConversationsWithMessages(int $conversationId, int $perPage = 10)
    {
        $this->validateConversationExists($conversationId);
        try {
            return ModelMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);
        } catch (Exception $e) {
            Log::error("Failed to retrieve history for conversation $conversationId: " . $e->getMessage());
            throw new Exception("Unable to retrieve conversation history.");
        }
    }

    private function validateConversationExists(int $conversationId): void
    {
        if (!ModelConversation::where('id', $conversationId)->exists()) {
            throw new InvalidArgumentException("Conversation $conversationId does not exist.");
        }
    }

    public function deleteConversation(int $conversationId, int $userId): bool
    {
        $this->validateConversationExists($conversationId);
        $this->checkUserOwnership($conversationId, $userId);

        try {
            return DB::transaction(function () use ($conversationId) {
                ModelMessage::where('conversation_id', $conversationId)->delete();
                $conversation = ModelConversation::find($conversationId);
                return $conversation->delete();
            });
        } catch (Exception $e) {
            Log::error("Failed to delete conversation $conversationId: " . $e->getMessage());
            throw new Exception("Unable to delete conversation.");
        }
    }

    public function recoverConversation(int $conversationId, int $userId): ModelConversation
    {
        $conversation = ModelConversation::withTrashed()->find($conversationId);
        if (!$conversation || $conversation->user_id !== $userId) {
            throw new Exception("Cannot recover conversation $conversationId: not found or not owned by user $userId.");
        }

        try {
            DB::transaction(function () use ($conversation) {
                $conversation->restore();
                $conversation->status = 'active';
                $conversation->save();
            });
            return $conversation;
        } catch (Exception $e) {
            Log::error("Failed to recover conversation $conversationId: " . $e->getMessage());
            throw new Exception("Unable to recover conversation.");
        }
    }

    public function selectModel(int $userId, ?string $preferredModel = null): string
    {
        $accessibleModels = $this->getAccessibleModels($userId);

        if ($preferredModel && in_array($preferredModel, $accessibleModels)) {
            return $preferredModel;
        }

        // Use the default model if no preferred model is specified
        return ModelHelper::$defaultModel;
    }

    protected function getAccessibleModels(int $userId): array
    {
        $packageTier = $this->getUserPackageTier($userId);
        return ModelHelper::$tierMap[$packageTier] ?? ModelHelper::tierMap()['basic'];
    }

    protected function getUserPackageTier(int $userId): string
    {
        $subscription = Subscription::where('user_id', $userId)
            ->where('is_active', true)
            ->with('package')
            ->first();

        if (!$subscription || !$subscription->package) {
            return 'basic'; // Default to basic tier if no active subscription
        }

        return strtolower($subscription->package->type);
    }

    public function chat(int $conversationId, string $modelSlug, string $userMessage): mixed
    {
        $userId = auth()->user()->id;

        try {

            $response = $this->ModelCall($modelSlug, $userMessage, $conversationId);

            $this->registerMessage($conversationId, $modelSlug, $userMessage, $userId);
            $this->registerMessage($conversationId, $modelSlug, $response, $userId, true);


            return [
                'user_message' => $userMessage,
                'model_response' => $response,
            ];
        } catch (Exception $e) {
            Log::error("Processing failed: " . $e->getMessage());
            throw new Exception("Message processing error: " . $e->getMessage());
        }
    }

    protected function ModelCall(string $modelSlug, string $message, int $conversationId): mixed
    {
        return ModelHelper::DefaultProvider($message, $modelSlug, $conversationId);
    }

    protected function registerMessage(int $conversationId, string $modelSlug, string $message, ?int $userId = null, ?bool $is_model = false): ModelMessage
    {
        $this->validateConversationExists($conversationId);
        $this->checkConversationStatus($conversationId);

        try {
            return DB::transaction(function () use ($conversationId, $modelSlug, $message, $userId, $is_model) {
                $msg = new ModelMessage([
                    'conversation_id' => $conversationId,
                    'model_version' => $modelSlug,
                    'user_id' => $userId,
                    'message' => $message,
                    "is_model" => $is_model,
                ]);
                $msg->save();
                return $msg;
            });
        } catch (Exception $e) {
            Log::error("Failed to add message: " . $e->getMessage());
            throw new Exception("Unable to add message: " . $e->getMessage());
        }
    }

    private function checkConversationStatus(int $conversationId): void
    {
        $conversation = ModelConversation::find($conversationId);
        if (!$conversation->isActive()) {
            throw new Exception("Cannot modify conversation $conversationId: it is {$conversation->status}.");
        }
    }

    private function validateModelAccess(string $modelSlug, int $userId): void
    {
        $accessibleModels = $this->getAccessibleModels($userId);
        if (!in_array($modelSlug, $accessibleModels)) {
            throw new Exception("Model $modelSlug is not accessible with your current package.");
        }
    }
}
