<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditException extends Exception
{
    public function __construct(string $message = 'Yeterli ders krediniz bulunmuyor.')
    {
        parent::__construct($message);
    }
}
