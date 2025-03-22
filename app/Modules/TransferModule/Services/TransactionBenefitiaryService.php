<?php

namespace App\Modules\TransferModule\Services;

use App\Exceptions\AppException;
use App\Helpers\ResponseHelper;
use App\Models\Account;
use App\Models\Beneficiary;
use App\Modules\FincraModule\Services\FincraService;
use App\Modules\FlutterWaveModule\Services\FlutterWaveService;
use App\Modules\PaystackModule\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionBenefitiaryService
{
 private static FincraService $fincraService;
 private static PaystackService $paystackService;
 private static FlutterWaveService $flutterWaveService;

 public static function initializeServices(): void
 {
  self::$fincraService = FincraService::getInstance();
  self::$paystackService = PaystackService::getInstance();
  self::$flutterWaveService = FlutterWaveService::getInstance();
 }

 /**
  * Get all beneficiaries for a user.
  */
 public static function getAllBeneficiaries(Request $request): JsonResponse
 {
  try {
   $user = auth()->user();
   $beneficiaries = Beneficiary::where('user_id', $user->id)->get();

   return ResponseHelper::success($beneficiaries);
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Create a new beneficiary.
  */
 public static function createBeneficiary(Request $request): JsonResponse
 {
  try {
   $user = auth()->user();
   $data = $request->validate([
    'name' => 'required|string',
    'type' => 'required|string',
    'account_number' => 'nullable|string',
    'bank_name' => 'nullable|string',
    'network_provider' => 'nullable|string',
    'phone_number' => 'nullable|string',
    'meter_number' => 'nullable|string',
    'utility_type' => 'nullable|string',
    'plan' => 'nullable|string',
    'amount' => 'nullable|numeric',
    'description' => 'nullable|string',
    'is_favorite' => 'nullable|boolean',
    'unique_id' => 'required|string',
   ]);

   $data['user_id'] = $user->id;

   $beneficiary = Beneficiary::create($data);

   return ResponseHelper::success($beneficiary, "Beneficiary created successfully.");
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Update a beneficiary.
  */
 public static function updateBeneficiary(Request $request, int $beneficiary_id): JsonResponse
 {
  try {
   $user = auth()->user();
   $beneficiary = Beneficiary::where('user_id', $user->id)->where('id', $beneficiary_id)->first();

   if (!$beneficiary) {
    throw new AppException("Beneficiary not found.");
   }

   $data = $request->validate([
    'name' => 'nullable|string',
    'type' => 'nullable|string',
    'account_number' => 'nullable|string',
    'bank_name' => 'nullable|string',
    'network_provider' => 'nullable|string',
    'phone_number' => 'nullable|string',
    'meter_number' => 'nullable|string',
    'utility_type' => 'nullable|string',
    'plan' => 'nullable|string',
    'amount' => 'nullable|numeric',
    'description' => 'nullable|string',
    'is_favorite' => 'nullable|boolean',
    'unique_id' => 'nullable|string',

   ]);

   $beneficiary->update($data);

   return ResponseHelper::success($beneficiary, "Beneficiary updated successfully.");
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Delete a beneficiary.
  */
 public static function deleteBeneficiary(Request $request, int $beneficiary_id): JsonResponse
 {
  try {
   $user = auth()->user();
   $beneficiary = Beneficiary::where('user_id', $user->id)->where('id', $beneficiary_id)->first();

   if (!$beneficiary) {
    throw new AppException("Beneficiary not found.");
   }

   $beneficiary->delete();

   return ResponseHelper::success(null, "Beneficiary deleted successfully.");
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Mark a beneficiary as favorite.
  */
 public static function markAsFavorite(Request $request, int $beneficiary_id): JsonResponse
 {
  try {
   $user = auth()->user();
   $beneficiary = Beneficiary::where('user_id', $user->id)->where('id', $beneficiary_id)->first();

   if (!$beneficiary) {
    throw new AppException("Beneficiary not found.");
   }

   $beneficiary->update(['is_favorite' => true]);

   return ResponseHelper::success($beneficiary, "Beneficiary marked as favorite.");
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Unmark a beneficiary as favorite.
  */
 public static function unmarkAsFavorite(Request $request, int $beneficiary_id): JsonResponse
 {
  try {
   $user = auth()->user();
   $beneficiary = Beneficiary::where('user_id', $user->id)->where('id', $beneficiary_id)->first();

   if (!$beneficiary) {
    throw new AppException("Beneficiary not found.");
   }

   $beneficiary->update(['is_favorite' => false]);

   return ResponseHelper::success($beneficiary, "Beneficiary unmarked as favorite.");
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }

 /**
  * Get all favorite beneficiaries for a user.
  */
 public static function getFavoriteBeneficiaries(Request $request): JsonResponse
 {
  try {
   $user = auth()->user();
   $favorites = Beneficiary::where('user_id', $user->id)->where('is_favorite', true)->get();

   return ResponseHelper::success($favorites);
  } catch (AppException $e) {
   return ResponseHelper::unprocessableEntity($e->getMessage());
  }
 }
}