<?php

namespace App\Exceptions;

use \Exception;

class WaveApiClientException extends Exception
{
    protected array $errors;

    public function setErrors(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}
