<?php

namespace App\Exception;

use App\Enum\FilterType;
use App\Enum\SortOrder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSortOrderException extends HttpException
{
    public function __construct(String $actual)
    {
        parent::__construct(
            statusCode: 400,
            message:
                "Invalid sort order:" .
                ". Expected [" . implode(', ', SortOrder::AVAILABLE_OPTIONS) . "]. Got $actual."
        );
    }
}