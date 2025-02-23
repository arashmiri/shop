<?php

namespace App\Exceptions;

use Exception;

class OtpExpiredException extends Exception
{
    protected $message = 'کد تأیید منقضی شده است.';
}
