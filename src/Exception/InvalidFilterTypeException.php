<?php

namespace App\Exception;

use App\Enum\FilterType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidFilterTypeException extends HttpException
{
    public function __construct(String $actual)
    {
        parent::__construct(
            statusCode: 400,
            message:
                "Invalid filter type:" .
                ". Expected [" . implode(', ', FilterType::AVAILABLE_OPTIONS) . "]. Got $actual."
        );
    }
}