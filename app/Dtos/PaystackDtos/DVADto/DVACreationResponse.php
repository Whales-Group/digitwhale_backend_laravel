<?php

namespace App\Dtos\PaystackDtos\DVADto;

class DVACreationResponse
{
    /**
     * @var bool Indicates whether the request was successful.
     */
    public $status;

    /**
     * @var string Contains the message from the API response.
     */
    public $message;

    /**
     * @var DVACreationData|null Contains the DVA creation data (if available).
     */
    public $data;
}
