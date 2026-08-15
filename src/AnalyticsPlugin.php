<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

use Nimbus\Http\Request;
use Nimbus\Plugin\Plugin;
use Nimbus\Plugin\PluginContext;
use Nimbus\Plugin\PluginStorage;
use Nimbus\Support\CoreEvents;

/**
 * The official Analytics plugin — and the plugin that drove the NimbusCMS plugin
 * system from one capability to six (events, migrations, storage, admin pages;
 * plus the existing field-type and head capabilities).
 *
 * It does two independent things:
 *
 *  - **First-party analytics.** A `request.handled` listener records public page
 *    views into a table this plugin owns (its own migration), and an admin page
 *    charts them. No cookies, no third-party requests, no PII — just a path, a
 *    referrer host, and a timestamp.
 *  - **Third-party agents.** If one is configured (env), a head contributor
 *    injects the provider's snippet (Plausible, Fathom, or GA).
 *
 * Storage is taken lazily — a closure, resolved at request/render time — so
 * register() itself runs no query and loads fine even without a database.
 */
final class AnalyticsPlugin implements Plugin
{
    /** Matches extra.nimbus.id in composer.json. */
    public const ID = 'nimbuscms.analytics';

    public function register(PluginContext $context): void
    {
        $context->migrations()->register('001_hits', Schema::hits());

        $storage  = static fn (): PluginStorage => $context->storage();
        $recorder = new HitRecorder($storage);
        $context->events()->listen(
            CoreEvents::REQUEST_HANDLED,
            static function (mixed $payload) use ($recorder): void {
                $recorder->record($payload);
            },
        );

        $dashboard = new Dashboard($storage);
        $context->adminPages()->register(
            'analytics',
            'Analytics',
            '📊',
            static fn (Request $request): string => $dashboard->render(),
        );

        $agent = Agent::fromEnv();
        if ($agent !== null) {
            $context->head()->register(new AgentContributor($agent));
        }
    }
}
