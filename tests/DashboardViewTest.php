<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics\Tests;

use NimbusCMS\Analytics\BarChart;
use NimbusCMS\Analytics\DashboardView;
use PHPUnit\Framework\TestCase;

final class DashboardViewTest extends TestCase
{
    public function test_it_renders_the_total_chart_and_tables(): void
    {
        $html = (new DashboardView(7))->html(
            42,
            [['d' => date('Y-m-d'), 'c' => 5]],
            [['k' => '/home', 'c' => 10], ['k' => '/about', 'c' => 4]],
            [['k' => 'news.example', 'c' => 3]],
        );

        self::assertStringContainsString('42', $html, 'the total');
        self::assertStringContainsString('<svg', $html, 'the chart');
        self::assertStringContainsString('/home', $html);
        self::assertStringContainsString('news.example', $html);
        self::assertStringContainsString('Top pages', $html);
        self::assertStringContainsString('Top referrers', $html);
    }

    public function test_it_escapes_untrusted_values(): void
    {
        $html = (new DashboardView())->html(0, [], [['k' => '/x"<script>', 'c' => 1]], []);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_empty_data_reads_gracefully(): void
    {
        // The window is always filled day by day, so the chart still draws (as
        // zero-bars); the tables report that there is nothing yet.
        $html = (new DashboardView(7))->html(0, [], [], []);

        self::assertStringContainsString('<svg', $html);
        self::assertSame(7, substr_count($html, '<rect'), 'one zero-bar per day in the window');
        self::assertStringContainsString('Nothing yet', $html);
    }

    public function test_the_chart_draws_one_bar_per_point(): void
    {
        $svg = BarChart::render([
            ['label' => '2026-08-14', 'value' => 3],
            ['label' => '2026-08-15', 'value' => 5],
        ]);

        self::assertStringContainsString('<svg', $svg);
        self::assertSame(2, substr_count($svg, '<rect'));
    }

    public function test_a_truly_empty_series_shows_a_message(): void
    {
        self::assertStringContainsString('No pageviews', BarChart::render([]));
    }
}
