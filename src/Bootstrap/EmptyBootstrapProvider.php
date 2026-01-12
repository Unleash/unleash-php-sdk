<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Override;
use Traversable;

final class EmptyBootstrapProvider implements BootstrapProvider
{
    /**
     * @return mixed[]|\JsonSerializable|\Traversable|null
     */
    #[Override]
    public function getBootstrap()
    {
        return null;
    }
}
