<?php

namespace App\Modules\BillsAndPaymentsModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentCyclesService
{
    /**
     * Store user payment patterns for intelligent prediction.
     */
    protected array $userPaymentHistory = [];

    /**
     * Schedule a new recurring payment.
     *
     * @param int $userId
     * @param string $billType
     * @param float $amount
     * @param Carbon $startDate
     * @param int $cycleDays
     * @return array
     */
    public function scheduleRecurring(int $userId, string $billType, float $amount, Carbon $startDate, int $cycleDays): array
    {
        return [
            'status' => 'scheduled',
            'next_payment' => $startDate->copy()->addDays($cycleDays),
            'cycle' => $cycleDays,
            'bill_type' => $billType,
        ];
    }

    /**
     * Track a payment and analyze its timeliness.
     *
     * @param int $userId
     * @param Carbon $expectedDate
     * @param Carbon $actualDate
     */
    public function trackPayment(int $userId, Carbon $expectedDate, Carbon $actualDate): void
    {
        $difference = $expectedDate->diffInDays($actualDate, false);

        $this->userPaymentHistory[$userId][] = $difference;
    }

    /**
     * Suggest optimal recurring day count based on payment history.
     *
     * @param int $userId
     * @param int $defaultCycle
     * @return int
     */
    public function suggestOptimizedCycle(int $userId, int $defaultCycle = 30): int
    {
        if (!isset($this->userPaymentHistory[$userId])) {
            return $defaultCycle;
        }

        $averageShift = collect($this->userPaymentHistory[$userId])->avg();
        return max(15, $defaultCycle + round($averageShift));
    }

    /**
     * Get the next payment date for a given user and last payment date.
     *
     * @param Carbon $lastPaymentDate
     * @param int $userId
     * @param int $defaultCycle
     * @return Carbon
     */
    public function getNextPaymentDate(Carbon $lastPaymentDate, int $userId, int $defaultCycle = 30): Carbon
    {
        $cycle = $this->suggestOptimizedCycle($userId, $defaultCycle);
        return $lastPaymentDate->copy()->addDays($cycle);
    }
}
