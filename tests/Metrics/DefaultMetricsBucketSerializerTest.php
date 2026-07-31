<?php

namespace Unleash\Client\Tests\Metrics;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\Metrics\DefaultMetricsBucketSerializer;
use Unleash\Client\Metrics\MetricsBucket;
use Unleash\Client\Metrics\MetricsBucketToggle;

final class DefaultMetricsBucketSerializerTest extends TestCase
{
    /**
     * @dataProvider serializeDeserializeData
     */
    public function testSerializeDeserialize(MetricsBucket $bucket)
    {
        $instance = new DefaultMetricsBucketSerializer();
        $deserialized = $instance->deserialize($instance->serialize($bucket));

        self::assertSame($bucket->jsonSerialize(), $deserialized->jsonSerialize());
    }

    public function serializeDeserializeData(): iterable
    {
        yield [new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds'))];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true)),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false)),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    false,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test2', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test3', true)
                )),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                )),
        ];
        // several features, only some of them with variants
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('featureA', true, []),
                    true,
                    new DefaultVariant('variantA', true)
                ))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('featureA', true, []), false))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('featureB', true, []), true)),
        ];
    }

    public function testSerializedSizeDoesNotGrowWithEvaluationCount()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $tenEvaluations = $this->bucketWithRepeatedEvaluations(10);
        $tenThousandEvaluations = $this->bucketWithRepeatedEvaluations(10000);

        // only the count itself gets longer, by 3 digits
        self::assertSame(
            strlen($instance->serialize($tenEvaluations)) + 3,
            strlen($instance->serialize($tenThousandEvaluations))
        );
    }

    /**
     * Entries without a count are what previous versions wrote, and mean one evaluation, so a
     * bucket stored before an upgrade is read in full rather than discarded.
     *
     * @dataProvider previousFormatData
     */
    public function testDeserializePreviousFormat(string $serialized, ?int $endTimestamp, array $expectedToggles)
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = $instance->deserialize($serialized);
        $bucket->setEndDate(new DateTimeImmutable('@1700000060'));

        self::assertSame(1700000000, $bucket->getStartDate()->getTimestamp());
        self::assertSame($endTimestamp, $bucket->getEndDate()->getTimestamp());
        self::assertSame($expectedToggles, $bucket->jsonSerialize()['toggles']);
    }

    public function previousFormatData(): iterable
    {
        yield ['1700000000;;~', 1700000060, []];
        yield ['1700000000;;1700000030', 1700000060, []];
        yield [
            '1700000000;test:1:~,test:1:~,test:0:~;1700000030',
            1700000060,
            ['test' => ['yes' => 2, 'no' => 1]],
        ];
        yield [
            '1700000000;test:1:variantA,test:0:variantA,test:1:~;~',
            1700000060,
            ['test' => ['yes' => 2, 'no' => 1, 'variants' => ['variantA' => 2]]],
        ];
    }

    /**
     * A payload written now has to stay readable by versions that don't know about counts: they
     * see one evaluation per entry, which under-counts the interval they flush but doesn't throw.
     */
    public function testSerializeKeepsTheShapeOfThePreviousFormat()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = $this->bucketWithRepeatedEvaluations(3);
        $bucket->addToggle(new MetricsBucketToggle(new DefaultFeature('other', true, []), false));

        self::assertSame('1700000000;test:1:variant:3,other:0:~:1;~', $instance->serialize($bucket));

        $bucket->setEndDate(new DateTimeImmutable('@1700000060'));
        self::assertSame(
            '1700000000;test:1:variant:3,other:0:~:1;1700000060',
            $instance->serialize($bucket)
        );
    }

    /**
     * One entry per feature, outcome and variant combination, so a feature evaluated both ways
     * takes two entries and a variant adds one each. The number of entries is bounded by how the
     * features are configured, not by how often they are evaluated, which is the point.
     */
    public function testSerializeWritesOneEntryPerOutcomeAndVariantCombination()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = new MetricsBucket(new DateTimeImmutable('@1700000000'), new DateTimeImmutable('@1700000060'));
        foreach ([['blue', 300], ['green', 100]] as [$variantName, $evaluations]) {
            for ($i = 0; $i < $evaluations; ++$i) {
                $bucket->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('flag', true, []),
                    true,
                    new DefaultVariant($variantName, true)
                ));
            }
        }
        for ($i = 0; $i < 100; ++$i) {
            $bucket->addToggle(new MetricsBucketToggle(new DefaultFeature('flag', true, []), false));
        }

        self::assertSame(
            '1700000000;flag:1:blue:300,flag:1:green:100,flag:0:~:100;1700000060',
            $instance->serialize($bucket)
        );
        self::assertSame(
            ['flag' => ['yes' => 400, 'no' => 100, 'variants' => ['blue' => 300, 'green' => 100]]],
            $bucket->jsonSerialize()['toggles']
        );
    }

    /**
     * A name containing a delimiter has never round tripped through this format. It has to stay
     * the way it was, though: nonsense counts rather than an exception.
     */
    public function testDeserializeNameContainingADelimiter()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = $instance->deserialize('1700000000;we:ird:1:~:2;1700000060');

        self::assertCount(1, $bucket->getToggles());
        self::assertSame('we', $bucket->getToggles()[0]->getFeature()->getName());
    }

    public function testSerializeDeserializePreservesCountsAndInterval()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = new MetricsBucket(new DateTimeImmutable('@1700000000'));
        for ($i = 0; $i < 7; ++$i) {
            $bucket->addToggle(new MetricsBucketToggle(
                new DefaultFeature('featureA', true, []),
                true,
                new DefaultVariant($i < 5 ? 'variantA' : 'variantB', true)
            ));
        }
        for ($i = 0; $i < 3; ++$i) {
            $bucket->addToggle(new MetricsBucketToggle(new DefaultFeature('featureA', true, []), false));
        }
        $bucket->addToggle(new MetricsBucketToggle(new DefaultFeature('featureB', true, []), true));

        $deserialized = $instance->deserialize($instance->serialize($bucket));
        $deserialized->setEndDate(new DateTimeImmutable('@1700000060'));
        $bucket->setEndDate(new DateTimeImmutable('@1700000060'));

        self::assertSame(1700000000, $deserialized->getStartDate()->getTimestamp());
        self::assertSame($bucket->jsonSerialize(), $deserialized->jsonSerialize());
        self::assertCount(11, $deserialized->getToggles());
    }

    public function testSerializeDeserializePreservesEndDate()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = new MetricsBucket(new DateTimeImmutable('@1700000000'), new DateTimeImmutable('@1700000060'));
        $deserialized = $instance->deserialize($instance->serialize($bucket));

        self::assertSame(1700000060, $deserialized->getEndDate()->getTimestamp());
    }

    /**
     * A payload whose interval boundaries are unreadable must not throw inside a metrics flush,
     * losing at most the counts of the current interval.
     *
     * @dataProvider unreadableIntervalData
     */
    public function testDeserializeUnreadableInterval(string $serialized)
    {
        $instance = new DefaultMetricsBucketSerializer();

        self::assertSame([], $instance->deserialize($serialized)->getToggles());
    }

    public function unreadableIntervalData(): iterable
    {
        yield [''];
        yield ['garbage'];
        yield ['{"start":"2023-11-14T22:13:20+00:00"}'];
        yield ['1700000000'];
        yield ['1700000000;test:1:~:1'];
        yield ['1700000000;test:1:~:1;1700000060;extra'];
        yield ['not-a-timestamp;;~'];
        yield ['1700000000;;not-a-timestamp'];
    }

    /**
     * A suspect entry costs its own counts, not the interval: the bucket keeps its real start
     * date, so a caller can still tell that the interval has elapsed and send it. Resetting the
     * start date would leave the interval permanently incomplete and stop metrics being sent.
     *
     * @dataProvider unusableEntryData
     */
    public function testDeserializeSkipsAnUnusableEntryAndKeepsTheInterval(string $serialized)
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = $instance->deserialize($serialized);

        self::assertSame([], $bucket->getToggles());
        self::assertSame(1700000000, $bucket->getStartDate()->getTimestamp());
    }

    public function unusableEntryData(): iterable
    {
        // an entry that isn't a feature, an outcome and a variant
        yield ['1700000000;test;~'];
        yield ['1700000000;test:1;~'];
        // more evaluations than the limit allows to be expanded
        yield ['1700000000;test:1:~:500001;~'];
    }

    /**
     * An entry over the limit is skipped on its own. The limit exists so a corrupt count can't
     * expand into hundreds of MB of records, which is no reason to lose the entries either side.
     */
    public function testDeserializeKeepsTheEntriesAroundAnOversizedOne()
    {
        $instance = new DefaultMetricsBucketSerializer();

        $bucket = $instance->deserialize('1700000000;absurd:1:~:999999999,real:1:~:3;~');

        self::assertCount(3, $bucket->getToggles());
        self::assertSame('real', $bucket->getToggles()[0]->getFeature()->getName());
    }

    /**
     * The budget is the whole bucket's, not each entry's, so an entry is skipped once what came
     * before it has used the allowance up — and what came before it is kept.
     */
    public function testTheExpansionLimitAppliesAcrossEntries()
    {
        $instance = new DefaultMetricsBucketSerializer(10);

        $bucket = $instance->deserialize('1700000000;first:1:~:4,second:1:~:7,third:1:~:6;~');

        // first fits, second would reach 11, third fits in what first left
        self::assertCount(10, $bucket->getToggles());
        self::assertSame(['first', 'third'], array_values(array_unique(array_map(
            static fn (MetricsBucketToggle $toggle): string => $toggle->getFeature()->getName(),
            $bucket->getToggles(),
        ))));
    }

    /**
     * Only the consumer knows what its memory limit and request budget can absorb, so the limit
     * is theirs to set.
     */
    public function testTheExpansionLimitIsConfigurable()
    {
        $serialized = '1700000000;test:1:~:10;~';

        self::assertCount(10, (new DefaultMetricsBucketSerializer(10))->deserialize($serialized)->getToggles());
        self::assertSame([], (new DefaultMetricsBucketSerializer(9))->deserialize($serialized)->getToggles());
    }

    private function bucketWithRepeatedEvaluations(int $evaluations): MetricsBucket
    {
        $bucket = new MetricsBucket(new DateTimeImmutable('@1700000000'));
        for ($i = 0; $i < $evaluations; ++$i) {
            $bucket->addToggle(new MetricsBucketToggle(
                new DefaultFeature('test', true, []),
                true,
                new DefaultVariant('variant', true)
            ));
        }

        return $bucket;
    }
}
