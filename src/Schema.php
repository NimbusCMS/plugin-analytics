<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

/**
 * The plugin's own table. Namespaced away from core `nb_*` (ADR 0005), and
 * deliberately minimal + privacy-first: a path, the referrer's host (not the
 * full URL, no query string), and a timestamp. No IP, no cookie, no user agent
 * stored.
 */
final class Schema
{
    public const TABLE = 'analytics_hits';

    /** @return list<string> */
    public static function hits(): array
    {
        return [
            'CREATE TABLE ' . self::TABLE . ' (
                id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                path          VARCHAR(191) NOT NULL,
                referrer_host VARCHAR(191) NULL,
                occurred_at   DATETIME NOT NULL,
                INDEX idx_occurred (occurred_at),
                INDEX idx_path (path)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
    }
}
