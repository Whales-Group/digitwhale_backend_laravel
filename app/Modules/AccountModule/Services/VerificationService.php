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
 public function addOrUpdateDocument(): JsonResponse
 {
  try {
   // Validate the request data
   $validatedData = request()->validate([
    'document_type_id' => 'required|integer|exists:document_types,id',
    'value' => 'required|string',
    'document_url' => 'nullable|url',
   ]);

   $user = auth()->user();
   $userId = $user->id;

   if (!auth()->user()->country_iso) {
    throw new AppException("Country on address not set.");
   }

   // Fetch all supported document types
   $supportedDocumentTypes = DocumentType::where('country_code', $user->country_iso)->get();

   foreach ($supportedDocumentTypes as $documentType) {
    // Check if a document of this type already exists for the user
    $existingDocument = UserDocument::where('user_id', $userId)
     ->where('document_type_id', $documentType->id)
     ->first();

    if (!$existingDocument) {
     // Create an empty entry for the user if it doesn't exist
     UserDocument::create([
      'user_id' => $userId,
      'document_type_id' => $documentType->id,
      'value' => '',
      'document_url' => '',
      'status' => 'None',
      'comment' => '',
     ]);
    }
   }

   // Check if a document of the same type already exists for the user
   $existingDocument = UserDocument::where('user_id', $userId)
    ->where('document_type_id', $validatedData['document_type_id'])
    ->first();

   if ($existingDocument) {
    // Update the existing document
    $existingDocument->update([
     'value' => $validatedData['value'],
     'document_url' => $validatedData['document_url'] ?? '',
     'status' => 'Pending',
     'comment' => 'Under review...',
    ]);

    // Update user table if document type is NIN or BVN
    $documentType = DocumentType::find($validatedData['document_type_id']);
    if ($documentType->name === 'NIN' || $documentType->name === 'BVN') {
     $user->update([
      strtolower($documentType->name) => $validatedData['value'],
     ]);
    }

    return ResponseHelper::success(message: 'Document updated successfully.', data: $existingDocument);
   } else {
    // Create a new document
    $validatedData['user_id'] = $userId;
    $validatedData['status'] = 'None';
    $validatedData['comment'] = '';

    $userDocument = UserDocument::create($validatedData);

    // Update user table if document type is NIN or BVN
    $documentType = DocumentType::find($validatedData['document_type_id']);
    if ($documentType->name === 'NIN' || $documentType->name === 'BVN') {
     $user->update([
      strtolower($documentType->name) => $validatedData['value'],
     ]);
    }

    return ResponseHelper::success(message: 'Document added successfully.', data: $userDocument);
   }
  } catch (QueryException $e) {
   // Handle database-level uniqueness violation
   if ($e->getCode() === '23000') { // SQLSTATE[23000]: Integrity constraint violation
    throw new AppException('A document of this type already exists for the user.');
   }
   throw new AppException('Failed to add or update document: ' . $e->getMessage());
  } catch (\Exception $e) {
   throw new AppException('Failed to add or update document: ' . $e->getMessage());
  }
 }
 /**
  * Get all documents for a user.
  *
  * @param int $userId
  * @return JsonResponse
  * @throws AppException
  */
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
   $user = auth()->user();
   $userId = $user->id;

   // Fetch all supported document types
   $supportedDocumentTypes = DocumentType::where('country_code', $user->country_iso)->get();

   foreach ($supportedDocumentTypes as $documentType) {
    // Check if a document of this type already exists for the user
    $existingDocument = UserDocument::where('user_id', $userId)
     ->where('document_type_id', $documentType->id)
     ->first();

    if (!$existingDocument) {
     // Create an empty entry for the user if it doesn't exist
     UserDocument::create([
      'user_id' => $userId,
      'document_type_id' => $documentType->id,
      'value' => '',
      'document_url' => '',
      'status' => 'None',
      'comment' => '',
     ]);
    }
   }

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