<?php

namespace App\Dtos\PaystackDtos\DVADto;

class DVACreationData
{
    /**
     * @var string The account name of the DVA.
     */
    public $account_name;

    /**
     * @var string The account number of the DVA.
     */
    public $account_number;

    /**
     * @var bool Indicates whether the DVA is assigned.
     */
    public $assigned;

    /**
     * @var string The currency in which the DVA operates.
     */
    public $currency;

    /**
     * @var bool Indicates whether the DVA is active.
     */
    public $active;

    /**
     * @var Bank|null The bank associated with the DVA.
     */
    public $bank;

    /**
     * @var Customer|null The customer to whom the DVA is assigned.
     */
    public $customer;
}
