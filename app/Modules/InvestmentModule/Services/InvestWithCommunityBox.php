<?php

namespace App\Modules\InvestmentModule\Services;

use App\Modules\InvestmentModule\Contracts\InvestmentInterface;

class InvestWithCommunityBox implements InvestmentInterface
{
	public function applyReferralBonus(int $referrerId, int $refereeId, float $amount): array
	{
		// TODO: Implement applyReferralBonus() method.
		return [];
	}

	public function calculateReturns(int $planId, float $amount, int $durationInDays): array
	{
		// TODO: Implement calculateReturns() method.
		return [];
	}

	public function cancelInvestment(int $investmentId): array
	{
		// TODO: Implement cancelInvestment() method.
		return [];
	}

	public function cashOut(int $investmentId, float|null $amount = null): array
	{
		// TODO: Implement cashOut() method.
		return [];
	}

	public function checkMaturityStatus(int $investmentId): array
	{
		// TODO: Implement checkMaturityStatus() method.
		return [];
	}

	public function extendInvestment(int $investmentId, int $additionalDays): array
	{
		// TODO: Implement extendInvestment() method.
		return [];
	}

	public function getAllPlans(): array
	{
		// TODO: Implement getAllPlans() method.
		return [];
	}

	public function getPlanById(int $planId): array
	{
		// TODO: Implement getPlanById() method.
		return [];
	}

	public function getReferralEarnings(int $userId): array
	{
		// TODO: Implement getReferralEarnings() method.
		return [];
	}

	public function getUserInvestments(int $userId): array
	{
		// TODO: Implement getUserInvestments() method.
		return [];
	}

	public function getUserPortfolio(int $userId): array
	{
		// TODO: Implement getUserPortfolio() method.
		return [];
	}

	public function invest(int $planId, float $amount, int|null $userId = null): array
	{
		// TODO: Implement invest() method.
		return [];
	}

	public function topUp(int $investmentId, float $amount): array
	{
		// TODO: Implement topUp() method.
		return [];
	}

	public function withdrawProfits(int $investmentId): array
	{
		// TODO: Implement withdrawProfits() method.
		return [];
	}
}