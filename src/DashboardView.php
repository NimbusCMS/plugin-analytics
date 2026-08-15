<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

/**
 * Builds the analytics dashboard HTML from aggregate rows — pure, so it is
 * unit-testable without a database. Every value that originated in a visitor's
 * request (paths, referrer hosts) is escaped before it reaches the page.
 */
final class DashboardView
{
    public function __construct(private int $days = 30)
    {
    }

    /**
     * @param array<int,array<string,mixed>> $perDay   rows of {d: 'Y-m-d', c: int}
     * @param array<int,array<string,mixed>> $topPages rows of {k: string, c: int}
     * @param array<int,array<string,mixed>> $topRefs  rows of {k: string, c: int}
     */
    public function html(int $total, array $perDay, array $topPages, array $topRefs): string
    {
        return '<div class="nb-page-head"><h1>Analytics</h1></div>'
            . '<p class="nb-muted" style="margin:-8px 0 20px">First-party page views — no cookies, no third-party requests. '
            . 'Last ' . $this->days . ' days.</p>'
            . '<p style="font-size:2rem;font-weight:600;margin:0">' . number_format($total) . '</p>'
            . '<p class="nb-muted" style="margin:.2rem 0 1.5rem">total pageviews</p>'
            . BarChart::render($this->series($perDay))
            . '<div style="display:flex;gap:2rem;flex-wrap:wrap;margin-top:2rem">'
            . $this->table('Top pages', $topPages)
            . $this->table('Top referrers', $topRefs)
            . '</div>';
    }

    /**
     * Fill the window day by day so the chart shows zero-days, not gaps.
     *
     * @param  array<int,array<string,mixed>> $perDay
     * @return list<array{label:string,value:int}>
     */
    private function series(array $perDay): array
    {
        $counts = [];
        foreach ($perDay as $row) {
            $counts[(string) ($row['d'] ?? '')] = (int) ($row['c'] ?? 0);
        }

        $series = [];
        for ($i = $this->days - 1; $i >= 0; $i--) {
            $day      = date('Y-m-d', strtotime("-{$i} days") ?: time());
            $series[] = ['label' => $day, 'value' => $counts[$day] ?? 0];
        }
        return $series;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function table(string $heading, array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr><td>' . $this->e((string) ($row['k'] ?? '')) . '</td>'
                . '<td style="text-align:right">' . number_format((int) ($row['c'] ?? 0)) . '</td></tr>';
        }
        if ($body === '') {
            $body = '<tr><td class="nb-muted" colspan="2">Nothing yet.</td></tr>';
        }

        return '<div style="flex:1;min-width:16rem"><h2 style="font-size:1rem">' . $this->e($heading) . '</h2>'
            . '<table class="nb-table" style="width:100%"><tbody>' . $body . '</tbody></table></div>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
