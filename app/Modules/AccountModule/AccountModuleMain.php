<?php

namespace App\Modules\AccountModule;

use App\Modules\AccountModule\Services\AccountCreationService;
use App\Modules\AccountModule\Services\GetAndUpdateAccountService;
use App\Modules\AccountModule\Services\VerificationService;
use Illuminate\Http\Client\Request;

class AccountModuleMain
{
    public GetAndUpdateAccountService $getAccountService;

    public AccountCreationService $accountCreationService;

    public VerificationService $verificationService;

    public function __construct(
        GetAndUpdateAccountService $getAccountService,
        AccountCreationService $accountCreationService,
        VerificationService $verificationService
    ) {
        $this->getAccountService = $getAccountService;
        $this->accountCreationService = $accountCreationService;
        $this->verificationService = $verificationService;
    }

    public function createAccount()
    {
        return $this->accountCreationService->createAccount();
    }

    public function getAccounts()
    {
        return $this->getAccountService->getAccount();
    }

    public function getAccountDetails()
    {
        return $this->getAccountService->getAccountDetails();
    }

    public function toggleEnabled()
    {
        return $this->getAccountService->toggleEnabled();
    }

    public function addDocument()
    {
        return $this->verificationService->addDocument();
    }

    public function getUserDocuments()
    {
        return $this->verificationService->getUserDocuments();
    }

    public function getRequiredDocumentsByCountry()
    {
        return $this->verificationService->getRequiredDocumentsByCountry();
    }


}
