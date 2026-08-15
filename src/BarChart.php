<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

/**
 * A dependency-free SVG bar chart — server-rendered, no JavaScript, no build
 * step. Enough to show pageviews over time; each bar carries a `<title>` for a
 * native hover tooltip.
 */
final class BarChart
{
    /**
     * @param list<array{label:string,value:int}> $series
     */
    public static function render(array $series, int $width = 720, int $height = 180): string
    {
        if ($series === []) {
            return '<p class="nb-muted">No pageviews recorded yet.</p>';
        }

        $max   = max(1, ...array_map(static fn (array $point): int => $point['value'], $series));
        $count = count($series);
        $gap   = 3;
        $barW  = max(1.0, ($width - ($count - 1) * $gap) / $count);

        $bars = '';
        $x    = 0.0;
        foreach ($series as $point) {
            $barH  = (int) round(($point['value'] / $max) * ($height - 4));
            $y     = $height - $barH;
            $title = self::e($point['label'] . ' — ' . $point['value']);
            $bars .= sprintf(
                '<rect x="%.2f" y="%d" width="%.2f" height="%d" rx="2"><title>%s</title></rect>',
                $x,
                $y,
                $barW,
                $barH,
                $title,
            );
            $x += $barW + $gap;
        }

        return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Pageviews over time"'
            . ' style="width:100%;height:auto;fill:#6366f1">' . $bars . '</svg>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
