<?php

namespace App\Exceptions;

use Exception;

class OtpRequestTooSoonException extends Exception
{
    public function __construct()
    {
        parent::__construct('لطفاً دو دقیقه صبر کنید و سپس دوباره درخواست دهید.', 429);
    }
}
