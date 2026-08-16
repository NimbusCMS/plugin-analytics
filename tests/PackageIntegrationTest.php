<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics\Tests;

use Nimbus\Admin\AdminPageRegistry;
use Nimbus\Database\MigrationRegistry;
use Nimbus\Plugin\PluginCapabilities;
use Nimbus\Plugin\PluginLoader;
use Nimbus\Support\CoreEvents;
use Nimbus\Support\EventDispatcher;
use Nimbus\Support\MaintenanceRegistry;
use NimbusCMS\Analytics\AnalyticsPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Proves the *package boundary*: a real Composer installation of this package is
 * discovered by Nimbus's own loader, from this package's real manifest, and
 * registers its migration, its request listener, and its admin page — no
 * database required, because the plugin takes storage lazily.
 */
final class PackageIntegrationTest extends TestCase
{
    private string $installedJson;

    protected function setUp(): void
    {
        $this->installedJson = tempnam(sys_get_temp_dir(), 'nb-installed-') ?: '';
    }

    protected function tearDown(): void
    {
        @unlink($this->installedJson);
    }

    /** @return array<string,mixed> this package's actual composer manifest */
    private function manifest(): array
    {
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);
        self::assertIsArray($manifest);

        return $manifest;
    }

    private function installedAs(): string
    {
        $manifest = $this->manifest();
        file_put_contents($this->installedJson, json_encode([
            'packages' => [[
                'name'  => $manifest['name'],
                'type'  => $manifest['type'],
                'extra' => $manifest['extra'],
            ]],
        ], JSON_THROW_ON_ERROR));

        return $this->installedJson;
    }

    public function test_the_package_declares_nimbus_as_a_runtime_dependency(): void
    {
        $manifest = $this->manifest();

        self::assertArrayHasKey('nimbuscms/nimbus', $manifest['require']);
        self::assertArrayNotHasKey('nimbuscms/nimbus', $manifest['require-dev'] ?? []);
    }

    public function test_the_package_is_typed_as_a_nimbus_plugin(): void
    {
        self::assertSame('nimbuscms-plugin', $this->manifest()['type']);
    }

    public function test_discovery_registers_the_migration_listener_admin_page_and_retention(): void
    {
        $migrations  = new MigrationRegistry();
        $events      = new EventDispatcher();
        $adminPages  = new AdminPageRegistry();
        $maintenance = new MaintenanceRegistry();

        $loader      = new PluginLoader($this->installedAs());
        $diagnostics = $loader->load(new PluginCapabilities(
            migrations: $migrations,
            events: $events,
            adminPages: $adminPages,
            maintenance: $maintenance,
        ));

        self::assertSame([], $diagnostics, 'a correctly installed package must load cleanly');
        self::assertSame([AnalyticsPlugin::ID => $this->manifest()['name']], $loader->registered());

        self::assertSame(['nimbuscms.analytics:001_hits'], array_column($migrations->all(), 'name'), 'its migration');
        self::assertTrue($events->hasListeners(CoreEvents::REQUEST_HANDLED), 'its page-view listener');
        self::assertSame(['analytics'], array_column($adminPages->all(), 'slug'), 'its admin page');
        self::assertSame(['nimbuscms.analytics:prune-hits'], array_column($maintenance->all(), 'name'), 'its retention task');
    }

    public function test_disabling_the_package_registers_nothing(): void
    {
        $migrations = new MigrationRegistry();
        $adminPages = new AdminPageRegistry();

        (new PluginLoader($this->installedAs(), [AnalyticsPlugin::ID => false]))
            ->load(new PluginCapabilities(migrations: $migrations, adminPages: $adminPages));

        self::assertSame([], $migrations->all());
        self::assertSame([], $adminPages->all());
    }
}
