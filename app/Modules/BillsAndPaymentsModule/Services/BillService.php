<?php

namespace App\Modules\BillsAndPaymentsModule\Services;

use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\Beneficiary;
use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\TransferModule\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BillService
{

    public FlutterWaveService $flutterWaveService;
    private TransactionService $transactionService;

    private TransferService $transferService;

    /**
     * @param FlutterWaveService $flutterWaveService
     * @param
     */
    public function __construct(TransactionService $transactionService, TransferService $transferService)
    {
        $this->flutterWaveService = FlutterWaveService::getInstance();
        $this->transactionService = $transactionService;
        $this->transferService = $transferService;
    }


    public function getBillCategories(): JsonResponse
    {
        return ResponseHelper::success($this->flutterWaveService->getBillCategories());
    }


    /**
     * @throws AppException
     */
    public function getBillerByCategory(): JsonResponse
    {
        $category = request()->route("category");
        $country = "NG";
        return ResponseHelper::success($this->flutterWaveService->getBillerByCategory($category, $country));
    }


    /**
     * @throws AppException
     */
    public function getBillerItems(): JsonResponse
    {
        $biller_code = request()->route("biller_code");
        return ResponseHelper::success($this->flutterWaveService->getBillerItems($biller_code));
    }


    /**
     * @throws AppException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function validateUserInformation()
    {
        $category = request()->route("category_id");
        $biller_code = request()->route("biller_code");
        $customer_id = request()->get("customer_id");
        return ResponseHelper::success($this->flutterWaveService->validateUserInformation($category, $biller_code, $customer_id));
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws AppException
     */
    public function purchaseBill()
    {
        // Get request parameters
        $item_code = request()->route("item_code");
        $biller_code = request()->route("biller_code");
        $customer_id = request()->get("customer_id");
        $amount = request()->get("amount");
        $account_id = request()->get("account_id");

        // Validate sender account
        $this->transferService->validateSenderAccount($account_id, "no_id", $amount);

        // Make payment and get response
        $payedBillResponse = $this->flutterWaveService->payUtilityBill($item_code, $biller_code, $amount, $customer_id);

//        $payedBillResponse = [
//            'airtime' => [
//                "response_code" => "00",
//                "address" => null,
//                "response_message" => "Successful",
//                "name" => "MTN Airtime",
//                "biller_code" => "BIL099",
//                "customer" => "08038291822",
//                "product_code" => "AT099",
//                "email" => null,
//                "fee" => 0,
//                "maximum" => 0,
//                "minimum" => 0
//            ]];
//        $payedBillResponse = [
//
//            'data' => [
//                "response_code" => "00",
//                "address" => null,
//                "response_message" => "Successful",
//                "name" => "MTN Data Bundle",
//                "biller_code" => "BIL097",
//                "customer" => "08031234567",
//                "product_code" => "DB001",
//                "email" => null,
//                "fee" => 0,
//                "maximum" => 0,
//                "minimum" => 0
//            ]];
//        $payedBillResponse = [
//            'utilities' => [
//                "response_code" => "00",
//                "address" => null,
//                "response_message" => "Successful",
//                "name" => "EKEDC PREPAID TOPUP",
//                "biller_code" => "BIL057",
//                "customer" => "123456789012",
//                "product_code" => "ET001",
//                "email" => null,
//                "fee" => 0,
//                "maximum" => 0,
//                "minimum" => 0
//            ]];
//        $payedBillResponse = [
//            'cable' => [
//                "response_code" => "00",
//                "address" => null,
//                "response_message" => "Successful",
//                "name" => "Test DSTV Account",
//                "biller_code" => "BIL119",
//                "customer" => "0025401100",
//                "product_code" => "CB141",
//                "email" => null,
//                "fee" => 0,
//                "maximum" => 0,
//                "minimum" => 0
//            ]
//        ];


        // Flatten the dynamic response format
        $billData = collect($payedBillResponse)->first();

        if (!$billData || !isset($billData['response_code'])) {
            throw new AppException("Unknown response type");
        }

        // Extract transaction details
        $transactionData = [
            'currency' => "NAIRA",
            'to_sys_account_id' => null,
            'to_user_name' => $billData["name"],
            'to_user_email' => $billData["email"] ?? null,
            'to_bank_name' => $billData["name"],
            'to_bank_code' => null,
            'to_account_number' => $billData["customer"],
            'transaction_reference' => CodeHelper::generateSecureReference(),
            'status' => 'successful',
            'type' => TransferType::BILL_PAYMENT,
            'amount' => $amount,
            'note' => "[Digitwhale/Transfer] | Bill Payment",
            'entry_type' => 'debit',
        ];

        // Register transaction
        $trp = $this->transactionService->registerTransaction($transactionData, TransferType::BILL_PAYMENT);

        // Create beneficiary (optional and based on bill type)
        $this->createBeneficiary($billData, $account_id);

        return ResponseHelper::success($trp);
    }

    /**
     * @throws AppException
     */
    public function createBeneficiary(array $response, $account_id): void
    {
        // Define the type based on biller_code or bill type
        $type = match (true) {
            str_contains(strtolower($response['name']), 'airtime') => 'airtime',
            str_contains(strtolower($response['name']), 'prepaid') => 'prepaid_meter',
            str_contains(strtolower($response['name']), 'dstv') || str_contains(strtolower($response['name']), 'gotv') => 'cable',
            default => 'utility',
        };

        $unique_id = $response['customer'] . '_' . $response['biller_code'];

        // Check for existing beneficiary
        $existing = Beneficiary::where('user_id', auth()->id())
            ->where('unique_id', $unique_id)
            ->first();

        if ($existing) return;

        // Construct beneficiary data
        $beneficiaryData = [
            'user_id' => auth()->id(),
            'name' => $response['name'],
            'type' => $type,
            'account_number' => $response['customer'],
            'bank_name' => $response['name'],
            'bank_code' => request()->get('bank_code'),
            'is_favorite' => false,
            'unique_id' => $unique_id,
            'amount' => request()->get('amount'),
        ];

        // Add extras based on type
        if ($type === 'airtime') {
            $beneficiaryData['network_provider'] = $response['name'];
            $beneficiaryData['phone_number'] = $response['customer'];
        }

        if ($type === 'prepaid_meter') {
            $beneficiaryData['meter_number'] = $response['customer'];
            $beneficiaryData['utility_type'] = 'electricity';
            $beneficiaryData['phone_number'] = request()->get('phone_number');
        }

        Beneficiary::create($beneficiaryData);
    }
}

//UTILITYBILLS
//BIL110
//UB134
