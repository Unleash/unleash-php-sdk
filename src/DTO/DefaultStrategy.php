<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultStrategy implements Strategy
{
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     * @param array<Segment>       $segments
     * @param array<Variant>       $variants
     */
    public function __construct(
        /**
         * @readonly
         */
        private string $name,
        /**
         * @readonly
         */
        private array $parameters = [],
        /**
         * @readonly
         */
        private array $constraints = [],
        /**
         * @readonly
         */
        private array $segments = [],
        /**
         * @readonly
         */
        private bool $nonexistentSegments = false,
        /**
         * @readonly
         */
        private array $variants = [],
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<Constraint>
     */
    #[Override]
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return array<Segment>
     */
    #[Override]
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @return array<Variant>
     */
    #[Override]
    public function getVariants(): array
    {
        return $this->variants;
    }

    #[Override]
    public function hasNonexistentSegments(): bool
    {
        return $this->nonexistentSegments;
    }
}
