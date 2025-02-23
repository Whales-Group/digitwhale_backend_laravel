<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncryptionController extends Controller
{
    /**
     * Encrypt the provided data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function encrypt(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'required',
        ]);

        try {
            $encryptedData = EncryptionHelper::encrypt($request->input('data'));
            return ResponseHelper::success([
                'encrypted_data' => $encryptedData,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error('Encryption failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Decrypt the provided encrypted data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function decrypt(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'required|string', // Must be a base64-encoded string
        ]);

        try {
            $decryptedData = EncryptionHelper::decrypt($request->input('data'));
            return ResponseHelper::success([
                'data' => $decryptedData,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error('Decryption failed: ' . $e->getMessage(), 500);
        }
    }
}