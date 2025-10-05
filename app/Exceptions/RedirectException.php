<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class RedirectException extends Exception
{
    private string $route;

    public function __construct(string $message, string $route, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this -> route = $route;
    }

    public function getRoute(): string
    {
        return $this -> route;
    }

    public function flashError(): void
    {
        session() -> flash('errormessage', $this -> message);
    }
}
