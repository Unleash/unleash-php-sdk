<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    /**
     * @readonly
     * @var string
     */
    private $contextName;
    /**
     * @readonly
     * @var string
     */
    private $operator;
    /**
     * @var array<string>
     * @readonly
     */
    private $values;
    /**
     * @readonly
     * @var string|null
     */
    private $singleValue;
    /**
     * @readonly
     * @var bool
     */
    private $inverted = false;
    /**
     * @readonly
     * @var bool
     */
    private $caseInsensitive = false;
    /**
     * @param array<string> $values
     */
    public function __construct(
        string $contextName,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\ConstraintOperator::class)]
        string $operator,
        ?array $values = null,
        ?string $singleValue = null,
        bool $inverted = false,
        bool $caseInsensitive = false
    )
    {
        $this->contextName = $contextName;
        $this->operator = $operator;
        $this->values = $values;
        $this->singleValue = $singleValue;
        $this->inverted = $inverted;
        $this->caseInsensitive = $caseInsensitive;
    }

    #[Override]
    public function getContextName(): string
    {
        return $this->contextName;
    }

    #[Override]
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array<string>|null
     */
    #[Override]
    public function getValues(): ?array
    {
        return $this->values;
    }

    #[Override]
    public function getSingleValue(): ?string
    {
        return $this->singleValue;
    }

    #[Override]
    public function isInverted(): bool
    {
        return $this->inverted;
    }

    #[Override]
    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }
}
