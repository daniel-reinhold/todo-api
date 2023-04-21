<?php

namespace App\Enum;

use App\Exception\InvalidSortOrderException;

enum SortOrder
{
    case ASC;
    case DESC;

    public function toString(): string {
        return match ($this) {
            SortOrder::ASC => 'asc',
            SortOrder::DESC => 'desc'
        };
    }

    public function asSQL(): string {
        return match ($this) {
            SortOrder::ASC => 'ASC',
            SortOrder::DESC => 'DESC'
        };
    }

    public static function parse(string $value): SortOrder {
        return match (strtolower(trim($value))) {
            'asc' => SortOrder::ASC,
            'desc' => SortOrder::DESC,
            default => throw new InvalidSortOrderException($value)
        };
    }

    public static function isSupported(string $value): bool {
        return in_array(
            needle: strtolower(trim($value)),
            haystack: [
                SortOrder::ASC->toString(),
                SortOrder::DESC->toString()
            ]
        );
    }
}
