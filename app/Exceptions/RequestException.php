<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class RequestException extends Exception
{

    public function flashError(): void
    {
        session()->flash('errormessage', $this->getMessage());
    }
}
