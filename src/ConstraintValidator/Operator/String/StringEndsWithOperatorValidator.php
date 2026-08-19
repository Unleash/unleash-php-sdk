<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

use Override;

/**
 * @internal
 */
final class StringEndsWithOperatorValidator extends AbstractStringOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    #[Override]
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return substr_compare($currentValue, $searchInValue, -strlen($searchInValue)) === 0;
    }
}
