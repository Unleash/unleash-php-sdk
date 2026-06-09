<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

use Override;

/**
 * @internal
 */
final class StringStartsWithOperatorValidator extends AbstractStringOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    #[Override]
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return strncmp($currentValue, $searchInValue, strlen($searchInValue)) === 0;
    }
}
