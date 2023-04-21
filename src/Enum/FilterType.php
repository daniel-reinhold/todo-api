<?php

namespace App\Enum;

use App\Exception\InvalidFilterTypeException;

enum FilterType
{
    case Only;
    case Not;
    case Both;

    public function toString(): string {
        return match ($this) {
            FilterType::Only => "only",
            FilterType::Not => "not",
            FilterType::Both => "both",
        };
    }

    /**
     * Parse a filter type by a string
     * @param string $value The string to parse
     * @return FilterType
     * @throws InvalidFilterTypeException if no type matches
     */
    public static function parse(string $value): FilterType {
        return match (strtolower(trim($value))) {
            'only' => FilterType::Only,
            'not' => FilterType::Not,
            'both' => FilterType::Both,
            default => throw new InvalidFilterTypeException($value),
        };
    }

    public static function isSupported(string $value): bool {
        return in_array(
            needle: strtolower(trim($value)),
            haystack: [
                FilterType::Only->toString(),
                FilterType::Not->toString(),
                FilterType::Both->toString()
            ]
        );
    }
}
