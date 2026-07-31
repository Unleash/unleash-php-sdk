<?php

namespace Unleash\Client\Metrics;

use DateTimeImmutable;
use Override;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;

final readonly class DefaultMetricsBucketSerializer implements MetricsBucketSerializer
{
    /**
     * Counts are expanded back into one record per evaluation on read, because that is what
     * {@see MetricsBucket} holds. A count is short enough to claim far more evaluations than fit
     * in memory, which the repeated-entry format it replaces couldn't, so the total expansion per
     * bucket is bounded and an entry claiming more than the budget is skipped.
     *
     * Measured on PHP 8.4: ~113 bytes per expanded record, so 500,000 is ~54 MB, and expansion
     * costs roughly 0.2ms per 1,000 records. Only the consumer knows what its `memory_limit` and
     * its request budget can absorb — in one production deployment the web tier runs at 128M
     * while its queue runs at 1G — so the limit is a constructor argument. The default is set
     * well above any plausible interval: 500,000 evaluations in 60 seconds is more than 8,000 a
     * second, whole-fleet.
     */
    public const DEFAULT_MAX_EVALUATIONS = 500_000;

    /**
     * How many times an entry occurred, when the entry doesn't say. Writing the count instead of
     * repeating the entry is what stops the payload growing with every evaluation, and one is
     * what every entry written before this change meant, so the two formats are the same format:
     * payloads from older versions are read as they are, and older versions read these as one
     * evaluation per entry instead of choking on them.
     */
    private const IMPLIED_COUNT = 1;

    public function __construct(
        private int $maxEvaluations = self::DEFAULT_MAX_EVALUATIONS,
    ) {
    }

    #[Override]
    public function serialize(MetricsBucket $bucket): string
    {
        $counts = [];
        foreach ($bucket->getToggles() as $toggle) {
            $variantName = $toggle->getVariant()?->getName() ?? '~';
            $entry = "{$toggle->getFeature()->getName()}:";
            $entry .= $toggle->isSuccess() ? '1' : '0';
            $entry .= ":{$variantName}";
            $counts[$entry] ??= 0;
            ++$counts[$entry];
        }

        $serialized = $bucket->getStartDate()->getTimestamp() . ';';
        if (!count($counts)) {
            $serialized .= ';';
        }
        foreach ($counts as $entry => $count) {
            $serialized .= "{$entry}:{$count},";
        }
        $serialized = substr($serialized, 0, -1);
        $serialized .= ';';
        $serialized .= $bucket->getEndDate()?->getTimestamp() ?? '~';

        return $serialized;
    }

    /**
     * Never throws: a payload this can't make sense of yields a fresh empty bucket, because
     * losing one interval of counts is preferable to an exception inside a metrics flush.
     */
    #[Override]
    public function deserialize(string $serialized): MetricsBucket
    {
        return $this->parse($serialized) ?? new MetricsBucket(new DateTimeImmutable());
    }

    /**
     * Returns null only when the interval's own boundaries are unreadable, because that is the
     * one case with nothing worth keeping. A bucket whose *entries* are suspect still comes back
     * carrying its real start date: callers decide whether an interval has elapsed from that date,
     * so resetting it to `now` would keep the interval permanently incomplete and stop metrics
     * being sent at all, for as long as the condition held.
     */
    private function parse(string $serialized): ?MetricsBucket
    {
        $parts = explode(';', $serialized);
        if (count($parts) !== 3) {
            return null;
        }

        [$startTimestamp, $serializedToggles, $endTimestamp] = $parts;
        if (!ctype_digit($startTimestamp) || ($endTimestamp !== '~' && !ctype_digit($endTimestamp))) {
            return null;
        }

        $bucket = new MetricsBucket(
            (new DateTimeImmutable())->setTimestamp((int) $startTimestamp),
            $endTimestamp === '~' ? null : (new DateTimeImmutable())->setTimestamp((int) $endTimestamp),
        );

        $evaluations = 0;
        $entries = array_filter(explode(',', $serializedToggles), static fn (string $entry): bool => $entry !== '');
        foreach ($entries as $serializedToggle) {
            $fields = explode(':', $serializedToggle);
            if (count($fields) < 3) {
                // skip the entry, keep the rest of the interval
                continue;
            }

            // a name containing a delimiter has always ended up here as nonsense rather than as
            // an error, and the count of such an entry is not a number, so it counts as one
            $count = isset($fields[3]) && ctype_digit($fields[3]) ? (int) $fields[3] : self::IMPLIED_COUNT;

            if ($evaluations + $count > $this->maxEvaluations) {
                // skipped rather than clamped to the remaining budget: a count this far above a
                // plausible interval is likelier to be corrupt than honest, and clamping would
                // fabricate records from it
                continue;
            }

            $evaluations += $count;
            $this->addToggles($bucket, $fields[0], $fields[1] === '1', $fields[2], $count);
        }

        return $bucket;
    }

    /**
     * Rebuilds one record per evaluation, the shape {@see MetricsBucket} holds. The feature is a
     * stub, as it was before this change: nothing reads more than the name, the outcome and the
     * variant name back out of a bucket, so nothing else survived the cache round trip either.
     */
    private function addToggles(
        MetricsBucket $bucket,
        string $featureName,
        bool $success,
        string $variantName,
        int $count,
    ): void {
        $feature = new DefaultFeature(name: $featureName, enabled: true, strategies: []);
        $variant = $variantName === '~' ? null : new DefaultVariant($variantName, true);

        for ($i = 0; $i < $count; ++$i) {
            $bucket->addToggle(new MetricsBucketToggle(
                feature: $feature,
                success: $success,
                variant: $variant,
            ));
        }
    }
}
