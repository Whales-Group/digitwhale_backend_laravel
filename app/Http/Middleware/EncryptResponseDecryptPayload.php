<?php

namespace App\Http\Middleware;

use App\Helpers\EncryptionHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EncryptResponseDecryptPayload
{
    /**
     * Handle an incoming request by decrypting the 'data' field and encrypting the response 'data' field.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Decrypt 'data' field from incoming payload if present
        if ($request->isJson() && $request->getContent()) {
            $encryptedPayload = $request->getContent();

            if (isset($encryptedPayload['data'])) {
                $decryptedData = EncryptionHelper::decrypt($encryptedPayload['data']);
                $encryptedPayload['data'] = json_decode($decryptedData, true);
                $request->replace($encryptedPayload);
            }
        }

        // Process the request
        $response = $next($request);

        // Encrypt the 'data' field in the response if it’s a JsonResponse
        if ($response instanceof JsonResponse) {
            $originalData = $response->getData(true);
            if (is_array($originalData) && isset($originalData['data'])) {
                $originalData['data'] = EncryptionHelper::encrypt(json_encode($originalData['data']));
                $response->setData($originalData);
            }
        }

        return $response;
    }
}