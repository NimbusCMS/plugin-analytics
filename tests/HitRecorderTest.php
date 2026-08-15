<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics\Tests;

use Nimbus\Http\Request;
use Nimbus\Http\Response;
use Nimbus\Plugin\PluginStorage;
use NimbusCMS\Analytics\HitRecorder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The pure decision — which requests become a recorded hit — with no database.
 */
final class HitRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_URL=https://mysite.test');
    }

    protected function tearDown(): void
    {
        putenv('APP_URL');
    }

    private function recorder(): HitRecorder
    {
        // hit() never touches storage; the factory must never be called.
        return new HitRecorder(static fn (): PluginStorage => throw new RuntimeException('storage is not used by hit()'));
    }

    /** @param array<string,string> $server */
    private function get(string $path, array $server = []): Request
    {
        return new Request('GET', $path, [], [], $server + ['HTTP_USER_AGENT' => 'Mozilla/5.0'], []);
    }

    private function htmlOk(): Response
    {
        return Response::html('<p>hi</p>');
    }

    public function test_a_public_html_page_view_is_recorded(): void
    {
        self::assertSame(
            ['path' => '/posts/hello', 'referrer_host' => null],
            $this->recorder()->hit($this->get('/posts/hello'), $this->htmlOk()),
        );
    }

    public function test_admin_api_and_asset_paths_are_skipped(): void
    {
        foreach (['/admin', '/admin/plugins', '/api/v1/collections', '/theme/assets/app.css'] as $path) {
            self::assertNull($this->recorder()->hit($this->get($path), $this->htmlOk()), $path);
        }
    }

    public function test_non_get_non_200_and_non_html_are_skipped(): void
    {
        $post = new Request('POST', '/posts', [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0'], []);
        self::assertNull($this->recorder()->hit($post, $this->htmlOk()));
        self::assertNull($this->recorder()->hit($this->get('/posts'), Response::html('x', 404)));
        self::assertNull($this->recorder()->hit($this->get('/posts'), Response::json(['x' => 1])));
    }

    public function test_bots_and_blank_user_agents_are_skipped(): void
    {
        self::assertNull($this->recorder()->hit($this->get('/posts', ['HTTP_USER_AGENT' => 'Googlebot/2.1']), $this->htmlOk()));
        $noUa = new Request('GET', '/posts', [], [], [], []);
        self::assertNull($this->recorder()->hit($noUa, $this->htmlOk()), 'a blank UA looks like a bot');
    }

    public function test_an_external_referrer_host_is_captured(): void
    {
        self::assertSame(
            ['path' => '/posts', 'referrer_host' => 'news.example'],
            $this->recorder()->hit($this->get('/posts', ['HTTP_REFERER' => 'https://news.example/story?utm=x']), $this->htmlOk()),
        );
    }

    public function test_an_internal_referrer_is_dropped(): void
    {
        self::assertSame(
            ['path' => '/posts', 'referrer_host' => null],
            $this->recorder()->hit($this->get('/posts', ['HTTP_REFERER' => 'https://mysite.test/home']), $this->htmlOk()),
        );
    }
}
