<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

use Override;

/**
 * @internal
 */
final class DateAfterOperatorValidator extends AbstractDateOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    #[Override]
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) > $this->convert($searchInValue);
    }
}
