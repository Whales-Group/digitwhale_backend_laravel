<?php

namespace App\Modules\InvestmentModule\Contracts;

/**
 * Interface InvestmentInterface
 *
 * Contract for handling all investment-related operations
 * in the InvestmentModule.
 */
interface InvestmentInterface
{
    /**
     * Retrieve all available investment plans in the system.
     *
     * @return array List of investment plans with details like name, rate, duration, etc.
     */
    public function getAllPlans(): array;

    /**
     * Fetch the details of a specific investment plan using its ID.
     *
     * @param int $planId Unique identifier of the investment plan.
     * @return array Investment plan details including terms, rate, duration, etc.
     */
    public function getPlanById(int $planId): array;

    /**
     * Create a new investment for a given user under a specified plan.
     *
     * @param int $planId The ID of the selected investment plan.
     * @param float $amount The amount the user wants to invest.
     * @param int|null $userId Optional user ID (if not using authenticated context).
     * @return array Details of the newly created investment.
     */
    public function invest(int $planId, float $amount, ?int $userId = null): array;

    /**
     * Add more funds to an ongoing investment.
     *
     * @param int $investmentId The ID of the active investment.
     * @param float $amount The top-up amount to be added.
     * @return array Updated investment summary after the top-up.
     */
    public function topUp(int $investmentId, float $amount): array;

    /**
     * Withdraw funds from an investment either partially or fully.
     *
     * @param int $investmentId The ID of the investment.
     * @param float|null $amount Amount to cash out (null for full cashout).
     * @return array Result of the cash out action including balance and status.
     */
    public function cashOut(int $investmentId, ?float $amount = null): array;

    /**
     * Get a high-level summary of a user's investment portfolio.
     *
     * @param int $userId The ID of the user.
     * @return array Portfolio data including active plans, profits, and total value.
     */
    public function getUserPortfolio(int $userId): array;

    /**
     * Estimate potential returns based on amount, plan, and duration.
     *
     * @param int $planId Plan ID to be evaluated.
     * @param float $amount Investment amount.
     * @param int $durationInDays Intended investment duration in days.
     * @return array Returns breakdown including interest earned, rate, and total payout.
     */
    public function calculateReturns(int $planId, float $amount, int $durationInDays): array;

    /**
     * Withdraw only the earned profits (not capital) from an investment.
     *
     * @param int $investmentId ID of the investment to withdraw from.
     * @return array Transaction status and updated balance.
     */
    public function withdrawProfits(int $investmentId): array;

    /**
     * Retrieve all investments associated with a specific user.
     *
     * @param int $userId User's ID.
     * @return array List of investments, their status, and key metrics.
     */
    public function getUserInvestments(int $userId): array;

    /**
     * Cancel an active investment prematurely.
     *
     * @param int $investmentId The investment ID to cancel.
     * @return array Status of the cancellation, penalty (if any), and refund info.
     */
    public function cancelInvestment(int $investmentId): array;

    /**
     * Extend the duration of an active investment.
     *
     * @param int $investmentId The ID of the investment.
     * @param int $additionalDays Number of days to extend.
     * @return array Updated investment info after extension.
     */
    public function extendInvestment(int $investmentId, int $additionalDays): array;

    /**
     * Check whether an investment has matured.
     *
     * @param int $investmentId Investment ID to check.
     * @return array Maturity status, days remaining or days overdue.
     */
    public function checkMaturityStatus(int $investmentId): array;

    /**
     * Apply a referral bonus when a user joins or invests through a referral.
     *
     * @param int $referrerId ID of the referring user.
     * @param int $refereeId ID of the new user (referee).
     * @param float $amount Referral bonus amount.
     * @return array Result of the referral application and any updates to earnings.
     */
    public function applyReferralBonus(int $referrerId, int $refereeId, float $amount): array;

    /**
     * Get the total referral bonuses earned by a user.
     *
     * @param int $userId ID of the user whose earnings are to be retrieved.
     * @return array Total referral earnings and a breakdown of referrals.
     */
    public function getReferralEarnings(int $userId): array;
}
