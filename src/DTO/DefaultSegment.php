<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultSegment implements Segment
{
    /**
     * @readonly
     */
    private int $id;
    /**
     * @var array<Constraint>
     * @readonly
     */
    private array $constraints;
    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(int $id, array $constraints)
    {
        $this->id = $id;
        $this->constraints = $constraints;
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
