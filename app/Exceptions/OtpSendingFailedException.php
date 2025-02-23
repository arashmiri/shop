<?php

namespace App\Exceptions;

use Exception;

class OtpSendingFailedException extends Exception
{
    protected $message = 'هنگام ارسال کد تایید مشکلی پیش امده است';
}
