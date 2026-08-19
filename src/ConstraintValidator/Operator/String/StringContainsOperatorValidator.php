<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

use Override;

/**
 * @internal
 */
final class StringContainsOperatorValidator extends AbstractStringOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    #[Override]
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return strpos($currentValue, $searchInValue) !== false;
    }
}
