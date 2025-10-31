<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Filter Operator Enum
 * Defines available filter operators
 */
enum FilterOperator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case GREATER_THAN = '>';
    case GREATER_THAN_OR_EQUAL = '>=';
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case LIKE = 'like';
    case NOT_LIKE = 'not_like';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case BETWEEN = 'between';
    case NULL = 'null';
    case NOT_NULL = 'not_null';
    case CONTAINS = 'contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case FULL_TEXT = 'full_text';

    /**
     * Get operator label
     */
    public function label(): string
    {
        return match ($this) {
            self::EQUALS => 'Equals',
            self::NOT_EQUALS => 'Not Equals',
            self::GREATER_THAN => 'Greater Than',
            self::GREATER_THAN_OR_EQUAL => 'Greater Than or Equal',
            self::LESS_THAN => 'Less Than',
            self::LESS_THAN_OR_EQUAL => 'Less Than or Equal',
            self::LIKE => 'Like',
            self::NOT_LIKE => 'Not Like',
            self::IN => 'In',
            self::NOT_IN => 'Not In',
            self::BETWEEN => 'Between',
            self::NULL => 'Is Null',
            self::NOT_NULL => 'Is Not Null',
            self::CONTAINS => 'Contains',
            self::STARTS_WITH => 'Starts With',
            self::ENDS_WITH => 'Ends With',
            self::FULL_TEXT => 'Full Text Search',
        };
    }

    /**
     * Check if operator requires value
     */
    public function requiresValue(): bool
    {
        return ! in_array($this, [self::NULL, self::NOT_NULL]);
    }

    /**
     * Check if operator requires array value
     */
    public function requiresArray(): bool
    {
        return in_array($this, [self::IN, self::NOT_IN, self::BETWEEN]);
    }
}
