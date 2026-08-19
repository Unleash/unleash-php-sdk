<?php

namespace Unleash\Client\Tests\ConstraintValidator;

use DateTimeImmutable;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Tests\AbstractHttpClientTestCase;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class ConstraintTest extends AbstractHttpClientTestCase
{
    use FakeCacheImplementationTrait;

    /**
     * @var DefaultUnleash
     */
    private $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instance = new DefaultUnleash(
            [new DefaultStrategyHandler()],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
    }

    public function testInvalidVersion()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => 'version55',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5');

        self::assertFalse($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionDisregardsConstraintMetadata()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => '1.5.5+build.322',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionDisregardsContextMetadata()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => '1.5.5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5+build.322');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionAllMetadataAreEqual()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => '1.5.5+metadata',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5+build.322');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionPreReleaseWithMetadataAreEqual()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_EQ',
                                    'value' => '1.5.5-alpha.1+metadata',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5-alpha.1+build.322');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionMetadataNotGreaterThan()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_GT',
                                    'value' => '1.5.5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5+build.322');

        self::assertFalse($this->instance->isEnabled('test', $context));
    }

    public function testSemVerVersionNotLesserThanMetadata()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_LT',
                                    'value' => '1.5.5+build.322',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.5');

        self::assertFalse($this->instance->isEnabled('test', $context));
    }

    public function testVersionGTEIsGreaterThan()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_GTE',
                                    'value' => '1.5.5+build.322',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.6');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testVersionGTENotGreaterOrEqual()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_GTE',
                                    'value' => '1.5.5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.4+build.322');

        self::assertFalse($this->instance->isEnabled('test', $context));
    }

    public function testVersionLTEIsLessThan()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_LTE',
                                    'value' => '1.5.5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.4+build.322');

        self::assertTrue($this->instance->isEnabled('test', $context));
    }

    public function testVersionLTENotLesserOrEqual()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'SEMVER_LTE',
                                    'value' => '1.5.5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $context = (new UnleashContext())->setCustomProperty('version', '1.5.6+build.322');

        self::assertFalse($this->instance->isEnabled('test', $context));
    }

    public function testInvalidOperator()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'version',
                                    'operator' => 'invalid_operator',
                                    'value' => 'version55',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($this->instance->isEnabled('test'));
    }

    /**
     * @see https://github.com/Unleash/unleash-php-sdk/issues/151
     */
    public function testDateBeforeGetValues()
    {
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'constraints' => [
                                [
                                    'contextName' => 'currentTime',
                                    'operator' => ConstraintOperator::DATE_BEFORE,
                                    'value' => (new DateTimeImmutable('+1 day'))->format('c'),
                                    'values' => [],
                                    'inverted' => false,
                                    'caseInsensitive' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($this->instance->isEnabled('test'));
    }
}
