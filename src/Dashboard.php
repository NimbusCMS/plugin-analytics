<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

use Closure;
use Nimbus\Plugin\PluginStorage;

/**
 * The admin dashboard. Thin: it runs the aggregate queries over a rolling window
 * and hands the rows to DashboardView, which builds the (escaped) HTML. Keeping
 * the query here and the rendering there makes the presentation unit-testable
 * without a database.
 */
final class Dashboard
{
    private const DAYS = 30;

    /** @var Closure():PluginStorage */
    private Closure $storage;

    /** @param callable():PluginStorage $storage resolved lazily, at render time */
    public function __construct(callable $storage)
    {
        $this->storage = Closure::fromCallable($storage);
    }

    public function render(): string
    {
        $storage = ($this->storage)();
        $since   = date('Y-m-d 00:00:00', strtotime('-' . (self::DAYS - 1) . ' days') ?: time());

        $total = (int) ($storage->selectOne(
            'SELECT COUNT(*) AS c FROM ' . Schema::TABLE . ' WHERE occurred_at >= :s',
            ['s' => $since],
        )['c'] ?? 0);

        $perDay = $storage->select(
            'SELECT DATE(occurred_at) AS d, COUNT(*) AS c FROM ' . Schema::TABLE
            . ' WHERE occurred_at >= :s GROUP BY d',
            ['s' => $since],
        );
        $topPages = $storage->select(
            'SELECT path AS k, COUNT(*) AS c FROM ' . Schema::TABLE
            . ' WHERE occurred_at >= :s GROUP BY path ORDER BY c DESC LIMIT 10',
            ['s' => $since],
        );
        $topRefs = $storage->select(
            'SELECT referrer_host AS k, COUNT(*) AS c FROM ' . Schema::TABLE
            . ' WHERE occurred_at >= :s AND referrer_host IS NOT NULL GROUP BY referrer_host ORDER BY c DESC LIMIT 10',
            ['s' => $since],
        );

        return (new DashboardView(self::DAYS))->html($total, $perDay, $topPages, $topRefs);
    }
}
