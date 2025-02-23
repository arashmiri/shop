<?php

namespace App\Exceptions;

use Exception;

class OtpInvalidException extends Exception
{
    protected $message = 'کد تأیید نامعتبر است.';
}
