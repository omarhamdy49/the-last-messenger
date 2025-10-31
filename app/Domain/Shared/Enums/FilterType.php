<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Filter Type Enum
 * Defines available filter types
 */
enum FilterType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case RANGE = 'range';
    case DATE_RANGE = 'date_range';
    case FULL_TEXT = 'full_text';

    /**
     * Get type label
     */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::NUMBER => 'Number',
            self::DATE => 'Date',
            self::BOOLEAN => 'Boolean',
            self::SELECT => 'Select',
            self::MULTISELECT => 'Multi-Select',
            self::RANGE => 'Range',
            self::DATE_RANGE => 'Date Range',
            self::FULL_TEXT => 'Full Text',
        };
    }

    /**
     * Get valid operators for this type
     *
     * @return array<FilterOperator>
     */
    public function validOperators(): array
    {
        return match ($this) {
            self::TEXT => [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::LIKE,
                FilterOperator::NOT_LIKE,
                FilterOperator::CONTAINS,
                FilterOperator::STARTS_WITH,
                FilterOperator::ENDS_WITH,
            ],
            self::NUMBER => [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::GREATER_THAN,
                FilterOperator::GREATER_THAN_OR_EQUAL,
                FilterOperator::LESS_THAN,
                FilterOperator::LESS_THAN_OR_EQUAL,
                FilterOperator::BETWEEN,
            ],
            self::DATE, self::DATE_RANGE => [
                FilterOperator::EQUALS,
                FilterOperator::GREATER_THAN,
                FilterOperator::GREATER_THAN_OR_EQUAL,
                FilterOperator::LESS_THAN,
                FilterOperator::LESS_THAN_OR_EQUAL,
                FilterOperator::BETWEEN,
            ],
            self::BOOLEAN => [
                FilterOperator::EQUALS,
            ],
            self::SELECT => [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
            ],
            self::MULTISELECT => [
                FilterOperator::IN,
                FilterOperator::NOT_IN,
            ],
            self::RANGE => [
                FilterOperator::BETWEEN,
            ],
            self::FULL_TEXT => [
                FilterOperator::FULL_TEXT,
            ],
        };
    }
}
