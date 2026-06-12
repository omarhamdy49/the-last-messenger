<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Business Rule Violation Exception
 *
 * Thrown when a business rule or domain invariant is violated.
 * Use for rules that go beyond simple validation.
 */
class BusinessRuleViolationException extends DomainException
{
    protected string $ruleName;

    /**
     * Create for named business rule
     *
     * @param  array<string, mixed>  $context
     */
    public static function forRule(string $ruleName, string $message, array $context = []): static
    {
        $exception = new static(
            message: $message,
            context: array_merge($context, ['rule' => $ruleName]),
            code: 409
        );

        $exception->ruleName = $ruleName;

        return $exception;
    }

    /**
     * Create for state transition violation
     */
    public static function invalidStateTransition(
        string $entity,
        string $currentState,
        string $targetState
    ): static {
        return static::forRule(
            ruleName: 'InvalidStateTransition',
            message: "لا يمكن تغيير حالة {$entity} من {$currentState} إلى {$targetState} | Cannot transition {$entity} from {$currentState} to {$targetState}",
            context: [
                'entity' => $entity,
                'current_state' => $currentState,
                'target_state' => $targetState,
            ]
        );
    }

    /**
     * Create for duplicate entity
     */
    public static function duplicate(string $entity, string $field, string $value): static
    {
        return static::forRule(
            ruleName: 'DuplicateEntity',
            message: "يوجد {$entity} بنفس القيمة {$value} في الحقل {$field} | {$entity} with {$field} '{$value}' already exists",
            context: [
                'entity' => $entity,
                'field' => $field,
                'value' => $value,
            ]
        );
    }

    /**
     * Create for operation not allowed
     */
    public static function operationNotAllowed(string $operation, string $reason): static
    {
        return static::forRule(
            ruleName: 'OperationNotAllowed',
            message: "العملية {$operation} غير مسموحة: {$reason} | Operation {$operation} not allowed: {$reason}",
            context: [
                'operation' => $operation,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Get the rule name
     */
    public function getRuleName(): string
    {
        return $this->ruleName ?? $this->context['rule'] ?? 'Unknown';
    }
}
