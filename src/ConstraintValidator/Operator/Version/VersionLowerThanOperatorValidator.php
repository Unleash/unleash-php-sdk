<?php

namespace Unleash\Client\ConstraintValidator\Operator\Version;

use Override;

/**
 * @internal
 */
final class VersionLowerThanOperatorValidator extends AbstractVersionOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    #[Override]
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return version_compare($currentValue, $searchInValue, 'lt');
    }
}
