<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

use Closure;
use Nimbus\Http\Request;
use Nimbus\Http\Response;
use Nimbus\Plugin\PluginStorage;

/**
 * Records a first-party page view for each qualifying request.
 *
 * Wired to the best-effort, isolated `request.handled` event, so a slow or
 * failing insert can never break the response that was already served.
 *
 * The decision of *what* to record is the pure hit() method — unit-testable
 * without a database; record() just runs the insert when hit() says so.
 */
final class HitRecorder
{
    /** @var Closure():PluginStorage */
    private Closure $storage;

    /** @param callable():PluginStorage $storage resolved lazily, at request time */
    public function __construct(callable $storage)
    {
        $this->storage = Closure::fromCallable($storage);
    }

    public function record(mixed $payload): void
    {
        if (!is_array($payload)) {
            return;
        }
        $request  = $payload['request'] ?? null;
        $response = $payload['response'] ?? null;
        if (!$request instanceof Request || !$response instanceof Response) {
            return;
        }

        $hit = $this->hit($request, $response);
        if ($hit === null) {
            return;
        }

        ($this->storage)()->insert(
            'INSERT INTO ' . Schema::TABLE . ' (path, referrer_host, occurred_at) VALUES (:p, :r, :t)',
            ['p' => $hit['path'], 'r' => $hit['referrer_host'], 't' => date('Y-m-d H:i:s')],
        );
    }

    /**
     * What to record for a request, or null to skip it — a public HTML page view
     * only: a GET that returned a 200 text/html page, not admin/API/asset, not an
     * obvious bot.
     *
     * @return array{path:string,referrer_host:?string}|null
     */
    public function hit(Request $request, Response $response): ?array
    {
        if ($request->method !== 'GET' || $response->status !== 200) {
            return null;
        }
        if (!str_contains((string) $response->header('Content-Type'), 'text/html')) {
            return null;
        }
        foreach (['/admin', '/api', '/theme/assets'] as $prefix) {
            if ($request->path === $prefix || str_starts_with($request->path, $prefix . '/')) {
                return null;
            }
        }
        if ($this->looksLikeBot((string) $request->header('User-Agent'))) {
            return null;
        }

        return [
            'path'          => mb_substr($request->path, 0, 191),
            'referrer_host' => $this->referrerHost((string) $request->header('Referer')),
        ];
    }

    private function looksLikeBot(string $userAgent): bool
    {
        return $userAgent === ''
            || preg_match('/bot|crawl|spider|slurp|bing|google|yandex|duckduck|facebookexternalhit|preview/i', $userAgent) === 1;
    }

    /** The referrer's host, unless it is this site itself (internal navigation) or absent. */
    private function referrerHost(string $referer): ?string
    {
        $host = $referer === '' ? null : (parse_url($referer, PHP_URL_HOST) ?: null);
        if ($host === null) {
            return null;
        }
        $ownHost = parse_url((string) getenv('APP_URL'), PHP_URL_HOST) ?: null;
        if ($ownHost !== null && strcasecmp($host, $ownHost) === 0) {
            return null;
        }
        return mb_substr($host, 0, 191);
    }
}
