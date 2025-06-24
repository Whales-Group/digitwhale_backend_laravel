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
            // $payedBillResponse = $this->flutterWaveService->payUtilityBill($item_code, $biller_code, $amount, $customer_id);

            $payedBillResponse = [
                'event' => 'singlebillpayment.status',
                'event.type' => 'SingleBillPayment',
                'data' => [
                    'customer' => '+2349068814392',
                    'amount' => 50,
                    'network' => 'MTN',
                    'tx_ref' => 'CF-FLYAPI-20250624012140281174009',
                    'flw_ref' => 'BPUSSD17507713008547596395',
                    'batch_reference' => null,
                    'customer_reference' => 'DigitWhale-90433820d8e714',
                    'status' => 'success',
                    'message' => 'Bill Payment was completed successfully',
                    'reference' => null,
                ],
            ];

            // Begin DB transaction
            DB::beginTransaction();

            // Validate and extract the 'data' portion
            $billData = $payedBillResponse['data'] ?? null;

            if (!$billData || !isset($billData['status']) || $billData['status'] !== 'success') {
                throw new AppException("Failed to process bill payment: Invalid or failed response.");
            }

            // Extract transaction details
            $transactionData = [
                'currency' => "NAIRA",
                'to_sys_account_id' => null,
                'to_user_name' => $billData["customer"] ?? "Unknown",
                'to_user_email' => $billData["email"] ?? null,
                'to_bank_name' => $billData["network"] ?? "Utility Provider",
                'to_bank_code' => null,
                'to_account_number' => $billData["customer"] ?? null,
                'transaction_reference' => $billData["tx_ref"] ?? CodeHelper::generateSecureReference(),
                'status' => 'successful',
                'type' => TransferType::BILL_PAYMENT,
                'amount' => $amount,
                'note' => "[Digitwhale/Transfer] | Bill Payment - " . ($billData["message"] ?? "Completed"),
                'entry_type' => 'debit',
                'charge' => 0
            ];

            // Register transaction
            $trp = $this->transactionService->registerTransaction($transactionData, TransferType::BILL_PAYMENT);

            // Create beneficiary
            $this->createBeneficiary($billData, $account_id);

            DB::commit();

            return ResponseHelper::success($trp);

        } catch (ClientException $e) {
            DB::rollBack();
            $responseBody = $e->getResponse()->getBody()->getContents();
            $errorData = json_decode($responseBody, true);
            throw new AppException($errorData['message'] ?? "Provider error");

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
        $network = strtolower($response['network'] ?? '');
        $customer = $response['customer'] ?? null;

        if (!$customer) {
            return; // no customer number? skip
        }

        $fallbackName = $response['network'] ?? 'Utility';

        // Smart type detection
        $type = match (true) {
            str_contains($network, 'airtime') || str_contains($response['tx_ref'] ?? '', 'airtime') => 'airtime',
            str_contains($network, 'prepaid') => 'prepaid_meter',
            str_contains($network, 'dstv') || str_contains($network, 'gotv') => 'cable',
            default => 'utility',
        };

        // Use network name if name not present
        $beneficiaryName = $response['name'] ?? $fallbackName;

        // Generate unique ID
        $unique_id = $customer . '_' . $response['network'];

        // Avoid duplicates
        $existing = Beneficiary::where('user_id', auth()->id())
            ->where('unique_id', $unique_id)
            ->first();

        if ($existing) {
            return;
        }

        // Prepare base data
        $beneficiaryData = [
            'user_id' => auth()->id(),
            'name' => $beneficiaryName,
            'type' => $type,
            'account_number' => $customer,
            'bank_name' => $beneficiaryName,
            'bank_code' => request()->get('bank_code'),
            'is_favorite' => false,
            'unique_id' => $unique_id,
            'amount' => request()->get('amount'),
        ];

        // Add type-specific details
        if ($type === 'airtime') {
            $beneficiaryData['network_provider'] = $response['network'];
            $beneficiaryData['phone_number'] = $customer;
        }

        if ($type === 'prepaid_meter') {
            $beneficiaryData['meter_number'] = $customer;
            $beneficiaryData['utility_type'] = 'electricity';
            $beneficiaryData['phone_number'] = request()->get('phone_number');
        }

        Beneficiary::create($beneficiaryData);
    }

}

//UTILITYBILLS
//BIL110
//UB134
