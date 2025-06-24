<?php

namespace App\Modules\BillsAndPaymentsModule\Services;

use App\Enums\ErrorCode;
use App\Enums\TransferType;
use App\Exceptions\AppException;
use App\Exceptions\CodedException;
use App\Gateways\FlutterWave\Services\FlutterWaveService;
use App\Helpers\CodeHelper;
use App\Helpers\ResponseHelper;
use App\Models\AppLog;
use App\Models\Beneficiary;
use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\TransferModule\Services\TransferService;
use DB;
use Exception;
use GuzzleHttp\Exception\ClientException;
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


    public function purchaseBill()
    {
        try {
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

            // Begin DB transaction
            DB::beginTransaction();

            // Validate response
            if (!$payedBillResponse || !isset($payedBillResponse['status']) || $payedBillResponse['status'] !== 'success') {
                throw new AppException("Failed to process bill payment: Invalid or failed response.");
            }

            // Extract transaction details
            $transactionData = [
                'currency' => "NAIRA",
                'to_sys_account_id' => null,
                'to_user_name' => $payedBillResponse["customer"] ?? "Unknown",
                'to_user_email' => $payedBillResponse["email"] ?? null,
                'to_bank_name' => $payedBillResponse["network"] ?? "Utility Provider",
                'to_bank_code' => null,
                'to_account_number' => $payedBillResponse["customer"],
                'transaction_reference' => $payedBillResponse["tx_ref"] ?? CodeHelper::generateSecureReference(),
                'status' => 'successful',
                'type' => TransferType::BILL_PAYMENT,
                'amount' => $amount,
                'note' => "[Digitwhale/Transfer] | Bill Payment - " . ($payedBillResponse["message"] ?? "Completed"),
                'entry_type' => 'debit',
            ];

            // Register transaction
            $trp = $this->transactionService->registerTransaction($transactionData, TransferType::BILL_PAYMENT);

            // Create beneficiary
            $this->createBeneficiary($payedBillResponse, $account_id);

            // Commit DB transaction
            DB::commit();

            return ResponseHelper::success($trp);

        } catch (ClientException $e) {
            DB::rollBack();
            $responseBody = $e->getResponse()->getBody()->getContents();
            $errorData = json_decode($responseBody, true);
            throw new AppException($errorData['message'] ?? "Flutterwave client error");

        } catch (Exception $e) {
            DB::rollBack();
            AppLog::error($e->getMessage());
            throw new CodedException(ErrorCode::INSUFFICIENT_PROVIDER_BALANCE, $e->getMessage());
        }
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

        if ($existing)
            return;

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
