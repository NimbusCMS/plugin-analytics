<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics\Tests;

use NimbusCMS\Analytics\Agent;
use PHPUnit\Framework\TestCase;

final class AgentTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['ANALYTICS_PROVIDER', 'ANALYTICS_DOMAIN', 'ANALYTICS_SITE_ID', 'ANALYTICS_MEASUREMENT_ID'] as $key) {
            putenv($key);
        }
    }

    public function test_no_provider_configured_is_null(): void
    {
        self::assertNull(Agent::fromEnv());
    }

    public function test_a_provider_without_its_value_is_null(): void
    {
        putenv('ANALYTICS_PROVIDER=plausible'); // no ANALYTICS_DOMAIN
        self::assertNull(Agent::fromEnv());
    }

    public function test_plausible_snippet(): void
    {
        putenv('ANALYTICS_PROVIDER=plausible');
        putenv('ANALYTICS_DOMAIN=example.com');

        $snippet = (string) Agent::fromEnv()?->snippet();
        self::assertStringContainsString('plausible.io/js/script.js', $snippet);
        self::assertStringContainsString('data-domain="example.com"', $snippet);
    }

    public function test_fathom_snippet(): void
    {
        putenv('ANALYTICS_PROVIDER=fathom');
        putenv('ANALYTICS_SITE_ID=ABCDEF');

        $snippet = (string) Agent::fromEnv()?->snippet();
        self::assertStringContainsString('cdn.usefathom.com/script.js', $snippet);
        self::assertStringContainsString('data-site="ABCDEF"', $snippet);
    }

    public function test_google_analytics_snippet(): void
    {
        putenv('ANALYTICS_PROVIDER=ga');
        putenv('ANALYTICS_MEASUREMENT_ID=G-ABC123');

        $snippet = (string) Agent::fromEnv()?->snippet();
        self::assertStringContainsString('googletagmanager.com/gtag/js?id=G-ABC123', $snippet);
        self::assertStringContainsString("gtag('config','G-ABC123')", $snippet);
    }

    public function test_the_value_is_escaped_into_the_snippet(): void
    {
        putenv('ANALYTICS_PROVIDER=plausible');
        putenv('ANALYTICS_DOMAIN=a"><b');

        $snippet = (string) Agent::fromEnv()?->snippet();
        self::assertStringNotContainsString('a"><b', $snippet);
        self::assertStringContainsString('&quot;', $snippet);
    }
}
