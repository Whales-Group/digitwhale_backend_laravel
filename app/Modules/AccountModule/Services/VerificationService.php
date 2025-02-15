<?php

namespace App\Modules\AccountModule\Services;

use App\Common\Helpers\ResponseHelper;
use App\Exceptions\AppException;
use App\Models\DocumentType;
use App\Models\UserDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationService
{
 /**
  * Add a document for a user.
  *
  * @param Request $request
  * @return JsonResponse
  * @throws AppException
  */
 public function addDocument(): JsonResponse
 {
  try {
   // Validate the request data
   $validatedData = request()->validate([
    'document_type_id' => 'required|integer|exists:document_types,id',
    'value' => 'required|string',
    'document_url' => 'required|url',
   ]);

   $userId = auth()->user()->id;

   // Check if a document of the same type already exists for the user
   $existingDocument = UserDocument::where('user_id', $userId)
    ->where('document_type_id', $validatedData['document_type_id'])
    ->first();

   if ($existingDocument) {
    throw new AppException('A document of this type already exists for the user.');
   }
   $validatedData['user_id'] = $userId;
   $validatedData['status'] = 'Pending';
   $validatedData['comment'] = 'Under review...';

   // Create the user document
   $userDocument = UserDocument::create($validatedData);

   return ResponseHelper::success(message: 'Document added successfully.', data: $userDocument);
  } catch (QueryException $e) {
   // Handle database-level uniqueness violation
   if ($e->getCode() === '23000') { // SQLSTATE[23000]: Integrity constraint violation
    throw new AppException('A document of this type already exists for the user.');
   }
   throw new AppException('Failed to add document: ' . $e->getMessage());
  } catch (\Exception $e) {
   throw new AppException('Failed to add document: ' . $e->getMessage());
  }
 }
 /**
  * Get all documents for a user.
  *
  * @param int $userId
  * @return JsonResponse
  * @throws AppException
  */
 public function getUserDocuments(): JsonResponse
 {
  try {
   $userId = auth()->user()->id;

   // Fetch all documents for the user
   $documents = UserDocument::where('user_id', $userId)
    ->with('documentType')
    ->get();

   return ResponseHelper::success(message: 'User documents retrieved successfully.', data: $documents);
  } catch (\Exception $e) {
   throw new AppException('Failed to retrieve user documents: ' . $e->getMessage());
  }
 }

 /**
  * Get required documents for a user based on their country ISO.
  *
  * @query string $countryCode
  * @return JsonResponse
  * @throws AppException
  */
 public function getRequiredDocumentsByCountry(): JsonResponse
 {
  try {
   $countryCode = request()->query('country_code');
   // Fetch required document types for the country
   $requiredDocuments = DocumentType::where('country_code', $countryCode)
    ->where('is_required', true)
    ->get();

   return ResponseHelper::success(message: 'Required documents retrieved successfully.', data: $requiredDocuments);
  } catch (\Exception $e) {
   throw new AppException('Failed to retrieve required documents: ' . $e->getMessage());
  }
 }
}