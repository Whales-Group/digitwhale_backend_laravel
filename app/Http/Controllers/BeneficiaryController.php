<?php

namespace App\Http\Controllers;

use App\Modules\TransferModule\Services\TransactionBenefitiaryService;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    /**
     * Get all beneficiaries for a user.
     */
    public function getAllBeneficiaries(Request $request)
    {
        return TransactionBenefitiaryService::getAllBeneficiaries($request);
    }

    /**
     * Create a new beneficiary.
     */
    public function createBeneficiary(Request $request)
    {
        return TransactionBenefitiaryService::createBeneficiary($request);
    }

    /**
     * Update a beneficiary.
     */
    public function updateBeneficiary(Request $request, int $beneficiary_id)
    {
        return TransactionBenefitiaryService::updateBeneficiary($request, $beneficiary_id);
    }

    /**
     * Delete a beneficiary.
     */
    public function deleteBeneficiary(Request $request, int $beneficiary_id)
    {
        return TransactionBenefitiaryService::deleteBeneficiary($request, $beneficiary_id);
    }

    /**
     * Mark a beneficiary as favorite.
     */
    public function markAsFavorite(Request $request, int $beneficiary_id)
    {
        return TransactionBenefitiaryService::markAsFavorite($request, $beneficiary_id);
    }

    /**
     * Unmark a beneficiary as favorite.
     */
    public function unmarkAsFavorite(Request $request, int $beneficiary_id)
    {
        return TransactionBenefitiaryService::unmarkAsFavorite($request, $beneficiary_id);
    }

    /**
     * Get all favorite beneficiaries for a user.
     */
    public function getFavoriteBeneficiaries(Request $request)
    {
        return TransactionBenefitiaryService::getFavoriteBeneficiaries($request);
    }
}