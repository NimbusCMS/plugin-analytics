<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

/**
 * A configured third-party analytics agent — the provider and the one value it
 * needs — read from the environment. Cookieless providers (Plausible, Fathom)
 * are first-class; Google Analytics is supported for those who need it.
 *
 *   ANALYTICS_PROVIDER=plausible ANALYTICS_DOMAIN=example.com
 *   ANALYTICS_PROVIDER=fathom    ANALYTICS_SITE_ID=ABCDEF
 *   ANALYTICS_PROVIDER=ga        ANALYTICS_MEASUREMENT_ID=G-XXXXXXX
 */
final class Agent
{
    private function __construct(
        public readonly string $provider,
        public readonly string $value,
    ) {
    }

    /** The configured agent, or null when none is set. */
    public static function fromEnv(): ?self
    {
        $provider = strtolower(trim((string) getenv('ANALYTICS_PROVIDER')));

        return match ($provider) {
            'plausible' => self::of('plausible', (string) getenv('ANALYTICS_DOMAIN')),
            'fathom'    => self::of('fathom', (string) getenv('ANALYTICS_SITE_ID')),
            'ga', 'google', 'gtag' => self::of('ga', (string) getenv('ANALYTICS_MEASUREMENT_ID')),
            default => null,
        };
    }

    private static function of(string $provider, string $value): ?self
    {
        $value = trim($value);
        return $value === '' ? null : new self($provider, $value);
    }

    /** The provider's `<head>` snippet, with the configured value escaped. */
    public function snippet(): string
    {
        $v = htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8');

        return match ($this->provider) {
            'plausible' => '<script defer data-domain="' . $v . '" src="https://plausible.io/js/script.js"></script>',
            'fathom'    => '<script src="https://cdn.usefathom.com/script.js" data-site="' . $v . '" defer></script>',
            'ga'        => '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $v . '"></script>'
                . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
                . 'gtag(\'js\',new Date());gtag(\'config\',\'' . $v . '\');</script>',
            default => '',
        };
    }
}
