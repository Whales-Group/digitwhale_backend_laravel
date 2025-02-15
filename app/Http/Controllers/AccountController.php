<?php

namespace App\Http\Controllers;

use App\Modules\AccountModule\AccountModuleMain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\Request;

class AccountController extends Controller
{
    protected AccountModuleMain $accountModuleMain;


    public function __construct(AccountModuleMain $accountModuleMain)
    {
        $this->accountModuleMain = $accountModuleMain;
    }


    public function getAccounts()
    {
        return $this->accountModuleMain->getAccounts();
    }

    public function getAccountDetails()
    {
        return $this->accountModuleMain->getAccountDetails();
    }

    public function updateAccount()
    {
        return $this->accountModuleMain->toggleEnabled();
    }

    public function createAccount()
    {
        return $this->accountModuleMain->createAccount();
    }


    public function addDocument()
    {
        return $this->accountModuleMain->addDocument();
    }

    public function getUserDocuments()
    {
        return $this->accountModuleMain->getUserDocuments();
    }

    public function getRequiredDocumentsByCountry()
    {
        return $this->accountModuleMain->getRequiredDocumentsByCountry();
    }

}