<?php

namespace Unleash\Client\Tests\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Client\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Event\FetchingDataFailedEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Tests\AbstractHttpClientTestCase;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;

final class RedirectLocationErrorMessageTest extends AbstractHttpClientTestCase
{
    use FakeCacheImplementationTrait;

    public function testRedirectResponseIncludesLocationInErrorMessage(): void
    {
        $caughtException = null;

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            UnleashEvents::FETCHING_DATA_FAILED,
            function (FetchingDataFailedEvent $event) use (&$caughtException): void {
                $caughtException = $event->getException();
            }
        );

        $bootstrapResponse = [
            'version' => 1,
            'features' => [],
        ];

        $repository = new DefaultUnleashRepository(
            new Client([
                'handler' => $this->handlerStack,
            ]),
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setCache($this->getCache())
                ->setEventDispatcher($eventDispatcher)
                ->setBootstrapProvider(new JsonSerializableBootstrapProvider($bootstrapResponse))
        );

        $this->mockHandler->append(new Response(
            308,
            ['Location' => 'https://unleash.com'],
            ''
        ));

        $repository->getFeatures();

        self::assertNotNull($caughtException, 'Expected a FetchingDataFailedEvent to be dispatched');
        self::assertStringContainsString(
            'https://unleash.com',
            $caughtException->getMessage(),
            'Error message should include the Location header value from the redirect response'
        );
    }
}
